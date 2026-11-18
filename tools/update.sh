#!/usr/bin/env bash
# Observation App container update -- runs ON the VPS, from the clone in $HOME.
#
# Rebuilds the webapp-fpm image on a FRESH php:8.3-fpm base, recreates the
# container, and verifies the app still answers through nginx.
#
# WHY THIS SCRIPT EXISTS -- `docker compose build` alone is not enough.
# The Dockerfile says `FROM php:8.3-fpm`, a floating tag, and Docker does NOT
# re-resolve a floating tag it already holds locally. A plain
# `docker compose build` therefore rebuilds OUR layers on top of whatever base
# was cached the first time: the app changes, the PHP underneath does not.
# That is how this image sat on a July base for three PHP patch releases
# (8.3.32 while Docker Hub was at 8.3.35) and was still unpatched during the
# CVE-2026-109618 / CVE-2026-109619 unauthenticated-RCE window. `--pull` is
# the whole fix, and it is exactly the flag that gets left off by hand.
#
# ALSO: `apt upgrade` on the VPS does NOT patch this PHP. The host's
# php8.3-fpm and this container are separate installations; only a rebuild
# touches the container. See the VPS config repo, php/readme.md.
#
# SCOPE: the image only. App code is bind-mounted (./ -> /var/www/html), so a
# `git pull` takes effect with no rebuild at all and is deliberately NOT done
# here. If composer.json changed, re-run the composer install step from
# docker-compose.yml's header separately -- vendor/ lives on the host, not in
# the image, so a rebuild neither updates nor breaks it.
#
# Idempotent: when the base image has not moved every layer is cached, the
# image ID does not change, and `up -d` is a no-op. Safe to run on a timer.
#
# Usage:   tools/update.sh [--no-check]
#            --no-check    skip the end-to-end HTTPS check (e.g. no DNS here)
#          CHECK_URL=...   override the URL checked (default below)
#
# Exit:    0 = updated, or already current | 1 = failed, with a rollback hint
#
# The pre-update image is always kept as webapp-fpm:previous.

set -euo pipefail
cd "$(dirname "$0")/.."

CHECK=1
for a in "$@"; do
    case "$a" in
        --no-check) CHECK=0 ;;
        -h|--help)  sed -n '2,36p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *)          echo "unknown argument: $a (try --help)" >&2; exit 1 ;;
    esac
done
: "${CHECK_URL:=https://rfsee.net/webapp/}"

[[ -f Dockerfile && -f docker-compose.yml ]] \
    || { echo "ERROR: no Dockerfile/docker-compose.yml above tools/ -- run from the clone" >&2; exit 1; }
command -v docker >/dev/null 2>&1 || { echo "ERROR: docker not found" >&2; exit 1; }

FAIL=0

echo "== current container PHP"
BEFORE=$(docker exec webapp-fpm php -r 'echo PHP_VERSION;' 2>/dev/null || echo "(not running)")
echo "   $BEFORE"

echo "== keep a rollback point as webapp-fpm:previous"
if docker image inspect webapp-fpm:latest >/dev/null 2>&1; then
    docker tag webapp-fpm:latest webapp-fpm:previous
else
    echo "   (no webapp-fpm:latest yet -- first build, nothing to roll back to)"
fi

echo "== build on a freshly pulled base image"
docker compose build --pull

echo "== recreate the container (a no-op if the image ID did not change)"
docker compose up -d

echo "== wait for the healthcheck"
st=""
for _ in $(seq 1 20); do
    st=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' \
         webapp-fpm 2>/dev/null || true)
    if [[ "$st" == "healthy" || "$st" == "none" ]]; then
        break
    fi
    sleep 3
done
if [[ "$st" == "healthy" ]]; then
    echo "   healthy"
elif [[ "$st" == "none" ]]; then
    echo "   (container defines no healthcheck -- skipped)"
else
    echo "   FAILED: status '${st:-unknown}' after 60s" >&2
    FAIL=1
fi

echo "== new container PHP"
AFTER=$(docker exec webapp-fpm php -r 'echo PHP_VERSION;' 2>/dev/null || echo "(not running)")
echo "   $BEFORE  ->  $AFTER"

# End-to-end, because a healthy FPM process proves nothing about the fastcgi
# hop: nginx reaches this container over 127.0.0.1:9083 with SCRIPT_FILENAME
# rewritten to the container's /var/www/html, and that mapping is the part
# most likely to break silently (it shows up as 502/404, not as unhealthy).
if [[ $CHECK -eq 1 && $FAIL -eq 0 ]]; then
    echo "== end-to-end: $CHECK_URL"
    code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "$CHECK_URL" || echo 000)
    if [[ "$code" == "200" || "$code" == "302" ]]; then
        echo "   HTTP $code"
    else
        echo "   FAILED: HTTP $code" >&2
        FAIL=1
    fi
fi

if [[ $FAIL -ne 0 ]]; then
    cat >&2 <<'EOF'

== UPDATE FAILED -- the new image is live and not serving. Roll back with:

     docker tag webapp-fpm:previous webapp-fpm:latest
     docker compose up -d --force-recreate

   --force-recreate is required: moving the tag leaves the name unchanged, so
   compose would otherwise see no reason to touch the running container.
EOF
    exit 1
fi

if [[ "$BEFORE" == "$AFTER" ]]; then
    echo "== done. PHP unchanged ($AFTER) -- the base image had not moved."
else
    echo "== done. PHP $BEFORE -> $AFTER"
fi
echo "   Previous image kept as webapp-fpm:previous."
