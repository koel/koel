<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class PwaManifestTest extends PlusTestCase
{
    #[Test]
    public function manifestsUseBrandedName(): void
    {
        Setting::set('branding', ['name' => 'Little Bird']);

        $this->get('manifest.json')->assertJsonPath('name', 'Little Bird');
        $this->get('manifest-remote.json')->assertJsonPath('name', 'Little Bird Remote Controller');
    }
}
