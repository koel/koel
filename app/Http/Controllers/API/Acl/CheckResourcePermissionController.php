<?php

namespace App\Http\Controllers\API\Acl;

use App\Enums\PermissionableResourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResourcePermissionResource;
use App\Services\Acl;

class CheckResourcePermissionController extends Controller
{
    public function __invoke(Acl $acl, string $type, string|int $id, string $action)
    {
        return new ResourcePermissionResource(
            type: $type,
            id: $id,
            action: $action,
            allowed: $acl->checkPermission(PermissionableResourceType::from($type), $id, $action),
        );
    }
}
