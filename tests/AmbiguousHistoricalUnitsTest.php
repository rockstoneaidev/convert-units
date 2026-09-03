<?php

declare(strict_types=1);

namespace Konvertera\Engine\Tests;

use PHPUnit\Framework\TestCase;

/**
 * A historical unit whose Swedish-facing name or abbreviation is ALSO claimed by a modern unit
 * in the same category must carry a `definition`.
 *
 * This is not a documentation nicety. Twice now, a missing definition on such a unit has led to
 * a live wrong answer on konvertera.nu, because nothing in the data says which unit the page
 * models:
 *
 *   - `steg` (0.891 m, half a famn) was read as a pedometer step and nearly "corrected"; a
 *     demand-shaped conversion table shipped and was reverted the same day. Fixed in #13.
 *   - `swedish_fot` (0.296901 m) owns the bare abbreviation `fot` while the international
 *     `foot` (0.3048 m) is filed under `ft`. /sv/omvandla/langd/svensk-fot/ was therefore
 *     answering ordinary "fot till meter" demand 2.6% wrong.
 *
 * Scope is deliberately narrow: only `type: "swedish"` units that COLLIDE with a non-Swedish
 * unit in the same category. Plenty of historical units have no definition yet (famn, aln, lod,
 * …), and that is fine — nobody confuses them with a modern unit. The collision is what makes a
 * definition load-bearing.
 *
 * Note: comparison is case-INSENSITIVE on purpose, because these tokens reach readers as prose.
 * Do not widen it to all units — `MW` (megawatt) and `mW` (milliwatt) are a legitimate pair whose
 * case is the whole distinction.
 */
final class AmbiguousHistoricalUnitsTest extends TestCase
{
    /** @return list<array{0:string,1:string,2:string,3:string}> */
    public static function collidingSwedishUnits(): array
    {
        $cases = [];
        foreach (glob(dirname(__DIR__) . '/resources/categories/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            $units = $data['units'] ?? [];
            $rows = [];
            foreach ($units as $key => $unit) {
                if (is_array($unit)) {
                    $rows[is_string($key) ? $key : (string) ($unit['key'] ?? '')] = $unit;
                }
            }

            $tokens = static function (array $unit): array {
                $out = [];
                foreach ([$unit['name']['sv'] ?? '', $unit['abbr'] ?? ''] as $token) {
                    $token = mb_strtolower(trim((string) $token));
                    if ($token !== '') {
                        $out[$token] = true;
                    }
                }

                return $out;
            };

            foreach ($rows as $key => $unit) {
                if (($unit['type'] ?? '') !== 'swedish') {
                    continue;
                }
                foreach ($rows as $other_key => $other) {
                    if ($other_key === $key || ($other['type'] ?? '') === 'swedish') {
                        continue;
                    }
                    $shared = array_intersect_key($tokens($unit), $tokens($other));
                    if ($shared !== []) {
                        $cases[] = [basename($file), $key, $other_key, (string) array_key_first($shared)];
                    }
                }
            }
        }

        return $cases;
    }

    /**
     * @dataProvider collidingSwedishUnits
     */
    public function testACollidingHistoricalUnitCarriesADefinition(
        string $file,
        string $key,
        string $collidesWith,
        string $token
    ): void {
        $data = json_decode((string) file_get_contents(dirname(__DIR__) . '/resources/categories/' . $file), true);
        $units = $data['units'] ?? [];

        $unit = null;
        foreach ($units as $k => $candidate) {
            if ($k === $key || ($candidate['key'] ?? null) === $key) {
                $unit = $candidate;
                break;
            }
        }

        self::assertIsArray($unit, "{$file}: unit {$key} not found");
        self::assertNotEmpty(
            $unit['definition'] ?? null,
            "{$file}: '{$key}' shares the token '{$token}' with the modern unit '{$collidesWith}' but has no "
            . 'definition — that is exactly how a historical unit ends up silently answering modern queries'
        );
        self::assertNotEmpty($unit['definition']['summary']['sv'] ?? null, "{$file}: {$key} definition needs a Swedish summary");
        self::assertNotEmpty($unit['definition']['summary']['en'] ?? null, "{$file}: {$key} definition needs an English summary");
    }

    public function testTheGuardActuallyFindsTheKnownCollisions(): void
    {
        $found = array_map(static fn (array $c): string => $c[1], self::collidingSwedishUnits());

        // If this shrinks, the provider has stopped looking rather than the data having improved.
        self::assertContains('swedish_fot', $found, 'swedish_fot/foot collision no longer detected');
        self::assertContains('tum', $found, 'tum/inch collision no longer detected');
    }
}
