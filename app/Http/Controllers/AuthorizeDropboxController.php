<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthorizeDropboxController extends Controller
{
    public function __invoke(Request $request)
    {
        $appKey = Arr::get(Cache::get($request->get('state')), 'app_key');

        abort_unless($appKey, Response::HTTP_NOT_FOUND);

        try {
            return redirect()->away(
                "https://www.dropbox.com/oauth2/authorize?client_id=$appKey&response_type=code&token_access_type=offline" // @phpcs:ignore
            );
        } catch (Throwable $e) {
            Log::error($e);
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to authorize with Dropbox. Please try again.');
        }
    }
}
