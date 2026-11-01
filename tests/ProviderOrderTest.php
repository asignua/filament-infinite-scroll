<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll\Tests;

use Asignua\FilamentInfiniteScroll\Livewire\InfiniteScrollHook;
use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use ReflectionProperty;
use Workbench\App\Filament\Resources\Posts\Pages\ListPosts;
use Workbench\App\Models\Post;

/**
 * Livewire's provider boots BEFORE ours here (see TestCase::getPackageProviders). The hook registry is
 * static and survives between tests, so it is emptied first: only a registration made before Livewire's
 * boot() can then put the hook back.
 */
class ProviderOrderTest extends TestCase
{
    protected function setUp(): void
    {
        $hooks = new ReflectionProperty(ComponentHookRegistry::class, 'componentHooks');
        $hooks->setValue(null, []);

        parent::setUp();
    }

    public function test_load_more_works_when_livewire_boots_before_the_plugin(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Post::create(['team_id' => 1, 'title' => "post {$i}", 'status' => 'draft']);
        }

        $hooks = new ReflectionProperty(ComponentHookRegistry::class, 'componentHooks');
        $this->assertContains(InfiniteScrollHook::class, $hooks->getValue());

        Livewire::test(ListPosts::class)
            ->call(InfiniteScrollHook::METHOD)
            ->assertSee('All 15 records are loaded');
    }
}
