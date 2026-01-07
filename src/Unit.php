<?php

declare(strict_types=1);

namespace Konvertera\Engine;

final class Unit
{
    /** @param array<string, string> $name */
    /** @param array<string, string>|null $help */
    public function __construct(
        public readonly string $key,
        public readonly array $name,
        public readonly string $abbr,
        public readonly Transform $transform,
        public readonly ?string $type = null,
        public readonly ?array $help = null
    ) {
    }
}
