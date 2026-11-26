<?php

use App\Helpers\SortTitle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    private const array LEADING_INVISIBLE_CHARACTERS = [
        "\u{00AD}",
        "\u{200B}",
        "\u{200C}",
        "\u{200D}",
        "\u{200E}",
        "\u{200F}",
        "\u{2060}",
        "\u{FEFF}",
    ];

    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('title_sort', SortTitle::MAX_LENGTH)->default('')->after('title')->index();
        });

        DB::table('songs')->update(['title_sort' => DB::raw(sprintf('SUBSTR(title, 1, %d)', SortTitle::MAX_LENGTH))]);

        DB::table('songs')
            ->select('id', 'title')
            ->where(static function (Builder $query): void {
                foreach (SortTitle::ARTICLES as $article) {
                    $query->orWhereRaw('LOWER(title) LIKE ?', [Str::lower($article) . ' %']);
                }

                foreach (self::LEADING_INVISIBLE_CHARACTERS as $character) {
                    $query->orWhere('title', 'like', $character . '%');
                }
            })
            ->lazyById()
            ->each(static function (object $song): void {
                DB::table('songs')
                    ->where('id', $song->id)
                    ->update(['title_sort' => SortTitle::fromTitle($song->title)]);
            });
    }
};
