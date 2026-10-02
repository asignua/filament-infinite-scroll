<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll\Tests;

use Asignua\FilamentInfiniteScroll\FilamentInfiniteScrollServiceProvider;
use PHPUnit\Framework\TestCase;

class ComposerDiscoveryTest extends TestCase
{
    public function test_service_provider_is_declared_for_laravel_package_discovery(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true);

        $this->assertContains(
            FilamentInfiniteScrollServiceProvider::class,
            $composer['extra']['laravel']['providers'] ?? [],
        );
    }

    public function test_stylesheet_hides_the_pager_that_is_a_sibling_of_the_footer_inside_fi_ta_main(): void
    {
        // Filament renders the footer (render hook) and the pager as siblings inside .fi-ta-main,
        // not directly inside .fi-ta-ctn: a selector on .fi-ta-ctn never matches in a real browser.
        $css = (string) file_get_contents(__DIR__.'/../resources/dist/filament-infinite-scroll.css');

        $this->assertStringContainsString('.fi-ta-main:has(> .fi-ta-infinite-scroll) > .fi-pagination', $css);
        $this->assertStringNotContainsString('.fi-ta-ctn:has(', $css);
    }
}
