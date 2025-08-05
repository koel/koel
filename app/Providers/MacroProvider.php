<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;

class MacroProvider extends ServiceProvider
{
    public function boot(): void
    {
        Collection::macro('orderByArray', function (array $orderBy, string $key = 'id') {
            /** @var Collection $this */
            return $this->sortBy(static fn ($item) => array_search($item->$key, $orderBy, true))->values();
        });

        Builder::macro('logSql', function (): Builder {
            /** @var Builder $this */
            Log::info($this->toSql());

            return $this;
        });

        DB::macro('dropForeignKeyIfExists', function (string $table, string $column): void { // @phpcs:ignore
            $driver = DB::getDriverName();

            if (!in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
                if (!app()->runningUnitTests()) {
                    Log::warning("No drop FK logic for driver $driver");
                }

                return;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->select('CONSTRAINT_NAME')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', $table)
                    ->where('COLUMN_NAME', $column)
                    ->whereNotNull('REFERENCED_TABLE_NAME')
                    ->first();
            } else {
                $constraint = DB::table('information_schema.table_constraints as tc')
                    ->join('information_schema.key_column_usage as kcu', static function ($join): void {
                        $join->on('tc.constraint_name', '=', 'kcu.constraint_name')
                            ->on('tc.constraint_schema', '=', 'kcu.constraint_schema');
                    })
                    ->select('tc.constraint_name')
                    ->where('tc.constraint_type', 'FOREIGN KEY')
                    ->where('tc.table_name', $table)
                    ->where('kcu.column_name', $column)
                    ->first();
            }

            if ($constraint) {
                Schema::table($table, static fn (Blueprint $table) => $table->dropForeign([$column]));
            }
        });

        if (app()->runningUnitTests()) {
            UploadedFile::macro(
                'fromFile',
                function (string $path, ?string $name = null): UploadedFile { // @phpcs:ignore
                    return UploadedFile::fake()->createWithContent($name ?? basename($path), File::get($path));
                }
            );

            TestResponse::macro('log', function (string $file = 'test-response.json'): TestResponse {
                /** @var TestResponse $this */
                File::put(storage_path('logs/' . $file), $this->getContent());

                return $this;
            });
        }
    }
}
