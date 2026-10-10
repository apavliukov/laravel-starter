<?php

declare(strict_types=1);

namespace App\Actions\Users;

use AlexPavliukov\Authorization\Authorization;
use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

/** Touches only the global ADMIN role; organization memberships are left as they are. */
final readonly class UpdateUser
{
    public function __construct(private Hasher $hasher, private TenantMembership $membership) {}

    public function __invoke(User $user, UpdateUserInput $input): User
    {
        return DB::transaction(function () use ($user, $input): User {
            $attributes = [
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
            ];

            if ($input->password !== null) {
                $attributes['password'] = $this->hasher->make($input->password);
            }

            $user->update($attributes);

            $isPlatformAdmin = Authorization::userHasGlobalRole($user, Role::ADMIN);

            if ($input->platformAdmin && ! $isPlatformAdmin) {
                $this->membership->assignGlobal($user, Role::ADMIN);
            }

            if (! $input->platformAdmin && $isPlatformAdmin) {
                Authorization::withTeam(null, static fn (): User => $user->removeRole(Role::ADMIN->value));
                Authorization::forgetUserRoles($user);
            }

            return $user->refresh();
        });
    }
}
