<?php

namespace App\Exceptions\Contracts;

interface SubsonicThrowable
{
    public function getSubsonicErrorCode(): int;

    public function getSubsonicErrorMessage(): string;
}
