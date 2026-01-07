<?php

declare(strict_types=1);

namespace Konvertera\Engine;

use Konvertera\Engine\Exceptions\UnitNotFound;

final class Category
{
    /** @var array<int, Unit> */
    private array $units;

    /** @var array<string, Unit> */
    private array $unitsByKey;

    /** @param array<int, Unit> $units */
    public function __construct(
        public readonly string $key,
        public readonly string $baseUnit,
        array $units,
        public readonly ?string $family = null,
        public readonly ?array $types = null
    ) {
        $this->units = $units;
        $this->unitsByKey = [];
        foreach ($units as $unit) {
            $this->unitsByKey[$unit->key] = $unit;
        }
    }

    /** @return array<int, Unit> */
    public function units(): array
    {
        return $this->units;
    }

    public function getUnit(string $key): Unit
    {
        if (!isset($this->unitsByKey[$key])) {
            throw new UnitNotFound("Unit not found: {$key}");
        }

        return $this->unitsByKey[$key];
    }
}
