<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Profile extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public bool $showEmailVerificationLink = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;

        $this->showEmailVerificationLink = $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        $this->redirect(route('admin.settings.profile'), navigate: true);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('admin.dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.profile');
    }
}
