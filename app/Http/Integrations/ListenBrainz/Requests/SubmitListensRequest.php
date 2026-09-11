<?php

namespace App\Http\Integrations\ListenBrainz\Requests;

use App\Enums\ListenType;
use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class SubmitListensRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly Song $song,
        private readonly User $user,
        private readonly ListenType $listenType,
        private readonly ?int $timestamp = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/1/submit-listens';
    }

    /** @inheritdoc */
    protected function defaultHeaders(): array
    {
        return ['Authorization' => "Token {$this->user->preferences->listenBrainzToken}"];
    }

    /** @inheritdoc */
    protected function defaultBody(): array
    {
        $listen = ['track_metadata' => $this->getTrackMetadata()];

        if ($this->listenType === ListenType::SINGLE) {
            $listen['listened_at'] = $this->timestamp;
        }

        return [
            'listen_type' => $this->listenType->value,
            'payload' => [$listen],
        ];
    }

    /** @return array{artist_name: string, track_name: string, release_name?: string, additional_info: array} */
    private function getTrackMetadata(): array
    {
        $metadata = [
            'artist_name' => $this->song->artist->name,
            'track_name' => $this->song->title,
            'additional_info' => array_filter([
                'duration_ms' => $this->song->length ? (int) round($this->song->length * 1000) : null,
                'tracknumber' => $this->song->track ?: null,
                'media_player' => 'Koel',
                'submission_client' => 'Koel',
            ]),
        ];

        if ($this->song->album->name !== Album::UNKNOWN_NAME) {
            $metadata['release_name'] = $this->song->album->name;
        }

        return $metadata;
    }
}
