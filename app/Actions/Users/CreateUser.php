<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\DTO\Users\CreateUserInput;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class CreateUser
{
    public function __construct(private Hasher $hasher) {}

    public function __invoke(CreateUserInput $input): User
    {
        return DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
                'password' => $this->hasher->make($input->password),
            ]);

            $user->syncRoles([$input->role->value]);

            return $user;
        });
    }
}
