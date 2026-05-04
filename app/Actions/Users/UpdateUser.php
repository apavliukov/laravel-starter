<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Dto\Users\UpdateUserInput;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUser
{
    public function __construct(private Hasher $hasher) {}

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

            if ($user->appRole !== $input->role) {
                $user->syncRoles([$input->role->value]);
            }

            return $user->refresh();
        });
    }
}
