<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PublicStorageLinker
{
    public function link(): bool
    {
        if ($this->linkIsUpToDate()) {
            return true;
        }

        return rescue(
            static fn (): bool => Artisan::call('storage:link', [
                '--quiet' => true,
                '--relative' => true,
                '--force' => true,
            ]) === Command::SUCCESS,
            false,
        );
    }

    private function linkIsUpToDate(): bool
    {
        $link = public_path('storage');
        $target = realpath(storage_path('app/public'));

        return is_link($link) && $target !== false && realpath($link) === $target;
    }
}
