<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use Konvertera\Engine\ConversionEngine;
use Konvertera\Engine\Exceptions\DivisionByZero;
use Konvertera\Engine\JsonCategoryRepository;
use PHPUnit\Framework\TestCase;

final class ConversionEngineTest extends TestCase
{
    public function testConvertsLengthLinear(): void
    {
        $engine = $this->makeEngine();

        $result = $engine->convertAll('length', 'meter', 1.0);

        $this->assertFloatEquals(1.0, $result['meter']);
        $this->assertFloatEquals(100.0, $result['centimeter']);
        $this->assertFloatEquals(3.280839895013123, $result['foot']);
    }

    public function testConvertsTemperatureAffine(): void
    {
        $engine = $this->makeEngine();

        $result = $engine->convertAll('temperature', 'celsius', 0.0);

        $this->assertFloatEquals(273.15, $result['kelvin']);
        $this->assertFloatEquals(32.0, $result['fahrenheit']);
    }

    public function testConvertsFuelConsumptionReciprocal(): void
    {
        $engine = $this->makeEngine();

        $result = $engine->convertAll('fuel_consumption', 'kilometer_per_liter', 10.0);

        $this->assertFloatEquals(0.1, $result['liter_per_kilometer']);
        $this->assertFloatEquals(10.0, $result['kilometer_per_liter']);
    }

    public function testReciprocalDivisionByZero(): void
    {
        $engine = $this->makeEngine();

        $this->expectException(DivisionByZero::class);
        $engine->convertAll('fuel_consumption', 'kilometer_per_liter', 0.0);
    }

    private function makeEngine(): ConversionEngine
    {
        $repo = new JsonCategoryRepository(dirname(__DIR__) . '/resources');

        return new ConversionEngine($repo);
    }

    private function assertFloatEquals(float $expected, float $actual, float $delta = 1.0E-12): void
    {
        self::assertEqualsWithDelta($expected, $actual, $delta);
    }
}
