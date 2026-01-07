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
        $units = [];
        foreach (($data['units'] ?? []) as $unit) {
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
                isset($unit['help']) ? (array) $unit['help'] : null
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
}
