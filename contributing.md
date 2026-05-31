# Contributing Guidelines

Thank you for considering contributing to the **Greenhouse Observation App**. Please follow the guidelines below so contributions land smoothly.

## Project state

The project is at **v1.0.0-rc.1 — release candidate** ([`changelog.md`](changelog.md)): Step 1 of the FDS is complete and deployed; most of Step 2 has also landed. The remaining major piece is **offline capture** (Service Worker + IndexedDB) per FR-REC-090.

Useful contributions right now:

- **Implementation feedback** — open an issue against an FR ID (`design/functionalRequirements.md`) with a reproduction, against a TDS ID (`design/technicalDesignSpecification.md`) with an architectural suggestion, or against a release listed in `changelog.md` for a regression.
- **Design-doc feedback** — comments, corrections, or gap reports against the FDS, TDS, or [`design/operatorObservationStrategy.md`](design/operatorObservationStrategy.md). Open an issue describing the relevant FR / TDS ID.
- **Manual feedback** — corrections or clarifications to [`manual/gebruikersHandleiding.md`](manual/gebruikersHandleiding.md) (Dutch, end users) or [`manual/administratorManual.md`](manual/administratorManual.md) (English, administrators).
- **Step 2 — offline capture** — Service Worker + IndexedDB queue, including the IndexedDB schema and conflict handling on sync.

## Getting Started

1. **Fork the Repository** — click the "Fork" button on the repository's page.
2. **Clone Your Fork**:
   ```sh
   git clone https://github.com/<your-user>/greenhouse-Observation-App.git
   ```
3. **Create a Branch** — use a descriptive name:
   ```sh
   git checkout -b feature/your-feature-name
   ```

## Making Changes

- Follow the project's coding style and the binding rules in the FDS and TDS. Where a change conflicts with an FR or TDS item, the spec wins — file an issue to discuss the spec change *before* the code change.
- Keep commits focused and meaningful.
- Write clear commit messages. Reference FR / TDS IDs where relevant (e.g. `FR-REC-070: validate photo MIME server-side`).
- Update the documentation when behaviour observable to a user or admin changes:
  - Update the **FDS** for *what* changes.
  - Update the **TDS** for *how* changes.
  - Update [`manual/gebruikersHandleiding.md`](manual/gebruikersHandleiding.md) for changes visible to operators, and [`manual/administratorManual.md`](manual/administratorManual.md) for changes visible to administrators. Regenerate the PDFs with `manual/md2pdf.py`.
  - Add a `changelog.md` entry under the next release section.

## Submitting a Pull Request

1. **Push to Your Fork**:
   ```sh
   git push origin feature/your-feature-name
   ```
2. **Open a Pull Request**:
   - Navigate to the original repository.
   - Click "New Pull Request".
   - Select your branch and provide a clear description, naming any FR / TDS IDs the change touches and any manual sections that were updated.

## Code Review Process

- PRs are reviewed by maintainers.
- Reviewers will check the change against the FDS / TDS — be open to feedback.
- Ensure your branch is up to date with the latest `main` before merging.

## Reporting Issues

- Check whether the issue is already reported.
- Provide detailed information: which FR / TDS ID it relates to, expected vs observed behaviour, reproduction steps if applicable, the release the issue was first observed in.
- Use clear and concise language.

## Community Standards

- Follow the [Code of Conduct](code_of_conduct.md).
- Be respectful and collaborative.
