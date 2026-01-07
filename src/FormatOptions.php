<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class FormatOptions
{
    public int $significantFigures = 3;
    public bool $scientificNotation = false;
    public string $decimalSeparator = '.';
}
