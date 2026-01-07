<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class Transform
{
    public function __construct(
        public readonly string $kind,
        public readonly ?float $factor = null,
        public readonly ?float $offset = null,
        public readonly ?string $customKey = null
    ) {
    }
}
