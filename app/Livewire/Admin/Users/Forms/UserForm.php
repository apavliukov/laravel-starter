<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users\Forms;

use App\DTO\Users\CreateUserInput;
use App\DTO\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class UserForm extends Form
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public ?int $ignoreUserId = null;

    public bool $passwordRequired = true;

    public function setUser(User $user): void
    {
        $this->firstName = $user->first_name ?? '';
        $this->lastName = $user->last_name ?? '';
        $this->email = $user->email ?? '';
        $this->role = $user->appRole->value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $emailRule = Rule::unique('users', 'email')->whereNull('deleted_at');

        if ($this->ignoreUserId !== null) {
            $emailRule->ignore($this->ignoreUserId);
        }

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$this->passwordRequired ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    public function toCreateInput(): CreateUserInput
    {
        return new CreateUserInput(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            password: $this->password,
            role: Role::from($this->role),
        );
    }

    public function toUpdateInput(): UpdateUserInput
    {
        return new UpdateUserInput(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            password: $this->password === '' ? null : $this->password,
            role: Role::from($this->role),
        );
    }
}
