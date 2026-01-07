<?php

declare(strict_types=1);

namespace Konvertera\Engine;

use Konvertera\Engine\Exceptions\InvalidNumber;

final class ValueParser
{
    public function parse(string $raw, ParseOptions $opts): float
    {
        $value = $opts->trimSpaces ? trim($raw) : $raw;
        if ($value === '') {
            throw new InvalidNumber('Empty input.');
        }

        if (!$opts->acceptCommaDecimal && str_contains($value, ',')) {
            throw new InvalidNumber('Comma decimals not allowed.');
        }

        if (!$opts->acceptDotDecimal && str_contains($value, '.')) {
            throw new InvalidNumber('Dot decimals not allowed.');
        }

        if (!$opts->acceptExponent && (str_contains($value, 'e') || str_contains($value, 'E'))) {
            throw new InvalidNumber('Exponent notation not allowed.');
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            throw new InvalidNumber('Ambiguous decimal separators.');
        }

        if (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        $pattern = $opts->acceptExponent
            ? '/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)(?:[eE][+-]?\d+)?$/'
            : '/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/';

        if (!preg_match($pattern, $value)) {
            throw new InvalidNumber('Invalid number format.');
        }

        $parsed = (float) $value;
        if (!is_finite($parsed)) {
            throw new InvalidNumber('Non-finite number.');
        }

        return $parsed;
    }
}
