<?php

namespace App\Http\Requests\Download;

use App\Enums\DownloadableType;
use Illuminate\Validation\Rules\Enum;

/**
 * @property string $type
 * @property array|null $ids
 * @property string|int|null $id
 */
class CheckDownloadableCountRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(DownloadableType::class)],
            'ids' => 'required_if:type,songs|array',
            'id' => 'required_if:type,album,artist,playlist',
        ];
    }

    public function downloadableType(): DownloadableType
    {
        return DownloadableType::from($this->type);
    }
}
