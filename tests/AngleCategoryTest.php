<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\JsonCategoryRepository;
use PHPUnit\Framework\TestCase;

/**
 * The angle category added on 2026-11-04. The site builds URLs from the pinned slugs, so
 * they are asserted here rather than left to the JSON alone.
 */
final class AngleCategoryTest extends TestCase
{
    private const UNITS = ['turn', 'degree', 'gradian', 'radian', 'arcminute', 'arcsecond', 'mil'];

    public function testAngleLoadsWithBaseUnitDegree(): void
    {
        $category = $this->repo()->getCategory('angle');

        self::assertSame('angle', $category->key);
        self::assertSame('degree', $category->baseUnit);
        self::assertCount(7, $category->units());
        self::assertSame(self::UNITS, array_map(static fn ($u) => $u->key, $category->units()));
    }

    public function testAngleIsLastInIndexWithFamilyDimension(): void
    {
        $list = $this->repo()->listCategories();
        $last = end($list);

        self::assertSame('angle', $last['key']);
        self::assertSame('dimension', $last['family']);
    }

    public function testPinnedSlugs(): void
    {
        $expected = [
            ['turn', 'varv', 'turn'],
            ['degree', 'grad', 'degree'],
            ['gradian', 'gon', 'gradian'],
            ['radian', 'radian', 'radian'],
            ['arcminute', 'bagminut', 'arcminute'],
            ['arcsecond', 'bagsekund', 'arcsecond'],
            ['mil', 'streck', 'mil'],
        ];

        $category = $this->repo()->getCategory('angle');
        foreach ($expected as [$key, $sv, $en]) {
            $unit = $category->getUnit($key);
            self::assertIsArray($unit->slugs, "{$key} must carry a slugs map");
            self::assertSame($sv, $unit->slugs['sv'] ?? null, "sv slug for {$key}");
            self::assertSame($en, $unit->slugs['en'] ?? null, "en slug for {$key}");
        }
    }

    /**
     * The site reads the category-level "slugs" map straight from the JSON and falls back to
     * the key when it is missing. Without it the sv category URLs became /sv/omvandla/angle/
     * and every sv unit and pair page 404'd.
     */
    public function testCategorySlugsArePinned(): void
    {
        $data = json_decode((string) file_get_contents(dirname(__DIR__) . '/resources/categories/angle.json'), true);

        self::assertSame(['en' => 'angle', 'sv' => 'vinkel'], $data['slugs'] ?? null);
    }

    public function testEveryCategoryFileCarriesSvAndEnSlugs(): void
    {
        foreach (glob(dirname(__DIR__) . '/resources/categories/*.json') ?: [] as $path) {
            $data = json_decode((string) file_get_contents($path), true);
            $name = basename($path);
            self::assertIsArray($data['slugs'] ?? null, "{$name} needs a category slugs map");
            self::assertNotEmpty($data['slugs']['sv'] ?? null, "{$name} sv slug");
            self::assertNotEmpty($data['slugs']['en'] ?? null, "{$name} en slug");
        }
    }

    public function testNoSlugContainsAnUnderscore(): void
    {
        foreach ($this->repo()->getCategory('angle')->units() as $unit) {
            foreach ((array) $unit->slugs as $locale => $slug) {
                self::assertStringNotContainsString('_', (string) $slug, "{$unit->key} {$locale}");
            }
        }
    }

    public function testEveryUnitHasADefinitionWithSummaryBodyAndSource(): void
    {
        foreach ($this->repo()->getCategory('angle')->units() as $unit) {
            $d = $unit->definition;
            self::assertIsArray($d, "{$unit->key} needs a definition");
            foreach (['en', 'sv'] as $locale) {
                self::assertNotEmpty($d['summary'][$locale] ?? null, "{$unit->key} summary {$locale}");
                self::assertNotEmpty($d['body'][$locale] ?? null, "{$unit->key} body {$locale}");
            }
            self::assertStringStartsWith('https://', (string) ($d['source'] ?? ''), "{$unit->key} source");
        }
    }

    public function testMilDefinitionDisambiguates(): void
    {
        $body = $this->repo()->getCategory('angle')->getUnit('mil')->definition['body'];

        self::assertStringContainsString('6,400', $body['en']);
        self::assertStringContainsString('6,300', $body['en']);
        self::assertStringContainsString('6,000', $body['en']);
        self::assertStringContainsString('6 400', $body['sv']);
        self::assertStringContainsString('6 300', $body['sv']);
        self::assertStringContainsString('6 000', $body['sv']);
    }

    /** @dataProvider roundTrips */
    public function testRoundTrip(string $from, float $value, string $to, float $expected, float $delta): void
    {
        $result = (new ConversionEngine($this->repo()))->convertAll('angle', $from, $value);

        self::assertEqualsWithDelta($expected, $result[$to], $delta);
    }

    /** @return array<string, array{0:string,1:float,2:string,3:float,4:float}> */
    public static function roundTrips(): array
    {
        return [
            'turn to degree' => ['turn', 1.0, 'degree', 360.0, 0.0],
            'turn to gradian' => ['turn', 1.0, 'gradian', 400.0, 1.0E-9],
            'turn to mil' => ['turn', 1.0, 'mil', 6400.0, 1.0E-9],
            'degree to arcminute' => ['degree', 1.0, 'arcminute', 60.0, 1.0E-9],
            'arcminute to arcsecond' => ['arcminute', 1.0, 'arcsecond', 60.0, 1.0E-9],
            '180 degree to radian' => ['degree', 180.0, 'radian', 3.14159265, 1.0E-8],
            'radian to degree' => ['radian', 1.0, 'degree', 57.29577951, 1.0E-8],
            'negative degree to radian' => ['degree', -90.0, 'radian', -1.57079633, 1.0E-8],
            '720 degree to turn' => ['degree', 720.0, 'turn', 2.0, 1.0E-12],
        ];
    }

    private function repo(): JsonCategoryRepository
    {
        return new JsonCategoryRepository(dirname(__DIR__) . '/resources');
    }
}
