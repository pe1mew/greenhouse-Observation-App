<?php
declare(strict_types=1);
namespace GreenhouseObs;

class PhotoHandler
{
    const MAX_BYTES = 8 * 1024 * 1024; // 8 MB
    const MAX_DIM   = 8192;

    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Validate an uploaded file. Returns a lang key on failure, null on success. */
    public static function validate(array $file): ?string
    {
        if ($file['size'] > self::MAX_BYTES) {
            return 'photo_too_large';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if ($mime === 'image/heic' || $mime === 'image/heif') {
            return 'photo_heic_unsupported';
        }
        if (!isset(self::ALLOWED[$mime])) {
            return 'photo_invalid_type';
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info && ($info[0] > self::MAX_DIM || $info[1] > self::MAX_DIM)) {
            return 'photo_dimensions_too_large';
        }

        return null;
    }

    /** Move a validated upload to photo_root. Returns the stored filename. */
    public static function store(array $file, string $photoRoot, int $obsId): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = self::ALLOWED[$mime] ?? 'jpg';
        $name  = $obsId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], rtrim($photoRoot, '/') . '/' . $name);
        return $name;
    }

    /** Delete a stored photo file. */
    public static function delete(string $photoRoot, string $filename): void
    {
        $path = rtrim($photoRoot, '/') . '/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    /** Stream a stored photo to the client and exit. */
    public static function serve(string $photoRoot, string $filename): void
    {
        $path = rtrim($photoRoot, '/') . '/' . $filename;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
