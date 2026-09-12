<?php

namespace App\Http\Integrations\ListenBrainz\Requests;

use App\Enums\ListenBrainzListenType;
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
        private readonly ListenBrainzListenType $listenType,
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

        if ($this->listenType === ListenBrainzListenType::SINGLE) {
            $listen['listened_at'] = $this->timestamp;
        }

        return [
            'listen_type' => $this->listenType->value,
            'payload' => [$listen],
        ];
    }

    /**
     * @return array{
     *     artist_name: string,
     *     track_name: string,
     *     release_name?: string,
     *     additional_info: array{
     *         duration_ms?: int,
     *         tracknumber?: int,
     *         recording_mbid?: string,
     *         release_mbid?: string,
     *         artist_mbids?: list<string>,
     *         media_player: string,
     *         submission_client: string,
     *     },
     * }
     */
    private function getTrackMetadata(): array
    {
        $metadata = [
            'artist_name' => $this->song->artist->name,
            'track_name' => $this->song->title,
            'additional_info' => array_filter([
                'duration_ms' => $this->song->length ? (int) round($this->song->length * 1000) : null,
                'tracknumber' => $this->song->track ?: null,
                'recording_mbid' => $this->song->mbid,
                'release_mbid' => $this->song->album->mbid,
                'artist_mbids' => $this->getArtistMbids(),
                'media_player' => 'Koel',
                'submission_client' => 'Koel',
            ]),
        ];

        if ($this->song->album->name !== Album::UNKNOWN_NAME) {
            $metadata['release_name'] = $this->song->album->name;
        }

        return $metadata;
    }

    /** @return list<string>|null */
    private function getArtistMbids(): ?array
    {
        return $this->song->artist->mbid ? [$this->song->artist->mbid] : null;
    }
}
