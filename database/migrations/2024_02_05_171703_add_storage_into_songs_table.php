<?php

use App\Values\SongStorageTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('storage')->nullable()->index();
        });

        DB::table('songs')->where('path', 'like', 's3://%')->update(['storage' => SongStorageTypes::S3_LAMBDA]);
    }
};
