<?php

namespace App\Services;

use App\Models\Organization;
use App\Repositories\OrganizationRepository;

class OrganizationService
{
    public function __construct(private readonly OrganizationRepository $repository)
    {
    }

    public function getCurrentOrganization(): Organization
    {
        return auth()->user()?->organization ?? $this->repository->getDefault();
    }
}
