<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Policies\PolicyBasicAuthorizationInterface;
use App\Traits\Controllers\HasAuthorizationActions;

abstract readonly class AbstractPolicy implements PolicyBasicAuthorizationInterface
{
    use HasAuthorizationActions;

    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
    }

    abstract protected function getModelClass(): string;
}
