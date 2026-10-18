<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\KoelAssistant;
use App\Ai\AiRequestContext;
use App\Ai\Tools\AddRadioStation;
use App\Ai\Tools\PlayLatestPodcastEpisode;
use App\Ai\Tools\PlayRadioStation;
use App\Ai\Tools\ResumePodcast;
use App\Ai\Tools\SubscribeToPodcast;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class KoelAssistantTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->instance(AiRequestContext::class, new AiRequestContext(create_user()));
    }

    #[Test]
    public function leaveOutRadioWhenItIsTurnedOff(): void
    {
        $assistant = new KoelAssistant(radioEnabled: false);
        $toolClasses = array_map(get_class(...), [...$assistant->tools()]);

        self::assertNotContains(AddRadioStation::class, $toolClasses);
        self::assertNotContains(PlayRadioStation::class, $toolClasses);
        self::assertContains(SubscribeToPodcast::class, $toolClasses);
        self::assertStringNotContainsString('radio', (string) $assistant->instructions());
    }

    #[Test]
    public function leaveOutPodcastsWhenTheyAreTurnedOff(): void
    {
        $assistant = new KoelAssistant(podcastsEnabled: false);
        $toolClasses = array_map(get_class(...), [...$assistant->tools()]);

        self::assertNotContains(SubscribeToPodcast::class, $toolClasses);
        self::assertNotContains(PlayLatestPodcastEpisode::class, $toolClasses);
        self::assertNotContains(ResumePodcast::class, $toolClasses);
        self::assertContains(AddRadioStation::class, $toolClasses);
        self::assertStringNotContainsString('podcast', (string) $assistant->instructions());
    }
}
