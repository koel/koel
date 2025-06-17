<?php

use App\Enums\PlayableType;
use App\Models\Song;
use FileEye\MimeMap\Extension;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const DEFAULT_MIME_TYPE = 'audio/mpeg'; // Default to mp3

    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('mime_type')->nullable();
        });

        Song::query(PlayableType::SONG)->get()->each(static function (Song $song): void {
            $song->mime_type = self::guessMimeType($song);
            $song->save();
        });
    }

    public static function guessMimeType(Song $song): string
    {
        static $extToMimeMap = [];

        $extension = Str::afterLast($song->storage_metadata->getPath(), '.');

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
