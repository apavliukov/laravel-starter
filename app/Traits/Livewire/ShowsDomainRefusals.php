<?php

declare(strict_types=1);

namespace App\Traits\Livewire;

use DomainException;
use Throwable;

/**
 * Business refusals thrown by an Action (the package's OwnershipException,
 * MembershipPolicyException, …) surface as an error toast instead of a 500.
 * Livewire's `exception` lifecycle hook; parameter names are bound by Livewire.
 */
trait ShowsDomainRefusals
{
    public function exceptionShowsDomainRefusals(Throwable $e, callable $stopPropagation): void
    {
        if (! $e instanceof DomainException) {
            return;
        }

        $this->dispatch('toast', message: $e->getMessage(), variant: 'error');
        $stopPropagation();
    }
}
