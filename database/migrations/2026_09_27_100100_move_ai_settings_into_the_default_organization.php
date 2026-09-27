<?php

use App\Models\Organization;
use App\Values\AiSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Enums\Lab;

return new class extends Migration {
    public function up(): void
    {
        $provider = Lab::tryFrom((string) config('ai.default'));
        $apiKey = in_array($provider, AiSettings::PROVIDERS, strict: true)
            ? config("ai.providers.{$provider->value}.key")
            : null;

        if (!$apiKey) {
            return;
        }

        $organizationId = DB::table('organizations')->where('slug', Organization::DEFAULT_SLUG)->value('id');

        if (!$organizationId) {
            return;
        }

        DB::table('settings')->insert([
            'organization_id' => $organizationId,
            'key' => 'ai',
            'value' => json_encode([
                'enabled' => (bool) config('koel.ai.enabled'),
                'provider' => $provider->value,
                'api_key' => Crypt::encryptString($apiKey),
            ]),
        ]);
    }
};
