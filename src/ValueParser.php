<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class ValueParser
{
    public function parse(string $raw, ParseOptions $opts): float
    {
        throw new \LogicException('ValueParser not implemented. See docs/app-prd.md.');
    }
}
