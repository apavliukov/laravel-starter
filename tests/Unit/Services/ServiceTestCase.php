<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BaseService;
use Tests\TestCase;

abstract class ServiceTestCase extends TestCase
{
    protected BaseService $service;

    /**
     * Get service object
     */
    abstract protected function getService(): BaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->getService();
    }

    protected function bindMocks(array $bindings): void
    {
        foreach ($bindings as $abstract => $mockSetup) {
            $this->mock($abstract, $mockSetup);
        }

        $this->service = $this->getService();
    }
}
