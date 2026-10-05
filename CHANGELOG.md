# Changelog

All notable changes to `asignua/filament-infinite-scroll` are documented here.

## v1.0.0 - 2026-10-05

- `Table::infiniteScroll()`: a table grows chunk by chunk instead of paging. Works in resources, relation managers and table widgets.
- Two modes: `scroll` (the next chunk loads when the end of the table comes into view) and `button` ("Load more").
- A ceiling (`max_records`, default 500) that the list never grows past, with a message that points to search and filters.
- Search, filters, sort, grouping and tab changes start the list over from the first chunk; a stale `?page=` is ignored.
- Ten languages, a global Livewire hook (no base class to change), Laravel Boost guidelines.
- The browser cannot raise the page size past what the server issued, so a table with no ceiling (`maxRecords: false`) cannot be loaded whole in one request; a lowered size snaps to whole chunks.
