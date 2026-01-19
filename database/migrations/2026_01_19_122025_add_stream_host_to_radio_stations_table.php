<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->string('stream_host')->nullable()->after('url');
            $table->index('stream_host');
        });

        // Populate stream_host for existing radio stations
        DB::table('radio_stations')->get()->each(function ($station): void {
            $parsedUrl = parse_url($station->url);
            $host = $parsedUrl['host'] ?? null;
            $port = $parsedUrl['port'] ?? null;
            $scheme = $parsedUrl['scheme'] ?? 'http';

            if ($host) {
                $streamHost = $scheme . '://' . $host;
                if ($port && !in_array($port, [80, 443], true)) {
                    $streamHost .= ':' . $port;
                }

                DB::table('radio_stations')
                    ->where('id', $station->id)
                    ->update(['stream_host' => $streamHost]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->dropIndex(['stream_host']);
            $table->dropColumn('stream_host');
        });
    }
};
