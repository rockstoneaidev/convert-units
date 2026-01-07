<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\FormatOptions;
use Konvertera\Engine\Formatter;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    public function testFormatsZero(): void
    {
        $formatter = new Formatter();
        $opts = new FormatOptions();
        $opts->significantFigures = 5;

        self::assertSame('0', $formatter->format(0.0, $opts));
    }

    public function testFormatsWithSignificantFigures(): void
    {
        $formatter = new Formatter();
        $opts = new FormatOptions();
        $opts->significantFigures = 4;

        self::assertSame('1.230', $formatter->format(1.23, $opts));
        self::assertSame('1230', $formatter->format(1234.0, $opts));
    }

    public function testFormatsWithCommaSeparator(): void
    {
        $formatter = new Formatter();
        $opts = new FormatOptions();
        $opts->significantFigures = 3;
        $opts->decimalSeparator = ',';

        self::assertSame('12,3', $formatter->format(12.34, $opts));
    }

    public function testFormatsScientificNotation(): void
    {
        $formatter = new Formatter();
        $opts = new FormatOptions();
        $opts->significantFigures = 3;
        $opts->scientificNotation = true;

        self::assertSame('1.23e+04', $formatter->format(12345.0, $opts));
    }
}
