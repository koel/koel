<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', static function (Blueprint $table): void {
            $table->string('hash', 32)->unique()->nullable();
        });

        Folder::all()->each(static function (Folder $folder): void {
            $folder->hash = simple_hash($folder->path);
            $folder->save();
        });
    }
};
