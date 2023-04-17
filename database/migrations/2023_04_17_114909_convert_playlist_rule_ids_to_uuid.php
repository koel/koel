<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        DB::table('playlists')
            ->whereNotNull('rules')
            ->where('rules', '<>', '')
            ->get()
            ->each(static function ($playlist): void {
                $groups = array_map(static function ($rule): array {
                    return [
                        'id' => Str::uuid()->toString(),
                        'rules' => array_map(static function ($rule): array {
                            return [
                                'id' => Str::uuid()->toString(),
                                'model' => $rule->model,
                                'operator' => $rule->operator,
                                'value' => $rule->value,
                            ];
                        }, $rule->rules),
                    ];
                }, json_decode($playlist->rules, false));

                DB::table('playlists')
                    ->where('id', $playlist->id)
                    ->update(['rules' => json_encode($groups)]);
            });
    }
};
