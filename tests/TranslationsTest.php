<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll\Tests;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;

class TranslationsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        $locales = [];

        foreach (glob(dirname(__DIR__).'/resources/lang/*/infinite-scroll.php') ?: [] as $file) {
            $locale = basename(dirname($file));
            $locales[$locale] = [$locale];
        }

        return $locales;
    }

    public function test_all_ten_languages_ship(): void
    {
        $this->assertSame(
            ['de', 'en', 'es', 'fr', 'it', 'nl', 'pl', 'pt_BR', 'tr', 'uk'],
            array_keys(self::locales()),
        );
    }

    #[DataProvider('locales')]
    public function test_every_locale_has_the_english_keys_and_placeholders(string $locale): void
    {
        $english = $this->strings('en');
        $strings = $this->strings($locale);

        $this->assertSame([], array_values(array_diff(array_keys($english), array_keys($strings))), 'missing keys');
        $this->assertSame([], array_values(array_diff(array_keys($strings), array_keys($english))), 'extra keys');

        foreach ($english as $key => $text) {
            $this->assertNotSame('', trim($strings[$key]), $key);
            $this->assertSame($this->placeholders($text), $this->placeholders($strings[$key]), $key);
        }
    }

    #[DataProvider('locales')]
    public function test_the_translator_resolves_every_key(string $locale): void
    {
        app()->setLocale($locale);

        foreach (array_keys($this->strings('en')) as $key) {
            $this->assertNotSame(
                'filament-infinite-scroll::infinite-scroll.'.$key,
                __('filament-infinite-scroll::infinite-scroll.'.$key),
                $key,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function strings(string $locale): array
    {
        /** @var array<string, string> $strings */
        $strings = require dirname(__DIR__)."/resources/lang/{$locale}/infinite-scroll.php";

        return Arr::dot($strings);
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $text): array
    {
        preg_match_all('/:[a-z_]+/', $text, $matches);
        sort($matches[0]);

        return $matches[0];
    }
}
