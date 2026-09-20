<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\JsonCategoryRepository;
use PHPUnit\Framework\TestCase;

/**
 * The six categories added on 2026-09-20: force, torque, frequency, voltage,
 * resistance, capacitance.
 *
 * JsonCategoryRepositoryTest does NOT loop over resources/categories/*.json (it loads
 * `length` and `fuel_consumption` by name), so nothing else in this suite would notice
 * a malformed new file. This test loads all six, checks the pinned slugs the site
 * builds URLs from, and pins one round-trip per category.
 */
final class NewCategoriesTest extends TestCase
{
    private const NEW_CATEGORIES = [
        'force' => 'newton',
        'torque' => 'newton_meter',
        'frequency' => 'hertz',
        'voltage' => 'volt',
        'resistance' => 'ohm',
        'capacitance' => 'farad',
    ];

    public function testEveryNewCategoryLoadsWithItsBaseUnit(): void
    {
        $repo = $this->repo();

        foreach (self::NEW_CATEGORIES as $key => $baseUnit) {
            $category = $repo->getCategory($key);
            self::assertSame($key, $category->key, "category key for {$key}");
            self::assertSame($baseUnit, $category->baseUnit, "base unit for {$key}");
            self::assertNotEmpty($category->units(), "units for {$key}");
        }
    }

    public function testNewCategoriesAreListedInTheIndexAfterArea(): void
    {
        $repo = $this->repo();
        $keys = array_column($repo->listCategories(), 'key');

        $areaAt = array_search('area', $keys, true);
        self::assertIsInt($areaAt, 'area must still be in the index');

        $tail = array_slice($keys, $areaAt + 1);
        self::assertSame(array_keys(self::NEW_CATEGORIES), $tail, 'the six new categories follow area, in build order');
    }

    public function testPinnedUnitSlugs(): void
    {
        $repo = $this->repo();

        $expected = [
            ['force', 'tonne_force', 'tonkraft', 'tonne-force'],
            ['force', 'kilopond', 'kilopond', 'kilogram-force'],
            ['force', 'pound_force', 'pundkraft', 'pound-force'],
            // Torque pins every slug: the Swedish URLs follow the Swedish compound names
            // rather than the English key spelling (owner decision, 2026-09-20).
            ['torque', 'kilonewton_meter', 'kilonewtonmeter', 'kilonewton-meter'],
            ['torque', 'kilopond_meter', 'kilopondmeter', 'kilopond-meter'],
            ['torque', 'newton_meter', 'newtonmeter', 'newton-meter'],
            ['torque', 'newton_centimeter', 'newtoncentimeter', 'newton-centimeter'],
            ['torque', 'millinewton_meter', 'millinewtonmeter', 'millinewton-meter'],
            ['torque', 'pound_foot', 'pund-fot', 'pound-foot'],
            ['torque', 'pound_inch', 'pund-tum', 'pound-inch'],
            ['torque', 'ounce_inch', 'uns-tum', 'ounce-inch'],
            ['frequency', 'revolution_per_minute', 'varv-per-minut', 'rpm'],
            ['frequency', 'revolution_per_second', 'varv-per-sekund', 'revolution-per-second'],
            ['voltage', 'microvolt', 'mikrovolt', 'microvolt'],
            ['resistance', 'microohm', 'mikroohm', 'microohm'],
            ['capacitance', 'microfarad', 'mikrofarad', 'microfarad'],
            ['capacitance', 'picofarad', 'pikofarad', 'picofarad'],
        ];

        foreach ($expected as [$category, $unitKey, $sv, $en]) {
            $unit = $repo->getCategory($category)->getUnit($unitKey);
            self::assertIsArray($unit->slugs, "{$unitKey} must carry a slugs map");
            self::assertSame($sv, $unit->slugs['sv'] ?? null, "sv slug for {$unitKey}");
            self::assertSame($en, $unit->slugs['en'] ?? null, "en slug for {$unitKey}");
        }
    }

    public function testNoSlugContainsAnUnderscore(): void
    {
        $repo = $this->repo();

        foreach (array_keys(self::NEW_CATEGORIES) as $key) {
            foreach ($repo->getCategory($key)->units() as $unit) {
                $slugs = is_array($unit->slugs) ? $unit->slugs : ['sv' => str_replace('_', '-', $unit->key)];
                foreach ($slugs as $locale => $slug) {
                    self::assertStringNotContainsString('_', (string) $slug, "{$key}/{$unit->key} {$locale}");
                }
            }
        }
    }

    /** @dataProvider roundTrips */
    public function testRoundTrip(string $category, string $from, float $value, string $to, float $expected, float $delta): void
    {
        $engine = new ConversionEngine($this->repo());

        $result = $engine->convertAll($category, $from, $value);

        self::assertEqualsWithDelta($expected, $result[$to], $delta);
    }

    /** @return array<string, array{0:string,1:string,2:float,3:string,4:float,5:float}> */
    public static function roundTrips(): array
    {
        return [
            'kilopond to newton' => ['force', 'kilopond', 1.0, 'newton', 9.80665, 1.0E-12],
            'kilopond_meter to newton_meter' => ['torque', 'kilopond_meter', 1.0, 'newton_meter', 9.80665, 1.0E-12],
            'newton_meter to pound_foot' => ['torque', 'newton_meter', 10.0, 'pound_foot', 7.3756, 1.0E-4],
            'rpm to hertz' => ['frequency', 'revolution_per_minute', 3000.0, 'hertz', 50.0, 0.0],
            'kilovolt to volt' => ['voltage', 'kilovolt', 1.0, 'volt', 1000.0, 1.0E-12],
            'megaohm to ohm' => ['resistance', 'megaohm', 1.0, 'ohm', 1000000.0, 1.0E-9],
            'microfarad to nanofarad' => ['capacitance', 'microfarad', 1.0, 'nanofarad', 1000.0, 1.0E-9],
        ];
    }

    public function testMassNoLongerCarriesKilonewton(): void
    {
        $keys = array_map(
            static fn ($unit) => $unit->key,
            $this->repo()->getCategory('mass')->units()
        );

        self::assertNotContains('kilonewton', $keys, 'kilonewton is a force, and moved to the force category');
    }

    private function repo(): JsonCategoryRepository
    {
        return new JsonCategoryRepository(dirname(__DIR__) . '/resources');
    }
}
