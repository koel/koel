<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->string('homepage_url')->nullable()->after('url');
        });
    }
};
