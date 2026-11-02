<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\SongUploadFailedException;
use App\Exceptions\UnreadableMediaFileException;
use ErrorException;
use Illuminate\Support\Facades\Exceptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongUploadFailedExceptionTest extends TestCase
{
    #[Test]
    public function renderAGenericMessageForAWrappedError(): void
    {
        $exception = SongUploadFailedException::make(new ErrorException('copy(https://bucket.host/key): Failed'));

        self::assertSame('The file could not be processed.', $exception->render()->getData(true)['message']);
    }

    #[Test]
    public function reportAWrappedError(): void
    {
        Exceptions::fake();
        $error = new ErrorException('copy(https://bucket.host/key): Failed');

        SongUploadFailedException::make($error);

        Exceptions::assertReported(static fn (ErrorException $reported): bool => $reported === $error);
    }

    #[Test]
    public function renderAUserFacingMessage(): void
    {
        $exception = SongUploadFailedException::make('The uploaded file could not be stored.');

        self::assertSame('The uploaded file could not be stored.', $exception->render()->getData(true)['message']);
    }

    #[Test]
    public function renderTheReasonOfAnUnreadableMediaFile(): void
    {
        $exception = SongUploadFailedException::make(UnreadableMediaFileException::create('Empty file'));

        self::assertSame('Empty file', $exception->render()->getData(true)['message']);
    }
}
