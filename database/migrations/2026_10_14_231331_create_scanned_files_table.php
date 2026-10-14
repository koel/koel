<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('scanned_files')) {
            return;
        }

        Schema::create('scanned_files', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('scan_id', 36)->charset('ascii');
            $table->text('path');
            $table->string('path_hash', 32)->charset('ascii');
            $table->string('type', 16);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['scan_id', 'path_hash']);
        });
    }
};
