<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll\Tests;

use Asignua\FilamentInfiniteScroll\InfiniteScroll;
use Asignua\FilamentInfiniteScroll\InfiniteScrollConfig;
use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
use Asignua\FilamentInfiniteScroll\InfiniteScrollRegistry;
use Asignua\FilamentInfiniteScroll\Livewire\InfiniteScrollHook;
use Illuminate\Support\Number;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use ValueError;
use Workbench\App\Filament\Resources\Posts\Pages\ListPosts;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
use Workbench\App\Filament\Resources\Teams\Pages\ListTeams;
use Workbench\App\Filament\Resources\Teams\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Widgets\AllPostsWidget;
use Workbench\App\Filament\Widgets\LatestPostsWidget;
use Workbench\App\Models\Post;
use Workbench\App\Models\Team;

class InfiniteScrollTest extends TestCase
{
    private function posts(int $count, string $status = 'draft', int $teamId = 1): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Post::create(['team_id' => $teamId, 'title' => "{$status} post {$i}", 'status' => $status]);
        }
    }

    private function loaded(mixed $component): int
    {
        return $component->instance()->getTableRecords()->count();
    }

    public function test_the_first_chunk_is_rendered_with_a_scroll_sentinel(): void
    {
        $this->posts(25);

        $component = Livewire::test(ListPosts::class);

        $this->assertSame(10, $this->loaded($component));
        $component->assertSee('Showing 10 of 25');
        $component->assertSeeHtml('x-intersect.margin.300px="load()"');
        $component->assertSeeHtml('data-mode="scroll"');
        $component->assertSeeHtml('data-loaded="10"');
        $component->assertSeeHtml('data-has-more="1"');
    }

    public function test_load_more_grows_the_list_one_chunk_at_a_time(): void
    {
        $this->posts(25);

        $component = Livewire::test(ListPosts::class)
            ->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));
        $component->assertSee('Showing 20 of 25');

        $component->call(InfiniteScrollHook::METHOD);

        $this->assertSame(25, $this->loaded($component));
    }

    public function test_the_all_loaded_state_removes_the_sentinel(): void
    {
        $this->posts(15);

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $component->assertSee('All 15 records are loaded');
        $component->assertDontSeeHtml('x-intersect');
        // The scroll loop reads this after its request and stops.
        $component->assertSeeHtml('data-has-more="0"');
    }

    public function test_a_table_that_fits_in_one_chunk_shows_no_footer(): void
    {
        $this->posts(6);

        Livewire::test(ListPosts::class)
            ->assertDontSeeHtml('class="fi-ta-infinite-scroll"')
            ->assertDontSeeHtml('fi-ta-infinite-scroll-status')
            ->assertDontSeeHtml('x-intersect')
            // An empty marker still lets the stylesheet hide Filament's own pager.
            ->assertSeeHtml('fi-ta-infinite-scroll-marker');
    }

    public function test_the_ceiling_is_respected_and_announced(): void
    {
        $this->posts(60);

        $component = Livewire::test(ListPosts::class);

        foreach (range(1, 8) as $ignored) {
            $component->call(InfiniteScrollHook::METHOD);
        }

        $this->assertSame(35, $this->loaded($component));
        $component->assertSee('Showing the first 35 of 60 records');
        $component->assertDontSeeHtml('x-intersect');
    }

    public function test_a_tampered_page_size_is_clamped(): void
    {
        $this->posts(60);

        $component = Livewire::test(ListPosts::class)->set('tableRecordsPerPage', 100000);

        // Only `infiniteScrollLoadMore` grows the list: a raised size falls back to the issued one.
        $this->assertSame(10, $this->loaded($component));

        $component->call(InfiniteScrollHook::METHOD)->set('tableRecordsPerPage', 15);

        // Lowering is allowed, but always to a whole number of chunks.
        $this->assertSame(10, $this->loaded($component));

        $component->set('tableRecordsPerPage', 'all');

        $this->assertSame(10, $this->loaded($component));
    }

    public function test_a_tampered_page_size_is_clamped_without_a_ceiling(): void
    {
        $this->posts(30);

        $component = Livewire::test(AllPostsWidget::class);

        $this->assertSame(5, $this->loaded($component));

        $component->set('tableRecordsPerPage', 100000000);

        $this->assertSame(5, $this->loaded($component));
        $this->assertSame(5, $component->get('tableRecordsPerPage'));

        $component->call(InfiniteScrollHook::METHOD)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(15, $this->loaded($component));

        $component->set('tableRecordsPerPage', 29);

        $this->assertSame(15, $this->loaded($component));
    }

    public function test_searching_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);
        $this->posts(30, 'published');

        $component = Livewire::test(ListPosts::class)
            ->call(InfiniteScrollHook::METHOD)
            ->call(InfiniteScrollHook::METHOD);

        $this->assertSame(30, $this->loaded($component));

        $component->searchTable('published');

        $this->assertSame(10, $this->loaded($component));
        $this->assertSame(10, $component->get('tableRecordsPerPage'));
    }

    public function test_filtering_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);
        $this->posts(30, 'published');

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));

        $component->filterTable('status', 'published');

        $this->assertSame(10, $this->loaded($component));

        $component->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));

        $component->removeTableFilter('status');

        $this->assertSame(10, $this->loaded($component));
    }

    public function test_sorting_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $component->sortTable('title');

        $this->assertSame(10, $this->loaded($component));
    }

    public function test_column_search_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);
        $this->posts(30, 'published');

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));

        $component->set('tableColumnSearches.title', 'published');

        $this->assertSame(10, $this->loaded($component));
        $this->assertSame(10, $component->get('tableRecordsPerPage'));
    }

    public function test_grouping_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));

        $component->set('tableGrouping', 'status');

        $this->assertSame(10, $this->loaded($component));
        $this->assertSame(10, $component->get('tableRecordsPerPage'));
    }

    public function test_switching_tabs_starts_over_from_the_first_chunk(): void
    {
        $this->posts(30);
        $this->posts(30, 'published');

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(20, $this->loaded($component));

        $component->set('activeTab', 'published');

        $this->assertSame(10, $this->loaded($component));
        $this->assertSame(10, $component->get('tableRecordsPerPage'));
    }

    public function test_a_query_change_made_in_code_drops_rows_read_before_it(): void
    {
        $this->posts(30);
        $this->posts(30, 'published');

        $component = Livewire::test(ListPosts::class)
            ->call(InfiniteScrollHook::METHOD)
            ->call('searchFromCode');

        $this->assertSame(10, $this->loaded($component));
        $component->assertSee('Showing 10 of 30');
    }

    public function test_counts_are_formatted_for_the_current_locale(): void
    {
        Post::insert(array_map(
            fn (int $i): array => ['team_id' => 1, 'title' => "post {$i}", 'status' => 'draft'],
            range(1, 1234),
        ));

        app()->setLocale('uk');

        $formatted = Number::format(1234, locale: 'uk');

        $this->assertNotSame('1,234', $formatted);

        $key = 'filament-infinite-scroll::infinite-scroll.showing';

        // Filament's own (hidden) pager formats its numbers by itself: look at the footer only.
        Livewire::test(ListPosts::class)
            ->assertSee(__($key, ['loaded' => 10, 'total' => $formatted]))
            ->assertDontSee(__($key, ['loaded' => 10, 'total' => '1,234']));
    }

    public function test_an_unknown_mode_is_rejected(): void
    {
        $this->expectException(ValueError::class);

        InfiniteScroll::configure(Livewire::test(ListTeams::class)->instance()->getTable(), mode: 'pages');
    }

    public function test_a_stale_page_parameter_is_ignored(): void
    {
        $this->posts(30);

        $component = Livewire::withQueryParams(['page' => 3])->test(ListPosts::class);

        $this->assertSame(10, $this->loaded($component));
        $component->assertSee('post 1');
    }

    public function test_bulk_actions_work_on_the_loaded_rows(): void
    {
        $this->posts(25);

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);
        $records = $component->instance()->getTableRecords();

        $component->callTableBulkAction('publish', $records->take(15));

        $this->assertSame(15, Post::query()->where('status', 'published')->count());
        $this->assertSame(20, $this->loaded($component));
    }

    public function test_selecting_all_loaded_rows_and_deleting_them(): void
    {
        $this->posts(25);

        $component = Livewire::test(ListPosts::class)->call(InfiniteScrollHook::METHOD);

        $keys = $component->instance()->getTableRecords()->pluck('id')->all();

        $component->callTableBulkAction('delete', $keys);

        $this->assertSame(5, Post::query()->count());
        $component->assertSee('post 21');
    }

    public function test_the_native_pager_is_the_default_for_other_tables(): void
    {
        Team::create(['name' => 'Alpha']);

        Livewire::test(ListTeams::class)
            ->assertDontSeeHtml('fi-ta-infinite-scroll')
            ->assertDontSeeHtml('x-intersect');
    }

    public function test_relation_manager_in_button_mode(): void
    {
        $team = Team::create(['name' => 'Core']);
        $this->posts(12, teamId: $team->id);
        $this->posts(3, teamId: 999);

        $component = Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => EditTeam::class,
        ]);

        $this->assertSame(5, $this->loaded($component));
        $component->assertSeeHtml('data-mode="button"');
        $component->assertDontSeeHtml('x-intersect');
        $component->assertSee(__('filament-infinite-scroll::infinite-scroll.load_more'));
        $component->assertSee('Showing 5 of 12');

        $component->call(InfiniteScrollHook::METHOD)->call(InfiniteScrollHook::METHOD);

        $this->assertSame(12, $this->loaded($component));
        $component->assertDontSee(__('filament-infinite-scroll::infinite-scroll.load_more'));
        $component->assertSee('All 12 records are loaded');

        $component->searchTable('draft post 3');

        $this->assertSame(5, $component->get('tableRecordsPerPage'));
    }

    public function test_table_widget(): void
    {
        $this->posts(30);

        $component = Livewire::test(LatestPostsWidget::class);

        $this->assertSame(4, $this->loaded($component));

        foreach (range(1, 5) as $ignored) {
            $component->call(InfiniteScrollHook::METHOD);
        }

        $this->assertSame(12, $this->loaded($component));
        $component->assertSee('Showing the first 12 of 30 records');
    }

    public function test_an_unknown_component_is_not_affected(): void
    {
        $this->expectException(MethodNotFoundException::class);

        Livewire::test(ListTeams::class)->call(InfiniteScrollHook::METHOD);
    }

    public function test_config_clamps_between_one_chunk_and_the_ceiling(): void
    {
        $config = new InfiniteScrollConfig(10, InfiniteScrollMode::Scroll, 35, 300);

        $this->assertSame(10, $config->clamp(0));
        $this->assertSame(20, $config->next(10));
        $this->assertSame(35, $config->next(30));
        $this->assertSame(35, $config->clamp(500));

        $unlimited = new InfiniteScrollConfig(10, InfiniteScrollMode::Scroll, null, 300);

        $this->assertSame(500, $unlimited->clamp(500));
        $this->assertSame(500, $unlimited->clamp(509));
        $this->assertSame(30, $config->clamp(34));

        $low = new InfiniteScrollConfig(10, InfiniteScrollMode::Scroll, 3, 300);

        $this->assertSame(10, $low->clamp(50));
    }

    public function test_defaults_come_from_the_config_file(): void
    {
        config()->set('filament-infinite-scroll.per_page', 7);
        config()->set('filament-infinite-scroll.mode', 'button');
        config()->set('filament-infinite-scroll.max_records', null);
        config()->set('filament-infinite-scroll.root_margin', 50);

        $table = Livewire::test(ListTeams::class)->instance()->getTable();

        InfiniteScroll::configure($table);

        $config = app(InfiniteScrollRegistry::class)->get($table);

        $this->assertInstanceOf(InfiniteScrollConfig::class, $config);
        $this->assertSame(7, $config->perPage);
        $this->assertSame(InfiniteScrollMode::Button, $config->mode);
        $this->assertNull($config->maxRecords);
        $this->assertSame(50, $config->rootMargin);

        InfiniteScroll::configure($table, maxRecords: 20, mode: 'scroll');

        $config = app(InfiniteScrollRegistry::class)->get($table);

        $this->assertSame(20, $config?->maxRecords);
        $this->assertSame(InfiniteScrollMode::Scroll, $config->mode);
    }
}
