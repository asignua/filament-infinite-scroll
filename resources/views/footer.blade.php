@php
    use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
    use Illuminate\Support\Number;

    $hasMore = $loaded < $total && $loaded < $config->limit();
    $isCapped = $loaded < $total && ! $hasMore;
    $isScroll = $config->mode === InfiniteScrollMode::Scroll;
    $locale = app()->getLocale();
    // Scroll mode: a new key for every server state makes the morph REPLACE the sentinel instead of
    // patching it in place: a fresh Alpine component and a fresh IntersectionObserver, whose first
    // callback fires while the footer is already in view. That one mechanism both fills a tall screen
    // chunk by chunk (each successful load changes $loaded) and re-arms the sentinel after a
    // sort/filter reset. A request that added nothing keeps the key, so a failing load does not loop.
    // Only the sentinel carries it: the footer itself, with the status line and the button, keeps a
    // stable key, so keyboard focus stays on "Load more" and the live region is patched, not re-inserted.
    $footerKey = $this->getId().'.infinite-scroll.footer';
    $sentinelKey = $this->getId().'.infinite-scroll.'.($hasMore ? $loaded : 'done-'.$loaded);
    // The button vanishes with the last chunk: hand the focus to the status line, not to <body>.
    $focusStatus = ! $isScroll && ! $hasMore && $loaded > $config->perPage;
@endphp

{{-- Nothing to say while the whole list fits in the first chunk; the empty marker below still hides Filament's own pager, so short and long tables look alike. --}}
@if ($hasMore || $isCapped || $loaded > $config->perPage)
    <div
        class="fi-ta-infinite-scroll"
        wire:key="{{ $footerKey }}"
        data-mode="{{ $config->mode->value }}"
        data-loaded="{{ $loaded }}"
        data-has-more="{{ $hasMore ? '1' : '0' }}"
    >
        @if ($isScroll)
            <div
                wire:key="{{ $sentinelKey }}"
                data-has-more="{{ $hasMore ? '1' : '0' }}"
                @if ($hasMore)
                    x-data="{
                        busy: false,
                        async load() {
                            if (this.busy || this.$el.dataset.hasMore !== '1') {
                                return
                            }

                            this.busy = true

                            // No chaining here on purpose. A load that added rows changes the wire:key,
                            // so the morph replaces this element: the new one gets its own
                            // IntersectionObserver, whose first callback fires while it is still in view
                            // (a tall screen) and loads the next chunk. A load that added nothing keeps
                            // the key, the element is patched in place, and nothing fires again.
                            try {
                                await this.$wire.{{ $method }}()
                            } finally {
                                this.busy = false
                            }
                        },
                    }"
                    x-intersect.margin.{{ $config->rootMargin }}px="load()"
                @endif
            >
                @if ($hasMore)
                    <div x-show="busy" x-cloak>
                        <x-filament::loading-indicator />
                    </div>
                @endif
            </div>
        @endif

        <p class="fi-ta-infinite-scroll-status" role="status" tabindex="-1">
            @if ($isCapped)
                {{ __('filament-infinite-scroll::infinite-scroll.limit_reached', ['loaded' => Number::format($loaded, locale: $locale), 'total' => Number::format($total, locale: $locale)]) }}
            @elseif (! $hasMore)
                {{ __('filament-infinite-scroll::infinite-scroll.all_loaded', ['total' => Number::format($total, locale: $locale)]) }}
            @else
                {{ __('filament-infinite-scroll::infinite-scroll.showing', ['loaded' => Number::format($loaded, locale: $locale), 'total' => Number::format($total, locale: $locale)]) }}
            @endif
        </p>

        @if (! $isScroll && $hasMore)
            <x-filament::button
                color="gray"
                :wire:click="$method"
                :wire:key="$this->getId() . '.infinite-scroll.load-more'"
            >
                {{ __('filament-infinite-scroll::infinite-scroll.load_more') }}
            </x-filament::button>
        @elseif ($focusStatus)
            {{-- Inserted once, when the button goes away: Alpine runs x-init on the new node only. --}}
            <span
                wire:key="{{ $this->getId() }}.infinite-scroll.focus-status"
                hidden
                x-init="$nextTick(() => $el.parentElement.querySelector('.fi-ta-infinite-scroll-status')?.focus())"
            ></span>
        @endif
    </div>
@else
    <div class="fi-ta-infinite-scroll-marker" wire:key="{{ $this->getId() }}.infinite-scroll.marker" hidden></div>
@endif
