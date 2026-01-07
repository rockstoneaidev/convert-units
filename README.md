# convert-units

Konvertera conversion engine as a standalone PHP Composer package. The goal is to provide a deterministic, data-driven unit conversion library that can be consumed by WordPress (MU-plugin/theme), Laravel, CLI tooling, and REST handlers without bundling any WordPress code.

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

## Usage (framework-agnostic)
## Installation
Install via Composer:

```bash
composer require konvertera/engine
```

## Usage (framework-agnostic)
The engine loads JSON categories from `resources/` by default; pass a custom path if you ship data elsewhere.

```php
use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\JsonCategoryRepository;

$repo = new JsonCategoryRepository(__DIR__ . '/resources');
$engine = new ConversionEngine($repo);

$values = $engine->convertAll('length', 'meter', 1.0);
// $values['centimeter'] === 100.0
```

Formatting and parsing:

```php
use Konvertera\Engine\FormatOptions;
use Konvertera\Engine\Formatter;
use Konvertera\Engine\ParseOptions;
use Konvertera\Engine\ValueParser;

$parser = new ValueParser();
$parseOptions = new ParseOptions();
$value = $parser->parse('12,5', $parseOptions);

$formatter = new Formatter();
$formatOptions = new FormatOptions();
$formatOptions->significantFigures = 4;
$formatOptions->decimalSeparator = ',';

$formatted = $formatter->format($value, $formatOptions);
// "12,50"
```

## Shipping custom categories
You can ship your own JSON definitions with your app. Keep them in a dedicated folder and point `JsonCategoryRepository` to it:

```php
use Konvertera\Engine\JsonCategoryRepository;

$repo = new JsonCategoryRepository(__DIR__ . '/resources');
```

Requirements:
- JSON files follow the schema in `docs/app-prd.md`.
- Each category is stored at `categories/<key>.json`.
- Optional `index.json` can list categories for navigation.

## Usage in WordPress
This package is framework-agnostic, so a WordPress MU-plugin or theme should only call the public API. Keep any WordPress-specific code outside this repository.

Example (MU-plugin):

```php
use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\JsonCategoryRepository;

$repo = new JsonCategoryRepository(WP_CONTENT_DIR . '/konvertera/resources');
$engine = new ConversionEngine($repo);
$values = $engine->convertAll('temperature', 'celsius', 0.0);
```

## Usage in Laravel
Register the repository and engine in a service provider, then inject where needed.

```php
use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\JsonCategoryRepository;

$repo = new JsonCategoryRepository(base_path('resources/konvertera'));
$engine = new ConversionEngine($repo);
```

## Planned structure
- `resources/`: category JSON data
- `src/`: PHP engine implementation
- `tests/`: unit and golden tests

## Contributing
If you add or change behavior, align with the PRD and include tests for conversion, parsing, and formatting.
