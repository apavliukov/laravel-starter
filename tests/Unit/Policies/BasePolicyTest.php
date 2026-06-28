<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use AlexPavliukov\Authorization\AbstractPolicy;
use Tests\TestCase;

abstract class BasePolicyTest extends TestCase
{
    protected AbstractPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = resolve($this->getPolicyClass());
    }

    /**
     * @return class-string<AbstractPolicy>
     */
    abstract protected function getPolicyClass(): string;
}
