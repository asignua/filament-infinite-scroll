# Filament Infinite Scroll

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/asignua/filament-infinite-scroll.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-infinite-scroll)
[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-infinite-scroll/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/asignua/filament-infinite-scroll/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/asignua/filament-infinite-scroll.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-infinite-scroll)
[![License](https://img.shields.io/packagist/l/asignua/filament-infinite-scroll.svg?style=flat-square)](https://github.com/asignua/filament-infinite-scroll/blob/main/LICENSE.md)
[![Plumb score](https://plumbphp.dev/badges/asignua/filament-infinite-scroll/composite.svg)](https://plumbphp.dev/asignua/filament-infinite-scroll)

<img class="filament-hidden" src="https://raw.githubusercontent.com/asignua/filament-infinite-scroll/v1.0.0/art/cover.jpg" alt="Filament Infinite Scroll">

Infinite scroll and a "Load more" button for [Filament](https://filamentphp.com) tables, with one line:

```php
return $table->columns([...])->infiniteScroll();
```

Filament 5 offers default, simple and cursor pagination only. People keep asking for a table that just grows
([#12764](https://github.com/filamentphp/filament/discussions/12764),
[#5692](https://github.com/filamentphp/filament/discussions/5692)), and the one existing plugin supports Filament 3
only. This one is built for Filament 5.

- [Screenshots](#screenshots)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [How it works](#how-it-works)
- [Configuration](#configuration)
- [Gotchas](#gotchas)
- [Translations](#translations)
- [AI agents](#ai-agents)
- [Testing](#testing)

## Screenshots

![Scroll mode loading the next chunk](https://raw.githubusercontent.com/asignua/filament-infinite-scroll/v1.0.0/art/scrolling.jpg)

![Everything loaded](https://raw.githubusercontent.com/asignua/filament-infinite-scroll/v1.0.0/art/all-loaded.jpg)

## Requirements

- PHP 8.3+
- Filament 5 (Livewire 4)
- Laravel 12 or 13

## Installation

```bash
composer require asignua/filament-infinite-scroll
php artisan filament:assets
```

The service provider is auto-discovered. `filament:assets` publishes a 1 KB stylesheet that hides Filament's own pager
on these tables and lays out the footer. Optionally publish the config:

```bash
php artisan vendor:publish --tag=filament-infinite-scroll-config
```

## Usage

`infiniteScroll()` is a macro on `Filament\Tables\Table`. Call it in any `table()`: a resource, a relation manager, a
table widget, a page with `InteractsWithTable`.

```php
use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;

public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->infiniteScroll(
            perPage: 25,                          // rows per chunk (first and every next one)
            mode: InfiniteScrollMode::Scroll,     // or 'scroll' | 'button'
            maxRecords: 500,                      // false removes the ceiling
            rootMargin: 300,                      // px before the end at which the next chunk is requested
        );
}
```

Every argument is optional and defaults to `config/filament-infinite-scroll.php`.

| Mode     | Behaviour |
| -------- | --------- |
| `scroll` | The next chunk loads when the end of the table scrolls into view. If the new rows do not push the end out of view (a tall screen) the next chunk follows at once. |
| `button` | A "Load more" button under the table. |

Under the rows the table shows "Showing 25 of 120", then "All 120 records are loaded" when the list is complete. A
table with fewer rows than one chunk shows no footer at all.

## How it works

Filament's table is one query and one Blade view. The plugin forks neither.

1. **The page size grows.** The table's `$tableRecordsPerPage` (a public Livewire property) goes `25 → 50 → 75 …`,
   the page stays 1. Because it is still a single paginated query, selection, "select all", bulk actions, grouping,
   summaries and record actions work exactly as on a normal page.
2. **A global Livewire hook** answers the `infiniteScrollLoadMore` call, so no trait or base class has to be added to
   your pages, relation managers or widgets. The same hook keeps the page size inside `[one chunk, ceiling]` (the
   value is client-writable, so it is clamped on the server) and pins the page number to 1.
3. **A render hook** (`TablesRenderHook::CONTENT_AFTER`) draws the footer: the status line, the `x-intersect`
   sentinel or the button (`<x-filament::button>`).
4. **Reset on change.** A signature of the search, column searches, filters, sort, grouping and the active tab is kept
   in the Livewire memo (checksummed with the snapshot). When it changes the list starts over from the first chunk.

Scroll position is preserved: Livewire morphs the new rows in below the existing ones.

### Trade-offs

- Rows are re-queried: loading chunk 4 runs `LIMIT 100`, not `LIMIT 25 OFFSET 75`. This is what keeps selection and
  actions simple, and it is cheap up to the ceiling. It is why there is a ceiling.
- The table needs a total (`COUNT(*)`), like Filament's default pager. Cursor and simple pagination are not used.
- The DOM grows with the list. Past a few hundred rows the browser, not the database, becomes the limit.

## Configuration

`config/filament-infinite-scroll.php`:

| Key           | Default    | |
| ------------- | ---------- | - |
| `per_page`    | `25`       | Rows per chunk. |
| `mode`        | `'scroll'` | `'scroll'` or `'button'`. |
| `max_records` | `500`      | The most rows kept on the page; `null` removes the ceiling. |
| `root_margin` | `300`      | Pixels before the end at which the next chunk is requested. |

When the ceiling is reached the footer says "Showing the first 500 of 12,345 records. Use search or filters to narrow
the list." and loading stops.

## Gotchas

- `infiniteScroll()` sets `paginated()`, the page-size options, the pagination mode and
  `persistRecordsPerPageInSession(false)`. Do not set them yourself afterwards.
- The "records per page" select disappears: the page size is the plugin's state.
- Do not use `deferLoading()` plus a custom `records()` data source: the plugin needs a length-aware paginator and
  draws nothing otherwise.
- Reordering (`reorderable()`) uses Filament's own rules for pagination while reordering.
- Run `php artisan filament:assets` after upgrading, or the stock pager shows next to the footer.

## Translations

English, Ukrainian, German, Spanish, French, Italian, Dutch, Polish, Brazilian Portuguese and Turkish, under the
`filament-infinite-scroll::infinite-scroll` namespace. A test keeps every language in step with the English keys and
placeholders.

## AI agents

The package ships [Laravel Boost](https://laravel.com/docs/boost) guidelines
(`resources/boost/guidelines/core.blade.php`) so a coding agent uses the macro correctly.

## Testing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
```

The suite runs on [Orchestra Testbench](https://packages.tools/testbench) with a `workbench/` panel: a resource, a
relation manager and a table widget.

## Changelog

See [CHANGELOG.md](https://github.com/asignua/filament-infinite-scroll/blob/main/CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE.md](https://github.com/asignua/filament-infinite-scroll/blob/main/LICENSE.md).
