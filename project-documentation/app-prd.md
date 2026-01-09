# PRD — Konvertera Conversion Engine (Composer Package)

**Repository:** `konvertera-engine` (separate repo)  
**Artifact:** PHP Composer package (PSR-4 autoload), *no WordPress dependency*  
**Primary consumers:** WordPress MU-plugin + theme (Bedrock/Acorn/Sage), CLI tooling, REST handlers  
**Date:** 2026-01-07

---

## 1) Background and context

Konvertera.nu is a legacy unit conversion site (originally 2003) where each category page presents a table of units. Users can enter a value in any row and the system updates all other rows immediately. The legacy system used:

- a generic “reference factor” conversion method for most categories
- special-case formulas for temperature and fuel consumption
- formatting controls: significant figures (1–7), scientific notation toggle, decimal separator toggle (comma/dot)
- permissive input: comma or dot as decimal separator

This PRD defines a modern, testable, reusable conversion engine that preserves the core behavior while enabling a data-driven expansion to many more conversion categories.

---

## 2) Goals

### 2.1 Functional goals
1) Provide a **category-based conversion engine** that can convert a value from one unit to **all units** in the category deterministically.

2) Support **data-driven unit definitions** (JSON files tracked in git) so that adding categories/units is primarily data work rather than new PHP code.

3) Support the historical UX features via a shared formatting pipeline:
- significant figures (1–7)
- scientific notation on/off
- output decimal separator dot/comma
- input accepts dot/comma (normalized internally)

4) Provide a generalized conversion model that eliminates most “special-case converters”:
- Temperature must be representable using an **affine transform** (factor + offset) to/from a base unit.
- Fuel consumption must be representable using **reciprocal transforms** to/from a base unit.

5) Provide a clean API surface for:
- server-side rendering in WordPress
- REST endpoints in WordPress
- JS parity testing (engine output should match frontend algorithm)

### 2.2 Quality goals
- Highly testable (unit tests + golden tests).
- Deterministic formatting and conversion output.
- Well-documented, stable, semantic versioning.
- Performant for typical category sizes (dozens to low hundreds of units).

---

## 3) Non-goals

- **No WordPress code** in this repo (no `wp_*` functions, no plugins).
- No UI/HTML output.
- No direct DB persistence layer (data is loaded from JSON; consumers may store/cache externally).
- Currency conversion and real-time external data fetching are **out-of-scope** for v1.
- No built-in i18n infrastructure beyond storing localized strings in data (consumer decides locale selection).

---

## 4) Key concepts and definitions

### 4.1 Category
A conversion category groups units that can be mutually converted, e.g. `length`, `mass`, `temperature`.

Each category defines:
- a unique `key`
- a `base_unit` (canonical internal reference unit)
- an array of `units`
- optional `family` classification (used for site navigation by consumers)
- optional `types` (group headers), e.g. “Metric”, “US”, “UK”

### 4.2 Unit
A unit defines:
- stable `key` (slug-safe identifier)
- display names (localized)
- abbreviation (string; may contain unicode like `°C`)
- optional tooltip/help text
- conversion transform between the unit and the category base unit

### 4.3 Transform types
To enable data-driven conversions without special-case PHP:

#### (A) Linear (factor)
- `base = value * factor`
- `value = base / factor`

Use for most physical units.

#### (B) Affine (factor + offset)
- `base = value * factor + offset`
- `value = (base - offset) / factor`

Use for temperature-like systems (e.g. Celsius/Fahrenheit to Kelvin base).

#### (C) Reciprocal-factor
Used for representations like “distance per unit fuel” vs “fuel per distance”.
Two equivalent forms are allowed; choose **one** canonical in code and allow the other as alias for data.

**Canonical definition in v1:**
- `base = factor / value`
- `value = factor / base`

This supports conversions like:
- base `l/km` from `km/l`: `base = 1 / value` (`factor = 1`)
- base `l/km` from `mpg (US)`: `base = 2.352145 / value` (`factor = 2.352145`)

> NOTE: This model must explicitly handle division by zero.

#### (D) Future extension hook (custom)
Allow a `kind: "custom"` placeholder with a `custom_key` for rare categories. v1 must implement only (A)–(C), but the API should not prevent adding (D) later.

---

## 5) Data specification

### 5.1 Directory layout
The package must ship category definitions under:

- `resources/categories/*.json`
- `resources/index.json` (optional but recommended)

### 5.2 Category JSON schema (v1)
Example (abbreviated):

```json
{
  "schema_version": 1,
  "key": "temperature",
  "family": "physics",
  "base_unit": "kelvin",
  "types": [
    {"key": "metric", "name": {"sv": "Metrisk", "en": "Metric"}},
    {"key": "imperial", "name": {"sv": "Anglosaxisk", "en": "Imperial"}}
  ],
  "units": [
    {
      "key": "kelvin",
      "type": "metric",
      "name": {"sv": "Kelvin", "en": "Kelvin"},
      "abbr": "K",
      "help": {"sv": "SI-enheten för temperatur.", "en": "SI base unit of temperature."},
      "transform": {"kind": "affine", "factor": 1, "offset": 0}
    }
  ]
}
```

