<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Contracts\Pagination\Paginator;

class DuplicateUploadService
{
    public function __construct(private readonly DuplicateUploadRepository $repository) {}

    public function findForUser(User $user, int $perPage = 50): Paginator
    {
        return $this->repository->findForUser($user, $perPage);
    }
}
