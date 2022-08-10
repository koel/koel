<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class MacroProvider extends ServiceProvider
{
    public function boot(): void
    {
        Builder::macro('integerCastType', function (): string { // @phpcs:ignore
            return match (DB::getDriverName()) {
                'mysql' => 'UNSIGNED', // only newer versions of MySQL support "INTEGER"
                'sqlsrv' => 'INT',
                default => 'INTEGER',
            };
        });
    }
}
