<?php

namespace App\Http\Controllers\API\Embed;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Embed\EmbedOptionsEncryptRequest;
use App\Values\EmbedOptions;

class EmbedOptionsController extends Controller
{
    public function encrypt(EmbedOptionsEncryptRequest $request)
    {
        return response()->json([
            'encrypted' => EmbedOptions::make(
                theme: $request->theme,
                layout: $request->layout,
                preview: $request->boolean('preview'),
            )->encrypt(),
        ]);
    }
}
