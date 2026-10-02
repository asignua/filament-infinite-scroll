<?php

declare(strict_types=1);

return [

    /*
    | Rows in the first chunk and in every chunk added after it.
    */
    'per_page' => 25,

    /*
    | "scroll": the next chunk loads when the end of the table comes into view.
    | "button": the next chunk loads on a click of the "Load more" button.
    */
    'mode' => 'scroll',

    /*
    | The most rows a table keeps on the page, so nobody scrolls a 50 000 row table into the
    | browser. `null` removes the ceiling.
    */
    'max_records' => 500,

    /*
    | How many pixels before the end of the table the next chunk is requested (scroll mode).
    */
    'root_margin' => 300,

];
