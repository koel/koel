<?php

namespace App\Repositories;

use App\Models\Organization;

/**
 * @extends Repository<Organization>
 */
class OrganizationRepository extends Repository
{
    public function getDefault(): Organization
    {
        return Organization::default();
    }
}
