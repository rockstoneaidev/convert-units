# AGENTS

Purpose: This repository defines a standalone PHP Composer package for the Konvertera conversion engine. The Product Requirements Document is the source of truth.

## Read first
- `project-documentation/app-prd.md` is authoritative for behavior, data schema, API surface, and tests.

## Project constraints
- Package must be framework-agnostic; no WordPress code in this repo.
- Conversions are data-driven via JSON under `resources/categories/*.json` and optional `resources/index.json`.
- Supported transform kinds in v1: `linear`, `affine`, `reciprocal_factor`.
- Formatting and parsing behavior must match the PRD (significant figures, decimal separators, permissive input parsing).

## Expectations for changes
- Preserve JSON order and numeric precision when loading data.
- Add tests for conversions, parsing, and formatting; include golden tests where specified.
- Keep public API stable and documented; internal classes are not for direct consumer use.

## Branching: `main` is protected — squash-merge only

`main` rejects direct pushes, and it rejects **merge commits**. Every change lands as a
squash-merged PR, which means `main` gets **one new commit whose hash your local branches
have never seen**. Merging a branch into local `main` and pushing therefore always fails:

```
remote: - This branch must not contain merge commits.
remote: - Changes must be made through a pull request.
```

**Never merge into local `main`.** The flow is:

```bash
git checkout main && git pull --ff-only origin main   # start from upstream
git checkout -b feat/my-change                        # branch, commit, push
gh pr create --base main                              # PR
gh pr merge <n> --squash                              # squash-merge
git checkout main && git fetch origin
git reset --hard origin/main                          # adopt the squash commit
```

After a squash merge, the PR branch's individual commits are **not** ancestors of `main`.
Any long-lived branch still holding them (`review`, `staging`) will replay them into `main`
the next time it is merged there — which is how the same already-merged commits keep
reappearing. Flatten those branches after each squash so they stay linear:

```bash
git checkout review && git reset --hard origin/main && git push --force-with-lease origin review
```

Before discarding local commits, confirm the content is already upstream — `git diff
origin/main main` should be empty. If it is, the local commits are redundant history, not
lost work. (This has bitten twice: PR #3 and PR #4.)

## Repository layout
- `project-documentation/app-prd.md`: product requirements and acceptance criteria.
- `resources/`: category JSON data (to be added).
- `src/`: PHP engine implementation (to be added).
- `tests/`: unit and golden tests (to be added).
