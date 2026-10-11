<?php

namespace Tests\Feature;

use App\Http\Integrations\iTunes\Requests\LookupPodcastRequest;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

class AddPodcastFromApplePodcastsTest extends TestCase
{
    #[Test]
    public function refuseAnApplePodcastsLinkWithoutAPublicFeed(): void
    {
        Saloon::fake([LookupPodcastRequest::class => MockResponse::make(body: ['resultCount' => 0, 'results' => []])]);

        $this
            ->postAs('api/podcasts', [
                'url' => 'https://podcasts.apple.com/us/podcast/exclusive/id123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('url');
    }
}
