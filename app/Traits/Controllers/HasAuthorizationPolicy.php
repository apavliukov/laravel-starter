<?php

declare(strict_types=1);

namespace App\Traits\Controllers;

use App\Enums\Policies\Ability;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

trait HasAuthorizationPolicy
{
    use AuthorizesRequests;

    protected string $authorizeErrorMessage = '';

    /**
     * Authorize user with ability
     */
    public function authorizeUserAbility(Ability $ability, ?Model $model = null): bool
    {
        try {
            $this->authorize(Str::camel($ability->value), [$this->modelClass, $model]);
        } catch (AuthorizationException) {
            $this->logUserRequestError($ability);
            $this->setAuthorizeErrorMessage($ability);

            return false;
        }

        return true;
    }

    /**
     * Log message if user tried to view page without permission
     */
    public function logUserRequestError(Ability $ability): void
    {
        // TODO use new Context facade to generate error log message like
        // "User #%s tried to view page \"%s\" without permission.\nRequest headers: %s."
    }

    /**
     * Redirect request if user doesn't have permission to view page
     */
    public function redirectIfNoPermission(): RedirectResponse
    {
        return back()->withErrors([
            $this->authorizeErrorMessage,
        ]);
    }

    /**
     * Get redirection error message
     */
    public function getRedirectMessage(Ability $ability): array|string|Translator|null
    {
        $translationString = sprintf(
            'auth.policies.%s.%s',
            get_model_table($this->modelClass),
            Str::snake($ability->value)
        );

        return __($translationString);
    }

    public function setAuthorizeErrorMessage(Ability $ability): void
    {
        $this->authorizeErrorMessage = $this->getRedirectMessage($ability);
    }
}
