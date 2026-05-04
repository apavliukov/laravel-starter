<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Policies\AbstractPolicy;
use Tests\TestCase;

abstract class BasePolicyTest extends TestCase
{
    protected AbstractPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->getPolicy();
    }

    abstract protected function getPolicy(): AbstractPolicy;
}
