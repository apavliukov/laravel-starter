<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Dto\Users\UpdateUserInput;
use App\Models\User;
use App\Support\Users\SyncsUserRole;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUser
{
    use SyncsUserRole;

    public function __construct(private Hasher $hasher) {}

    public function __invoke(User $user, UpdateUserInput $input): User
    {
        return DB::transaction(function () use ($user, $input): User {
            $attributes = [
                'organization_id' => $input->organizationId,
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
            ];

            if ($input->password !== null) {
                $attributes['password'] = $this->hasher->make($input->password);
            }

            $user->update($attributes);

            $this->syncRole($user, $input->role);

            return $user->refresh();
        });
    }
}
