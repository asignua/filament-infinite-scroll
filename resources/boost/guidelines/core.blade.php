## Filament Infinite Scroll (asignua/filament-infinite-scroll)

- Adds `Table::infiniteScroll()` (a macro; no trait, no base class). Put it in any `table(Table $table)`: resource, relation manager, table widget, custom page. `->infiniteScroll(perPage: 25, mode: 'scroll'|'button'|InfiniteScrollMode::Button, maxRecords: 500|false, rootMargin: 300)`; every argument is optional and defaults to `config/filament-infinite-scroll.php`.
- It replaces Filament's pager. Do not combine it with your own `paginated()`, `paginationPageOptions()`, `paginationMode()` or `persistRecordsPerPageInSession()`: the macro sets them (one query, default pagination mode, page size = one chunk).
- Mechanism: the table's page size (`$tableRecordsPerPage`) grows by one chunk per "load more", page stays 1. Selection, bulk actions, grouping, summaries and record actions work as on a normal page. `maxRecords: false` removes the ceiling; the default ceiling protects the browser from a 50 000 row table.
- Search, filters, column searches, sort, grouping and `activeTab` changes reset the list to the first chunk (a signature of them is kept in the Livewire memo). Do not rely on `tableRecordsPerPage` being any other value.
- Run `php artisan filament:assets` after install: the package ships a small stylesheet that hides the stock pager of these tables.
- Strings live under `filament-infinite-scroll::infinite-scroll.*` (10 languages).
