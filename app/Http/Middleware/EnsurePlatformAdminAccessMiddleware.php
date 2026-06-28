<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Policies\SystemAbility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Delegates to Laravel's authorization layer via the SystemAbility::ACCESS_PLATFORM_ADMIN gate.
 */
final class EnsurePlatformAdminAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        throw_if(
            $request->user()->cannot(SystemAbility::ACCESS_PLATFORM_ADMIN),
            AccessDeniedHttpException::class
        );

        return $next($request);
    }
}
