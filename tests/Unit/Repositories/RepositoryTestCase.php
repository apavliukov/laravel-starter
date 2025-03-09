<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\BaseRepository;
use Tests\TestCase;

abstract class RepositoryTestCase extends TestCase
{
    protected BaseRepository $repository;

    /**
     * Get repository object
     */
    abstract protected function getRepository(): BaseRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getRepository();
    }
}
