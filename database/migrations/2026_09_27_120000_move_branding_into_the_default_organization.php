<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $organizationId = DB::table('organizations')->where('slug', Organization::DEFAULT_SLUG)->value('id');

        if (!$organizationId) {
            return;
        }

        DB::table('settings')
            ->where('key', 'branding')
            ->whereNull('organization_id')
            ->update(['organization_id' => $organizationId]);
    }
};
