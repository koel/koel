<?php

use App\Helpers\Ulid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', static function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', static function (Blueprint $table): void {
            $table->string('organization_id', 26)->nullable()->after('id')->index();
        });

        $organizationId = Ulid::generate();

        DB::table('organizations')->insert([
            'id' => $organizationId,
            'name' => 'Koel',
            'slug' => 'koel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update(['organization_id' => $organizationId]);

        Schema::table('users', static function (Blueprint $table): void {
            $table->string('organization_id', 26)->nullable(false)->change();

            $table
                ->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
