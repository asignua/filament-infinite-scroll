@php
    use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
    use Illuminate\Support\Number;

    $hasMore = $loaded < $total && $loaded < $config->limit();
    $isCapped = $loaded < $total && ! $hasMore;
    $isScroll = $config->mode === InfiniteScrollMode::Scroll;
    $locale = app()->getLocale();
@endphp

{{-- Nothing to say while the whole list fits in the first chunk; the empty marker below still hides Filament's own pager, so short and long tables look alike. --}}
@if ($hasMore || $isCapped || $loaded > $config->perPage)
    <div
        class="fi-ta-infinite-scroll"
        data-mode="{{ $config->mode->value }}"
        data-loaded="{{ $loaded }}"
        data-has-more="{{ $hasMore ? '1' : '0' }}"
        @if ($hasMore && $isScroll)
            x-data="{
                busy: false,
                async load() {
                    if (this.busy || this.$el.dataset.hasMore !== '1') {
                        return
                    }

                    this.busy = true

                    const before = Number(this.$el.dataset.loaded)

                    try {
                        await this.$wire.{{ $method }}()
                    } finally {
                        this.busy = false
                    }

                    await this.$nextTick()

                    // The new rows may not have pushed the end out of view yet (a tall screen).
                    // The morph patches this element in place, so its attributes are the
                    // server's answer: stop at the last chunk, at the ceiling and when a failed
                    // request added nothing.
                    if (
                        this.$el.isConnected &&
                        this.$el.dataset.hasMore === '1' &&
                        Number(this.$el.dataset.loaded) > before &&
                        this.$el.getBoundingClientRect().top <
                            window.innerHeight + {{ $config->rootMargin }}
                    ) {
                        this.load()
                    }
                },
            }"
            x-intersect.margin.{{ $config->rootMargin }}px="load()"
        @endif
    >
        <p class="fi-ta-infinite-scroll-status" role="status">
            @if ($isCapped)
                {{ __('filament-infinite-scroll::infinite-scroll.limit_reached', ['loaded' => Number::format($loaded, locale: $locale), 'total' => Number::format($total, locale: $locale)]) }}
            @elseif (! $hasMore)
                {{ __('filament-infinite-scroll::infinite-scroll.all_loaded', ['total' => Number::format($total, locale: $locale)]) }}
            @else
                {{ __('filament-infinite-scroll::infinite-scroll.showing', ['loaded' => Number::format($loaded, locale: $locale), 'total' => Number::format($total, locale: $locale)]) }}
            @endif
        </p>

        @if ($hasMore && $isScroll)
            <div x-show="busy" x-cloak>
                <x-filament::loading-indicator />
            </div>
        @elseif ($hasMore)
            <x-filament::button
                color="gray"
                :wire:click="$method"
                :wire:key="$this->getId() . '.infinite-scroll.load-more'"
            >
                {{ __('filament-infinite-scroll::infinite-scroll.load_more') }}
            </x-filament::button>
        @endif
    </div>
@else
    <div class="fi-ta-infinite-scroll-marker" hidden></div>
@endif
