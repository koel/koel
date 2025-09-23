<?php

namespace App\Http\Middleware;

use App\Attributes\DemoConstraint;
use App\Attributes\DisabledInDemo;
use App\Attributes\RequiresDemo;
use App\Enums\Acl\Role;
use App\Http\Middleware\Concerns\ChecksControllerAttributes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use Symfony\Component\HttpFoundation\Response;

class HandleDemoMode
{
    use ChecksControllerAttributes;

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $callback = static function (ReflectionAttribute $attribute): void {
            /** @var DemoConstraint $instance */
            $instance = $attribute->newInstance();

            if ($instance->allowAdminOverride && auth()->user()?->role === Role::ADMIN) {
                return;
            }

            abort(
                $attribute->newInstance()->code,
                'This action is disabled in demo mode.'
            );
        };

        if (config('koel.misc.demo')) {
            optional(Arr::get(self::getAttributeUsageFromRequest($request, DisabledInDemo::class), 0), $callback);
        } else {
            optional(Arr::get(self::getAttributeUsageFromRequest($request, RequiresDemo::class), 0), $callback);
        }

        return $next($request);
    }
}
