<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use AlexPavliukov\Authorization\AbstractPolicy;
use Tests\TestCase;

/** @template TPolicy of AbstractPolicy */
abstract class BasePolicyTestCase extends TestCase
{
    /** @var TPolicy */
    protected AbstractPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = resolve($this->getPolicyClass());
    }

    /**
     * @return class-string<TPolicy>
     */
    abstract protected function getPolicyClass(): string;
}