#### Required fields
- `schema_version` (int): must be `1` in v1.
- `key` (string): stable category identifier.
- `base_unit` (string): must match one unit `key`.
- `units` (array): non-empty.

#### Unit fields
- `key` (string, required)
- `name` (object map locale=>string, required; at least `sv` and/or `en`)
- `abbr` (string, required; may be empty if needed)
- `type` (string, optional; must match a `types[].key` if provided)
- `help` (object map locale=>string, optional)
- `transform` (object, required)

#### Transform fields
- `kind` (enum: `linear|affine|reciprocal_factor|custom`)
- For `linear`: `factor` (number)
- For `affine`: `factor` (number), `offset` (number)
- For `reciprocal_factor`: `factor` (number)
- For `custom`: `custom_key` (string) + optional params (future)

### 5.3 Numeric expectations
- `factor` and `offset` are JSON numbers; consumers may render as floats.
- Provide sufficient precision for known constants (e.g. Fahrenheit transform).
- The engine must not re-serialize and round constants; store as-is from JSON.

### 5.4 `resources/index.json` (recommended)
Index file listing available categories and minimal metadata for navigation.

```json
{
  "schema_version": 1,
  "categories": [
    {"key": "length", "family": "physics"},
    {"key": "mass", "family": "physics"},
    {"key": "temperature", "family": "physics"}
  ],
  "families": [
    {"key": "physics", "name": {"sv": "Fysik", "en": "Physics"}}
  ]
}
```

---

## 6) Public PHP API (v1)

> The API must be stable and documented; consumers (WordPress MU-plugin) should not access internal classes directly except through these.

### 6.1 Core classes (namespaces are suggestions)
- `Konvertera\Engine\CategoryRepositoryInterface`
- `Konvertera\Engine\JsonCategoryRepository` (reads JSON from disk)
- `Konvertera\Engine\ConversionEngine`
- `Konvertera\Engine\Formatter`
- `Konvertera\Engine\ValueParser`

### 6.2 Data access
```php
interface CategoryRepositoryInterface {
  public function listCategories(): array;                 // list of category metadata
  public function getCategory(string $key): Category;      // throws if missing
}
```

### 6.3 Conversion
```php
final class ConversionEngine {
  public function __construct(CategoryRepositoryInterface $repo);

  /**
   * Convert from one unit to all units in a category.
   *
   * @return array<string, float> map of unitKey => numericValue
   */
  public function convertAll(
    string $categoryKey,
    string $fromUnitKey,
    float $value
  ): array;
}
```

### 6.4 Formatting (server-side)
```php
final class FormatOptions {
  public int $significantFigures;     // 1..7
  public bool $scientificNotation;    // true => may use e-notation
  public string $decimalSeparator;    // "." or ","
}

final class Formatter {
  /** @return string formatted */
  public function format(float $value, FormatOptions $opts): string;

  /** @return array<string, string> */
  public function formatAll(array $valuesByUnit, FormatOptions $opts): array;
}
```

### 6.5 Parsing (input normalization)
```php
final class ParseOptions {
  public bool $acceptCommaDecimal;    // true in v1
  public bool $acceptDotDecimal;      // true in v1
  public bool $acceptExponent;        // true in v1 ("1e-3")
  public bool $trimSpaces;            // true in v1
  public bool $allowThousandsSep;     // false in v1 (avoid ambiguity)
}

final class ValueParser {
  /** @return float */
  public function parse(string $raw, ParseOptions $opts): float;
}
```

Parsing must:
- accept comma or dot as decimal separator (normalize to dot)
- reject invalid strings with a typed exception
- treat empty string as invalid (consumer decides UI behavior)

### 6.6 Error handling
Provide specific exceptions:
- `CategoryNotFound`
- `UnitNotFound`
- `InvalidNumber`
- `DivisionByZero` (or return `INF` with explicit policy; see §7)

Consumers must get actionable error types.

---

## 7) Conversion rules and edge-case policies

### 7.1 Division by zero (reciprocal units)
When converting from a reciprocal unit and input is exactly `0`:
- The numeric base computation would be `factor / 0` → infinity.
- v1 policy:
  - `convertAll()` must throw a `DivisionByZero` exception.
  - Consumers (WP UI) should display an inline “invalid / cannot divide by zero” state.

### 7.2 NaN and Infinity
- `convertAll()` must never return NaN/INF values silently.
- If an intermediate result is not finite, throw a domain exception.

### 7.3 -0 normalization
Floating operations may yield `-0.0`.
- In `Formatter::format()`, normalize `-0.0` to `0.0` before formatting.

