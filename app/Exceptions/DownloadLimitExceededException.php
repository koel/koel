<?php

namespace App\Exceptions;

use DomainException;

class DownloadLimitExceededException extends DomainException
{
    public function __construct(int $limit)
    {
        $noun = str()->plural('song', $limit);

        parent::__construct("Cannot download more than $limit $noun at once.");
    }
}
