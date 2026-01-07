<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class Formatter
{
    public function format(float $value, FormatOptions $opts): string
    {
        throw new \LogicException('Formatter not implemented. See docs/app-prd.md.');
    }

    /** @param array<string, float> $valuesByUnit */
    public function formatAll(array $valuesByUnit, FormatOptions $opts): array
    {
        $out = [];
        foreach ($valuesByUnit as $unit => $value) {
            $out[$unit] = $this->format($value, $opts);
        }

        return $out;
    }
}