### 7.4 Base unit inclusion
- The base unit must be present and convertible.
- Units list order must be preserved from JSON (used by UI).

---

## 8) Significant figures and formatting specification

### 8.1 Requirements
- Significant figures: integer 1–7 (inclusive).
- Scientific notation: if enabled, output may use `e` format.
- Decimal separator: output must use `.` or `,` (no thousands separator).
- Output must not end with a dangling decimal separator (e.g. `12.` or `12,`).

### 8.2 Formatting behavior (v1 policy)
Given `value` (finite float) and `sig = 1..7`:

1) If `value == 0`, output `"0"` (or `"0,0"` is **not** used; just `0`).

2) Determine digits/exponent:
- `abs = abs(value)`
- `exp = floor(log10(abs))` for non-zero

3) Determine rounding scale to achieve sig figs:
- `scale = sig - 1 - exp`
- Round using decimal precision `scale`:
  - if `scale >= 0`: round to `scale` decimal places
  - if `scale < 0`: round to `-scale` places left of decimal

4) If `scientificNotation == true`:
- Use a representation with exactly `sig` significant digits.
- Example: for sig=4, use `sprintf('%.3e', $valueRoundedToSigFigs)` and ensure mantissa rounding matches sig figs.

5) Else (plain notation):
- Render as decimal without exponent.
- Include trailing zeros *as needed* to reflect the chosen significant figures after rounding.
- Remove trailing decimal separator if present.

6) Apply decimal separator substitution at the end:
- if requested separator is comma, replace `.` with `,` in the final string.

> NOTE: The trailing-zero policy is important for “significant figures” behavior. The engine must include trailing zeros where they represent significant digits (e.g. `1.230` for sig=4). It must not add thousands separators.

### 8.3 Cross-language parity
The algorithm must be documented such that the WordPress frontend JS implementation can match it exactly. Provide test vectors in this repo under `tests/fixtures/formatting.json`.

---

## 9) Testing requirements

### 9.1 Unit tests
- Test each transform kind:
  - linear round-trip accuracy
  - affine correctness (including Fahrenheit/Celsius/Kelvin cases)
  - reciprocal-factor correctness
- Test parser input variants (comma/dot/exponent).
- Test formatter output for known cases.

### 9.2 Golden tests (behavior locking)
Create golden tests for legacy parity. At minimum:
- Length: verify multiple known conversions (e.g. `1 m` -> `100 cm`, `3.28084 ft` etc.)
- Temperature: verify known points (0°C, 100°C, -40°C, 32°F, etc.)
- Fuel consumption: verify a set of known conversions using legacy constants.

Golden tests should:
- compare numeric outputs within a strict tolerance (e.g. `1e-12` for numeric)
- compare formatted outputs exactly for a fixed formatting option set.

### 9.3 Test tooling
- Use PHPUnit or Pest (either is acceptable).
- CI must run tests on supported PHP versions (define in composer).

---

## 10) Performance and caching expectations

- Category JSON files are small; loading should be cached in-memory within a request.
- `JsonCategoryRepository` must:
  - lazily load categories on demand
  - cache parsed Category objects
  - validate schema once per load

Consumers (WordPress) may add persistent caching (transients/object cache). This repo should not.

---

## 11) Documentation requirements

Provide:
- README explaining concept, how to add a category, schema rules, transform kinds.
- Examples for:
  - listing categories
  - converting values
  - formatting output
  - parsing user input
- “How to add new conversion category” step-by-step.

---

## 12) Versioning and release

- Use semantic versioning.
- Breaking changes to JSON schema require:
  - increment `schema_version`
  - add migration notes
  - likely major version bump
- v1 must maintain schema_version=1.

---

## 13) Acceptance criteria (v1)

1) A consumer can load category definitions from `resources/categories/*.json` and list categories via repository.

2) `ConversionEngine::convertAll()` correctly converts across:
- at least one linear category (length)
- temperature (affine)
- fuel consumption (reciprocal-factor and linear units together)

3) Formatter supports:
- sig figs 1..7
- scientific notation toggle
- decimal separator output
- no trailing dangling separators
- stable output on golden tests

4) Parser supports:
- comma or dot decimals
- exponent notation
- rejects invalid input with `InvalidNumber`

5) Comprehensive tests exist and pass in CI.

---

## 14) Suggested initial category set (for seeding)

Implement as data and tests:
- `length`
- `mass`
- `temperature`
- `fuel_consumption`
(others can be migrated later)

---

## 15) Future extensions (not in v1, but supported by design)

- Currency conversion (external rates + caching) via `custom` transform strategy.
- Complex categories requiring piecewise transforms.
- Unit aliases and search synonyms.
- Locale-aware formatting beyond decimal separator (grouping, thin spaces).
- “Unit families” taxonomy expansion.

