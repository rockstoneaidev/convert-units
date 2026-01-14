<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\JsonCategoryRepository;
use PHPUnit\Framework\TestCase;

final class JsonCategoryRepositoryTest extends TestCase
{
    public function testListCategoriesFromIndex(): void
    {
        $repo = new JsonCategoryRepository(dirname(__DIR__) . '/resources');

        $categories = $repo->listCategories();

        self::assertNotEmpty($categories);
        self::assertSame('length', $categories[0]['key']);
    }

    public function testUnitSlugsAreLoaded(): void
    {
        $repo = new JsonCategoryRepository(dirname(__DIR__) . '/resources');

        $category = $repo->getCategory('fuel_consumption');
        $unit = $category->getUnit('liter_per_10km');

        self::assertIsArray($unit->slugs);
        self::assertSame('liter-per-mil', $unit->slugs['sv']);
    }
}
