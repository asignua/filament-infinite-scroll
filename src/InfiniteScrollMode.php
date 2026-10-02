<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll;

enum InfiniteScrollMode: string
{
    /**
     * The next chunk loads by itself when the end of the table scrolls into view.
     */
    case Scroll = 'scroll';

    /**
     * The next chunk loads on a click of the "Load more" button.
     */
    case Button = 'button';
}
