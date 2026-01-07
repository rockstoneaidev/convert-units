<?php

declare(strict_types=1);

namespace Konvertera\Engine;

use Konvertera\Engine\Exceptions\DivisionByZero;

final class ConversionEngine
{
    public function __construct(private CategoryRepositoryInterface $repo)
    {
    }

    /**
     * @return array<string, float>
     */
    public function convertAll(string $categoryKey, string $fromUnitKey, float $value): array
    {
        $category = $this->repo->getCategory($categoryKey);
        $fromUnit = $category->getUnit($fromUnitKey);

        $baseValue = $this->toBase($fromUnit->transform, $value);
        $results = [];

        foreach ($category->units() as $unit) {
            $results[$unit->key] = $this->fromBase($unit->transform, $baseValue);
        }

        return $results;
    }

    private function toBase(Transform $transform, float $value): float
    {
        return match ($transform->kind) {
            'linear' => $value * (float) $transform->factor,
            'affine' => $value * (float) $transform->factor + (float) $transform->offset,
            'reciprocal_factor' => $this->reciprocalToBase($transform, $value),
            default => throw new \RuntimeException("Unsupported transform kind: {$transform->kind}"),
        };
    }

    private function fromBase(Transform $transform, float $base): float
    {
        return match ($transform->kind) {
            'linear' => $base / (float) $transform->factor,
            'affine' => ($base - (float) $transform->offset) / (float) $transform->factor,
            'reciprocal_factor' => $this->reciprocalFromBase($transform, $base),
            default => throw new \RuntimeException("Unsupported transform kind: {$transform->kind}"),
        };
    }

    private function reciprocalToBase(Transform $transform, float $value): float
    {
        if ($value == 0.0) {
            throw new DivisionByZero('Division by zero in reciprocal transform.');
        }

        return (float) $transform->factor / $value;
    }

    private function reciprocalFromBase(Transform $transform, float $base): float
    {
        if ($base == 0.0) {
            throw new DivisionByZero('Division by zero in reciprocal transform.');
        }

        return (float) $transform->factor / $base;
    }
}
