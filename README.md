# convert-units

Konvertera conversion engine as a standalone PHP Composer package. The goal is to provide a deterministic, data-driven unit conversion library that can be consumed by WordPress (MU-plugin/theme), CLI tooling, and REST handlers without bundling any WordPress code.

## What this package is
- A category-based conversion engine (convert one unit to all units in a category).
- Data-driven categories and units defined in JSON under `resources/categories/*.json`.
- A stable public PHP API for conversion, formatting, and parsing.

## Core behaviors
- Transform kinds: `linear`, `affine` (factor + offset), `reciprocal_factor` (for fuel consumption).
- Formatting: significant figures (1..7), optional scientific notation, configurable decimal separator.
- Parsing: accepts dot or comma decimal separators and exponent notation; rejects invalid input.

## Status
This repository currently contains the product requirements and scaffolding. Implementation, resources, and tests are to be added according to the PRD.

## Where to look
- `docs/app-prd.md` is the source of truth for schema, API, formatting rules, and acceptance criteria.
- `AGENTS.md` provides guidance for AI agents working in this repo.

## Planned structure
- `resources/`: category JSON data
- `src/`: PHP engine implementation
- `tests/`: unit and golden tests

## Contributing
If you add or change behavior, align with the PRD and include tests for conversion, parsing, and formatting.
