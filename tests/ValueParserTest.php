<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\Exceptions\InvalidNumber;
use Konvertera\Engine\ParseOptions;
use Konvertera\Engine\ValueParser;
use PHPUnit\Framework\TestCase;

final class ValueParserTest extends TestCase
{
    public function testParsesDotDecimal(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();

        self::assertSame(12.5, $parser->parse('12.5', $opts));
    }

    public function testParsesCommaDecimal(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();

        self::assertSame(12.5, $parser->parse('12,5', $opts));
    }

    public function testParsesExponent(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();

        self::assertSame(0.001, $parser->parse('1e-3', $opts));
    }

    public function testRejectsEmptyInput(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();

        $this->expectException(InvalidNumber::class);
        $parser->parse('   ', $opts);
    }

    public function testRejectsMixedSeparators(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();

        $this->expectException(InvalidNumber::class);
        $parser->parse('1,2.3', $opts);
    }

    public function testRejectsExponentWhenDisabled(): void
    {
        $parser = new ValueParser();
        $opts = new ParseOptions();
        $opts->acceptExponent = false;

        $this->expectException(InvalidNumber::class);
        $parser->parse('1e3', $opts);
    }
}
