<?php

namespace App\Exceptions;

use App\Http\Responses\Subsonic\SubsonicResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class SubsonicAwareErrorHandler
{
    /**
     * Subsonic error codes per https://opensubsonic.netlify.app/docs/responses/error/
     * Listed specific-first; the first matching class wins.
     *
     * @var array<class-string<Throwable>, array{int, string}>
     */
    private const array EXCEPTION_MAP = [
        ValidationException::class => [10, 'Required parameter is missing.'],
        AuthorizationException::class => [50, 'User is not authorized for the given operation.'],
        NotFoundHttpException::class => [70, 'The requested data was not found.'],
        OperationNotApplicableForSmartPlaylistException::class => [
            0,
            'Operation is not applicable to smart playlists.',
        ],
    ];

    public static function handle(Throwable $e, Request $request): ?Response
    {
        if (!$request->is('rest/*')) {
            return null;
        }

        foreach (self::EXCEPTION_MAP as $class => [$code, $message]) {
            if ($e instanceof $class) {
                return SubsonicResponse::error($code, $message)->toResponse($request);
            }
        }

        return null;
    }
}
