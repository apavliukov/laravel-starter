<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Dto\Users\CreateUserInput;
use App\Models\User;
use App\Support\Users\SyncsUserRole;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

final readonly class CreateUser
{
    use SyncsUserRole;

    public function __construct(private Hasher $hasher) {}

    public function __invoke(CreateUserInput $input): User
    {
        return DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'organization_id' => $input->organizationId,
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'email' => $input->email,
                'password' => $this->hasher->make($input->password),
            ]);

            $this->syncRole($user, $input->role);

            return $user;
        });
    }
}
