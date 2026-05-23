# Contributing Guidelines

Thank you for considering contributing to the **Greenhouse Observation App**. Please follow the guidelines below so contributions land smoothly.

## Project state

The project is currently in its **design phase** (FDS + TDS complete, implementation not yet started). That shapes what useful contributions look like right now:

- **Design-doc feedback** — comments, corrections, or gap reports against `design/functionalRequirements.md` (FDS) or `design/technicalDesignSpecification.md` (TDS). Open an issue describing the FR or TDS ID and the concern.
- **Mock-up review** — feedback on the M1–M9 sketches in `design/operatorObservationStrategy.md`.
- **Step 1 implementation** — once started, contributions follow the FR IDs as units of work.

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
- Update the documentation when behaviour observable to a user or admin changes. Update the FDS for *what* changes; update the TDS for *how* changes.

## Submitting a Pull Request

1. **Push to Your Fork**:
   ```sh
   git push origin feature/your-feature-name
   ```
2. **Open a Pull Request**:
   - Navigate to the original repository.
   - Click "New Pull Request".
   - Select your branch and provide a clear description, naming any FR / TDS IDs the change touches.

## Code Review Process

- PRs are reviewed by maintainers.
- Reviewers will check the change against the FDS / TDS — be open to feedback.
- Ensure your branch is up to date with the latest `main` before merging.

## Reporting Issues

- Check whether the issue is already reported.
- Provide detailed information: which FR / TDS ID it relates to, expected vs observed behaviour, reproduction steps if applicable.
- Use clear and concise language.

## Community Standards

- Follow the [Code of Conduct](code_of_conduct.md).
- Be respectful and collaborative.
