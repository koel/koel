<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Repositories\RadioStationRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRadioStation implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly RadioStationRepository $radioStationRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play or stream a radio station by name. '
            . 'Use this when the user wants to listen to, play, or stream a radio station.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema
                ->string()
                ->required()
                ->description('The name (or partial name) of the radio station to play'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $stations = $this->radioStationRepository->search($request['name'], 1, $this->context->user);

        if ($stations->isEmpty()) {
            return sprintf('No radio station matching "%s" found.', $request['name']);
        }

        $station = $stations->first();

        $this->result->action = 'play_radio_station';
        $this->result->data = [
            'station' => $station,
        ];

        return sprintf('Now streaming "%s".', $station->name);
    }
}
