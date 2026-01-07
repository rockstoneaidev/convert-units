<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class Formatter
{
    public function format(float $value, FormatOptions $opts): string
    {
        if (!is_finite($value)) {
            throw new \DomainException('Cannot format non-finite number.');
        }

        if ($value == 0.0) {
            return '0';
        }

        $sig = max(1, min(7, $opts->significantFigures));
        $rounded = $this->roundToSigFigs($value, $sig);

        if (!is_finite($rounded)) {
            throw new \DomainException('Cannot format non-finite number.');
        }

        if ($opts->scientificNotation) {
            $formatted = sprintf('%.' . ($sig - 1) . 'e', $rounded);
            return $this->applyDecimalSeparator($formatted, $opts->decimalSeparator);
        }

        $abs = abs($rounded);
        if ($abs == 0.0) {
            return '0';
        }

        $exp = (int) floor(log10($abs));
        $scale = $sig - 1 - $exp;
        $decimals = max(0, $scale);

        $formatted = number_format($rounded, $decimals, '.', '');

        return $this->applyDecimalSeparator($formatted, $opts->decimalSeparator);
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

    private function roundToSigFigs(float $value, int $sig): float
    {
        if ($value == 0.0) {
            return 0.0;
        }

        $abs = abs($value);
        $exp = (int) floor(log10($abs));
        $scale = $sig - 1 - $exp;

        return round($value, $scale);
    }

    private function applyDecimalSeparator(string $value, string $separator): string
    {
        if ($separator === ',') {
            return str_replace('.', ',', $value);
        }

        return $value;
    }
}
