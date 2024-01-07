<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;

class MacroProvider extends ServiceProvider
{
    public function boot(): void
    {
        Collection::macro('orderByArray', function (array $orderBy, string $key = 'id'): Collection {
            /** @var Collection $this */
            return $this->sortBy(static fn ($item) => array_search($item->$key, $orderBy, true));
        });

        Builder::macro('logSql', function (): Builder {
            /** @var Builder $this */
            Log::info($this->toSql());

            return $this;
        });

        if (app()->runningUnitTests()) {
            UploadedFile::macro('fromFile', static function (string $path, ?string $name = null): UploadedFile {
                return UploadedFile::fake()->createWithContent($name ?? basename($path), File::get($path));
            });

            TestResponse::macro('log', function (string $file = 'test-response.json'): TestResponse {
                /** @var TestResponse $this */
                File::put(storage_path('logs/' . $file), $this->getContent());

                return $this;
            });
        }
    }
}
