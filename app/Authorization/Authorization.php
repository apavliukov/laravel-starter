<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Contracts\AuthorizationRole;
use App\Authorization\Contracts\TeamResolver;
use BackedEnum;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void useRoleEnum(string $roleEnum)
 * @method static string roleEnum()
 * @method static void authorizableModels(array<int, class-string> $models)
 * @method static array<int, class-string> models()
 * @method static array<int, AuthorizationRole&BackedEnum> superAdminRoles()
 * @method static void resolveTeamsUsing(string $resolverClass)
 * @method static TeamResolver teamResolver()
 *
 * @see AuthorizationManager
 */
final class Authorization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthorizationManager::class;
    }
}
