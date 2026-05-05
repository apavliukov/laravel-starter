<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users\Forms;

use App\Dto\Users\CreateUserInput;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class UserForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public ?int $ignoreUserId = null;

    public bool $passwordRequired = true;

    public function setUser(User $user): void
    {
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$this->passwordRequired ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    public function toCreateInput(): CreateUserInput
    {
        return new CreateUserInput(
            firstName: $this->first_name,
            lastName: $this->last_name,
            email: $this->email,
            password: $this->password,
            role: Role::from($this->role),
        );
    }

    public function toUpdateInput(): UpdateUserInput
    {
        return new UpdateUserInput(
            firstName: $this->first_name,
            lastName: $this->last_name,
            email: $this->email,
            password: $this->password === '' ? null : $this->password,
            role: Role::from($this->role),
        );
    }
}
