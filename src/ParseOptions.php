<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class ParseOptions
{
    public bool $acceptCommaDecimal = true;
    public bool $acceptDotDecimal = true;
    public bool $acceptExponent = true;
    public bool $trimSpaces = true;
    public bool $allowThousandsSep = false;
}
