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
}
