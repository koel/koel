<?php

namespace App\Listeners;

use Media;

class TidyLibrary
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Fired every time a LibraryChanged event is triggered.
     * Tidies up our lib.
     */
    public function handle()
    {
        Media::tidy();
    }
}
