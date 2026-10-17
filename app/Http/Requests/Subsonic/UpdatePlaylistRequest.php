<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property string $playlistId
 * @property ?string $name
 * @property ?string $comment
 * @property list<string> $songIdToAdd
 * @property list<int> $songIndexToRemove
 */
class UpdatePlaylistRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'songIdToAdd' => Arr::wrap($this->input('songIdToAdd')),
            'songIndexToRemove' => Arr::wrap($this->input('songIndexToRemove')),
        ]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'playlistId' => [Rule::required(), Rule::string()],
            'name' => [Rule::nullable(), Rule::string()],
            'comment' => [Rule::nullable(), Rule::string()],
            'songIdToAdd' => [Rule::array()],
            'songIdToAdd.*' => [Rule::string()],
            'songIndexToRemove' => [Rule::array()],
            'songIndexToRemove.*' => [Rule::numeric()->integer()->min(0)],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'songIndexToRemove' => array_map('intval', Arr::wrap($this->input('songIndexToRemove'))),
        ]);
    }

    /** @return array<string, string> */
    public function getChanges(): array
    {
        return array_filter(
            ['name' => $this->name, 'description' => $this->comment],
            static fn ($value) => $value !== null,
        );
    }
}
