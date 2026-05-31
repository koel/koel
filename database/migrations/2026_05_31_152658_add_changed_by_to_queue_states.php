<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('queue_states', static function (Blueprint $table): void {
            $table->string('changed_by')->nullable()->after('playback_position');
        });
    }
};
