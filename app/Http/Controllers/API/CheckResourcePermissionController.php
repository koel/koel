<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionableResourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResourcePermissionResource;
use App\Services\ResourcePermissionService;

class CheckResourcePermissionController extends Controller
{
    public function __invoke(
        ResourcePermissionService $service,
        string $type,
        string|int $id,
        string $action,
    ) {
        return new ResourcePermissionResource(
            type: $type,
            id: $id,
            action: $action,
            allowed: $service->checkPermission(PermissionableResourceType::resolve($type), $id, $action),
        );
    }
}
