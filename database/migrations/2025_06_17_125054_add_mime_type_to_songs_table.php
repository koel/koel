<?php

use App\Models\Song;
use FileEye\MimeMap\Extension;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    private const DEFAULT_MIME_TYPE = 'audio/mpeg'; // Default to mp3

    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('mime_type')->nullable();
        });

        DB::table('songs')
            ->whereNull('podcast_id')
            ->chunkById(100, static function ($songs): void {
                $cases = '';
                $ids = [];

                /** @var Song $song */
                foreach ($songs as $song) {
                    $ids[] = $song->id;
                    $mimeType = self::guessMimeType($song);
                    $cases .= "WHEN '$song->id' THEN '$mimeType' ";
                }

                DB::table('songs')
                    ->whereIn('id', $ids)
                    ->update(['mime_type' => DB::raw("CASE id $cases END")]);
            });
    }

    public static function guessMimeType(object $song): string
    {
        static $extToMimeMap = [];

        $extension = Str::afterLast($song->path, '.');

        if (!$extension) {
            return self::DEFAULT_MIME_TYPE;
        }

        if (!isset($extToMimeMap[$extension])) {
            try {
                $mimeType = strtolower((new Extension($extension))->getDefaultType());
            } catch (Throwable) {
                $mimeType = self::DEFAULT_MIME_TYPE;
            }

            $extToMimeMap[$extension] = $mimeType;
        }

        return $extToMimeMap[$extension];
    }
};
