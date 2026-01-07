<?php

declare(strict_types=1);

namespace Konvertera\Engine;

interface CategoryRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listCategories(): array;

    public function getCategory(string $key): Category;
}
