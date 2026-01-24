# SEO Content Ideas for Unit Pages

## Observed structure from metric-conversions.org example
- Unit header: name + abbreviation + unit family.
- Quick facts: definition, simple equivalence (e.g., 1 cm = 0.39370 in), worldwide use.
- Origin/history: longer narrative, includes standards bodies (CGPM/BIPM).
- Common references: everyday examples + canonical equalities.
- Usage context: practical fields and examples.
- Redundancy: some points repeat for emphasis (usage + worldwide use), which is OK for SEO.

## Content blocks to generate inside this package
- Unit summary: 1–2 sentence definition using base unit and common equivalence.
- Abbreviation/symbol and unit family (length, area, etc.).
- Formal definition: SI/BIPM-based if available in PRD data.
- Conversion highlight: 1 unit to 2–3 common units (metric + imperial).
- Quick list of canonical equalities (e.g., 1 m = 100 cm).
- Short usage note per family (length, mass, time, etc.).

## Content blocks better suited to konvertera.nu (dynamic / editorial)
- Long-form origin/history narratives (per unit or per family).
- Country usage notes + locale-specific exceptions.
- Curated common references (everyday objects) tailored per locale.
- “Did you know” sections (seasonal or topical).
- Internal links to related units and calculators.

## Data model suggestion (conversion package)
- Keep compact structured fields per unit:
  - definition.summary.en/sv
  - definition.body.en/sv (short, 2–4 sentences max)
  - definition.source
  - equivalences: list of {to_unit, factor, display}
  - usage: list of tags (e.g., science, construction, daily)
- This stays small and stable, good for APIs and rendering.

## Data model suggestion (konvertera.nu WP app)
- Enrich using templates + external content layers:
  - family-level template paragraphs (length, area, mass, time).
  - locale overrides (sv/en) for usage and references.
  - curated examples (objects, human body, etc.) stored in WP for editorial tweaks.
  - auto-citation blocks for standards (BIPM/CGPM/NIST) with links.

## Auto-generated content ideas
- “1 {unit} equals” block with 3–5 most-used conversions.
- “Common conversions” list seeded by site analytics (top 5 targets for that unit).
- “Fractional equivalents” for imperial units (e.g., inches in 1 foot).
- “In other systems” block (metric/imperial/US customary/astronomical).
- “Order of magnitude” placement (e.g., micrometer vs millimeter).

## Semi-automated content ideas (template + curated data)
- Usage context paragraphs by unit family; inject unit name and symbol.
- Common references: store a small curated list per family + a few unit-specific entries.
- History/origin: per family with a short unit-specific sentence.
- Standards: auto insert when source matches BIPM/CGPM/NIST.

## SEO structure suggestion for unit pages
- H1: {Unit name} conversions
- Short intro: 2–3 sentences (definition + global use + 1 simple equivalence)
- “Quick facts” table: abbreviation, unit family, base unit, SI status.
- “Definition” block: from package data (short)
- “Common conversions” block: auto-generated
- “Usage context” block: family template + locale add-ons
- “Common references” block: curated items (WP)
- FAQ: 3–6 questions auto-generated (e.g., “How many cm in 1 inch?”)

## Quality and scale strategy
- Start with short, accurate core content from package data.
- Add richer editorial content in WP where it can be updated without releases.
- Use a content linter in WP to avoid repetition and ensure correctness.
- Track performance: which blocks drive engagement; iterate.

## Practical next steps
- Define a minimal per-unit schema in this repo (summary/body/source/equivalences/tags).
- Build a converter-page template in WP that merges package data + editorial blocks.
- Create a small curated examples dataset per family in WP for immediate value.
