# AGENTS

Purpose: This repository defines a standalone PHP Composer package for the Konvertera conversion engine. The Product Requirements Document is the source of truth.

## Read first
- `docs/app-prd.md` is authoritative for behavior, data schema, API surface, and tests.

## Project constraints
- Package must be framework-agnostic; no WordPress code in this repo.
- Conversions are data-driven via JSON under `resources/categories/*.json` and optional `resources/index.json`.
- Supported transform kinds in v1: `linear`, `affine`, `reciprocal_factor`.
- Formatting and parsing behavior must match the PRD (significant figures, decimal separators, permissive input parsing).

## Expectations for changes
- Preserve JSON order and numeric precision when loading data.
- Add tests for conversions, parsing, and formatting; include golden tests where specified.
- Keep public API stable and documented; internal classes are not for direct consumer use.

## Repository layout
- `docs/app-prd.md`: product requirements and acceptance criteria.
- `resources/`: category JSON data (to be added).
- `src/`: PHP engine implementation (to be added).
- `tests/`: unit and golden tests (to be added).
