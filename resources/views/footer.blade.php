@php
    use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
    use Illuminate\Support\Number;

    $hasMore = $loaded < $total && $loaded < $config->limit();
    $isCapped = $loaded < $total && ! $hasMore;
    $isScroll = $config->mode === InfiniteScrollMode::Scroll;
@endphp

{{-- Nothing to say while the whole list fits in the first chunk. --}}
@if ($hasMore || $isCapped || $loaded > $config->perPage)
    <div
        class="fi-ta-infinite-scroll"
        data-mode="{{ $config->mode->value }}"
        @if ($hasMore && $isScroll)
            x-data="{
                busy: false,
                async load() {
                    if (this.busy) {
                        return
                    }

                    this.busy = true

                    try {
                        await this.$wire.{{ $method }}()
                    } finally {
                        this.busy = false
                    }

                    await this.$nextTick()

                    // The new rows may not have pushed the end out of view yet (a tall screen).
                    if (
                        this.$el.isConnected &&
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
                {{ __('filament-infinite-scroll::infinite-scroll.limit_reached', ['loaded' => Number::format($loaded), 'total' => Number::format($total)]) }}
            @elseif (! $hasMore)
                {{ __('filament-infinite-scroll::infinite-scroll.all_loaded', ['total' => Number::format($total)]) }}
            @else
                {{ __('filament-infinite-scroll::infinite-scroll.showing', ['loaded' => Number::format($loaded), 'total' => Number::format($total)]) }}
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
@endif
