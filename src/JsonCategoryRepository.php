<?php

declare(strict_types=1);

namespace Konvertera\Engine;

use Konvertera\Engine\Exceptions\CategoryNotFound;

final class JsonCategoryRepository implements CategoryRepositoryInterface
{
    private string $resourcesPath;

    /** @var array<string, Category> */
    private array $cache = [];

    public function __construct(?string $resourcesPath = null)
    {
        $this->resourcesPath = $resourcesPath ?? dirname(__DIR__) . '/resources';
    }

    public function listCategories(): array
    {
        $indexPath = $this->resourcesPath . '/index.json';
        if (is_file($indexPath)) {
            $data = $this->decodeJson($indexPath);
            return is_array($data['categories'] ?? null) ? $data['categories'] : [];
        }

        $files = glob($this->resourcesPath . '/categories/*.json') ?: [];
        $list = [];
        foreach ($files as $file) {
            $data = $this->decodeJson($file);
            if (isset($data['key'])) {
                $list[] = [
                    'key' => $data['key'],
                    'family' => $data['family'] ?? null,
                ];
            }
        }

        return $list;
    }

    public function getCategory(string $key): Category
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $path = $this->resourcesPath . '/categories/' . $key . '.json';
        if (!is_file($path)) {
            throw new CategoryNotFound("Category not found: {$key}");
        }

        $data = $this->decodeJson($path);
        $category = $this->mapCategory($data);
        $this->cache[$key] = $category;

        return $category;
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Failed to read JSON: {$path}");
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid JSON: {$path}");
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function mapCategory(array $data): Category
    {
        $this->validateCategory($data);
        $units = [];
        foreach (($data['units'] ?? []) as $unit) {
            $this->validateUnit($unit);
            $transformData = $unit['transform'] ?? [];
            $transform = new Transform(
                (string) ($transformData['kind'] ?? ''),
                isset($transformData['factor']) ? (float) $transformData['factor'] : null,
                isset($transformData['offset']) ? (float) $transformData['offset'] : null,
                isset($transformData['custom_key']) ? (string) $transformData['custom_key'] : null
            );

            $units[] = new Unit(
                (string) $unit['key'],
                (array) ($unit['name'] ?? []),
                (string) ($unit['abbr'] ?? ''),
                $transform,
                isset($unit['type']) ? (string) $unit['type'] : null,
                isset($unit['definition']) ? (array) $unit['definition'] : null
            );
        }

        return new Category(
            (string) $data['key'],
            (string) $data['base_unit'],
            $units,
            isset($data['family']) ? (string) $data['family'] : null,
            isset($data['types']) ? (array) $data['types'] : null
        );
    }

    /** @param array<string, mixed> $data */
    private function validateCategory(array $data): void
    {
        if (($data['schema_version'] ?? null) !== 1) {
            throw new \RuntimeException('Unsupported schema_version.');
        }

        if (!isset($data['key']) || !is_string($data['key'])) {
            throw new \RuntimeException('Category key missing or invalid.');
        }

        if (!isset($data['base_unit']) || !is_string($data['base_unit'])) {
            throw new \RuntimeException('Category base_unit missing or invalid.');
        }

        if (!isset($data['units']) || !is_array($data['units']) || count($data['units']) === 0) {
            throw new \RuntimeException('Category units missing or empty.');
        }

        $hasBaseUnit = false;
        foreach ($data['units'] as $unit) {
            if (is_array($unit) && ($unit['key'] ?? null) === $data['base_unit']) {
                $hasBaseUnit = true;
                break;
            }
        }

        if (!$hasBaseUnit) {
            throw new \RuntimeException('Category base_unit is not listed in units.');
        }
    }

    /** @param array<string, mixed> $unit */
    private function validateUnit(array $unit): void
    {
        if (!isset($unit['key']) || !is_string($unit['key'])) {
            throw new \RuntimeException('Unit key missing or invalid.');
        }

        if (!isset($unit['name']) || !is_array($unit['name']) || $unit['name'] === []) {
            throw new \RuntimeException('Unit name missing or invalid.');
        }

        if (!array_key_exists('abbr', $unit) || !is_string($unit['abbr'])) {
            throw new \RuntimeException('Unit abbr missing or invalid.');
        }

        if (!isset($unit['transform']) || !is_array($unit['transform'])) {
            throw new \RuntimeException('Unit transform missing or invalid.');
        }

        $kind = $unit['transform']['kind'] ?? null;
        if (!in_array($kind, ['linear', 'affine', 'reciprocal_factor', 'custom'], true)) {
            throw new \RuntimeException('Unit transform kind invalid.');
        }

        if ($kind === 'linear' || $kind === 'reciprocal_factor') {
            if (!isset($unit['transform']['factor']) || !is_numeric($unit['transform']['factor'])) {
                throw new \RuntimeException('Unit transform factor missing or invalid.');
            }
        }

        if ($kind === 'affine') {
            if (!isset($unit['transform']['factor']) || !is_numeric($unit['transform']['factor'])) {
                throw new \RuntimeException('Unit transform factor missing or invalid.');
            }
            if (!isset($unit['transform']['offset']) || !is_numeric($unit['transform']['offset'])) {
                throw new \RuntimeException('Unit transform offset missing or invalid.');
            }
        }

        if ($kind === 'custom') {
            if (!isset($unit['transform']['custom_key']) || !is_string($unit['transform']['custom_key'])) {
                throw new \RuntimeException('Unit transform custom_key missing or invalid.');
            }
        }
    }
}
