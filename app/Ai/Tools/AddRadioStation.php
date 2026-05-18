<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;
use App\Services\RadioService;
use App\Values\Radio\RadioStationCreateData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddRadioStation implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly RadioService $radioService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Add a new radio station. Use this when the user wants to add a radio station with a streaming URL.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The station name'),
            'url' => $schema->string()->required()->description('The streaming URL of the radio station'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $userId = $this->context->user->id;

        $validator = Validator::make(['url' => $request['url']], [
            'url' => [
                'required',
                'url',
                Rule::unique('radio_stations')->where(static fn (Builder $query) => $query->where('user_id', $userId)),
                new SafeUrl(),
                new HasAudioContentType(),
            ],
        ]);

        if ($validator->fails()) {
            return sprintf('Cannot add radio station: %s', $validator->errors()->first('url'));
        }

        $station = $this->radioService->createRadioStation(
            RadioStationCreateData::make(url: $request['url'], name: $request['name'], description: ''),
            $this->context->user,
        );

        $this->result->action = 'add_radio_station';
        $this->result->data = [
            'station' => $station,
        ];

        return sprintf('Radio station "%s" added successfully.', $station->name);
    }
}
