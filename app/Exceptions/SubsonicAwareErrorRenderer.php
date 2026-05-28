<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\SubsonicThrowable;
use App\Http\Responses\Subsonic\SubsonicResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class SubsonicAwareErrorRenderer
{
    /**
     * Subsonic error codes per https://opensubsonic.netlify.app/docs/responses/error/
     * for framework/SPL exceptions we can't modify. koel-owned exceptions should
     * implement {@see SubsonicThrowable} instead.
     *
     * @var array<class-string<Throwable>, array{int, string}>
     */
    private const array EXCEPTION_MAP = [
        ValidationException::class => [10, 'Required parameter is missing.'],
        AccessDeniedHttpException::class => [50, 'User is not authorized for the given operation.'],
        NotFoundHttpException::class => [70, 'The requested data was not found.'],
    ];

    public static function render(Throwable $e, Request $request): ?Response
    {
        if (!$request->is('rest/*')) {
            return null;
        }

        if ($e instanceof SubsonicThrowable) {
            return SubsonicResponse::error($e->getSubsonicErrorCode(), $e->getSubsonicErrorMessage())->toResponse(
                $request,
            );
        }

        foreach (self::EXCEPTION_MAP as $class => [$code, $message]) {
            if ($e instanceof $class) {
                return SubsonicResponse::error($code, $message)->toResponse($request);
            }
        }

        return null;
    }
}
