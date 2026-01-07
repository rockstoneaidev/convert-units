<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\FormatOptions;
use Konvertera\Engine\Formatter;
use PHPUnit\Framework\TestCase;

final class FormattingFixturesTest extends TestCase
{
    public function testFormattingFixtures(): void
    {
        $fixtures = $this->loadFixtures();
        $formatter = new Formatter();

        foreach ($fixtures as $case) {
            $opts = new FormatOptions();
            $opts->significantFigures = (int) $case['options']['sig'];
            $opts->scientificNotation = (bool) $case['options']['sci'];
            $opts->decimalSeparator = (string) $case['options']['sep'];

            $actual = $formatter->format((float) $case['value'], $opts);
            self::assertSame($case['expected'], $actual, 'Fixture failed: ' . json_encode($case));
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function loadFixtures(): array
    {
        $path = dirname(__DIR__) . '/fixtures/formatting.json';
        $raw = file_get_contents($path);
        if ($raw === false) {
            self::fail('Failed to read formatting fixtures.');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::fail('Invalid formatting fixtures JSON.');
        }

        return $data;
    }
}
