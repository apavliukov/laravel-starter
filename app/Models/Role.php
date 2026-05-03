<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Policies\Role as RoleEnum;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @mixin IdeHelperRole
 */
final class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    final public const RoleEnum DEFAULT_ROLE = RoleEnum::MEMBER;
}
