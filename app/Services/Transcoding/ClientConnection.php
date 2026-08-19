<?php

namespace App\Services\Transcoding;

class ClientConnection
{
    public function keepRunningAfterDisconnect(): bool
    {
        return (bool) ignore_user_abort(true);
    }

    public function restoreAbortBehavior(bool $ignoreUserAbort): void
    {
        ignore_user_abort($ignoreUserAbort);
    }

    public function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    public function isDisconnected(): bool
    {
        return connection_aborted() !== 0;
    }
}
