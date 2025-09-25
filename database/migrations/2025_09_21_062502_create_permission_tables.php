<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::create($tableNames['permissions'], static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames): void {
            $table->bigIncrements('id');

            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }

            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create(
            $tableNames['model_has_permissions'],
            static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams): void {
                $table->unsignedBigInteger($pivotPermission);

                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);

                $table->index(
                    [$columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_model_id_model_type_index',
                );

                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');

                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                    $table->primary([
                        $columnNames['team_foreign_key'],
                        $pivotPermission,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ], 'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([
                        $pivotPermission,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ], 'model_has_permissions_permission_model_type_primary');
                }
            },
        );

        Schema::create(
            $tableNames['model_has_roles'],
            static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams): void {
                $table->unsignedBigInteger($pivotRole);

                $table->string('model_type');
                $table->unsignedInteger($columnNames['model_morph_key']);

                $table->index(
                    [$columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_model_id_model_type_index',
                );

                $table->foreign($pivotRole)
                    ->references('id') // role id
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');

                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                    $table->primary([
                        $columnNames['team_foreign_key'],
                        $pivotRole,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ], 'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([
                        $pivotRole,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ], 'model_has_roles_role_model_type_primary');
                }
            }
        );

        Schema::create(
            $tableNames['role_has_permissions'],
            static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission): void {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);

                $table->foreign($pivotPermission)
                    ->references('id') // permission id
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');

                $table->foreign($pivotRole)
                    ->references('id') // role id
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');

                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            },
        );

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
