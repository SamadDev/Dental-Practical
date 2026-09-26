<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The SPA ships three complete translations (en / ku / ar). A key added to only
 * one of them silently falls back to English (or shows the raw key) at runtime,
 * which is invisible in code review — so parity is asserted here instead.
 *
 * Mirrors the one-off audit script that found 792/792/792 keys in parity.
 */
class LocaleParityTest extends TestCase
{
    private const REFERENCE = 'en';

    private const LOCALES = ['en', 'ku', 'ar'];

    public function test_all_locales_declare_exactly_the_same_keys(): void
    {
        $reference = $this->flatten($this->load(self::REFERENCE));

        foreach ($this->otherLocales() as $locale) {
            $flat = $this->flatten($this->load($locale));

            $this->assertSame(
                [],
                array_keys(array_diff_key($reference, $flat)),
                "{$locale}.json is missing keys that exist in " . self::REFERENCE . '.json: '
                    . implode(', ', array_keys(array_diff_key($reference, $flat)))
            );

            $this->assertSame(
                [],
                array_keys(array_diff_key($flat, $reference)),
                "{$locale}.json has keys that " . self::REFERENCE . '.json does not: '
                    . implode(', ', array_keys(array_diff_key($flat, $reference)))
            );
        }
    }

    public function test_placeholder_tokens_match_across_locales(): void
    {
        $reference = $this->flatten($this->load(self::REFERENCE));

        foreach ($this->otherLocales() as $locale) {
            foreach ($this->flatten($this->load($locale)) as $key => $value) {
                if (! array_key_exists($key, $reference)) {
                    continue;
                }

                $expected = $this->placeholders($reference[$key]);
                $actual   = $this->placeholders($value);

                sort($expected);
                sort($actual);

                $this->assertSame(
                    $expected,
                    $actual,
                    "Placeholder mismatch for '{$key}': {$locale} must use the same {{tokens}} as "
                        . self::REFERENCE . ' (interpolation would break at runtime).'
                );
            }
        }
    }

    /** @return list<string> */
    private function otherLocales(): array
    {
        return array_values(array_filter(self::LOCALES, fn (string $l) => $l !== self::REFERENCE));
    }

    /** @return list<string> */
    private function placeholders(string $value): array
    {
        preg_match_all('/\{[a-zA-Z0-9_]+\}/', $value, $matches);

        return $matches[0];
    }

    private function load(string $locale): array
    {
        $path = dirname(__DIR__, 3) . '/frontend/src/locales/' . $locale . '.json';

        $this->assertFileExists($path, "Locale file not found: {$path}");

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $full);
            } else {
                $flat[$full] = (string) $value;
            }
        }

        return $flat;
    }
}
