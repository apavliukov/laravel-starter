<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Models\Users;

use App\Models\User;
use App\Repositories\Models\Users\UserRepository;
use App\Services\Models\Users\UserInteractionService;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Generators\Models\Users\UserGenerator;
use Tests\Unit\Services\Models\ModelServiceTestCase;

#[Group('central')]
#[Group('models')]
#[Group('users')]
#[Group('services')]
#[CoversClass(UserInteractionService::class)]
/**
 * @property UserInteractionService $service
 */
final class UserInteractionServiceTest extends ModelServiceTestCase
{
    protected UserGenerator $userGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userGenerator = resolve(UserGenerator::class);
    }

    /**
     * Get model class
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Get service object
     */
    protected function getService(): UserInteractionService
    {
        return resolve(UserInteractionService::class);
    }

    public function bindUserRepository(Closure $closure): void
    {
        $this->bindMocks([
            UserRepository::class => $closure,
        ]);
    }

    public function test_all_action_returns_correct_collection(): void
    {
        $users = $this->userGenerator->createUserCollection();

        $this->bindUserRepository(static function (MockInterface $mock) use ($users): void {
            $mock->shouldReceive('all')
                ->once()
                ->andReturn($users);
        });

        $this->assertEquals($users->pluck('id')->toArray(), $this->service->all()->pluck('id')->toArray());
    }

    public function test_paginate_action_returns_correct_collection(): void
    {
        $usersCount = 5;
        $users = $this->userGenerator->createUserCollection(count: $usersCount);

        $this->bindUserRepository(static function (MockInterface $mock) use ($users): void {
            $mock->shouldReceive('paginate')
                ->once()
                ->andReturn(new LengthAwarePaginator($users, $users->count(), 15));
        });

        $this->assertEquals($usersCount, $this->service->paginate()->count());
    }

    public function test_find_action_returns_correct_model(): void
    {
        $user = $this->userGenerator->createModel();

        $this->bindUserRepository(static function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('find')
                ->once()
                ->andReturn($user);
        });

        $this->assertEquals($user->id, $this->service->find($user->id)->id);
    }

    public function test_create_action_returns_null_if_empty_data(): void
    {
        $this->assertNull($this->service->create([]));
    }

    public function test_create_action_returns_model(): void
    {
        $name = 'John Doe';
        $data = $this->userGenerator->generateData([
            'name' => $name,
        ], onlyFillable: true);

        $this->bindUserRepository(function (MockInterface $mock) use ($data): void {
            $mock->shouldReceive('create')
                ->with($data)
                ->once()
                ->andReturn($this->userGenerator->makeModel($data));
        });

        $user = $this->service->create($data);

        $this->assertInstanceOf($this->modelClass, $user);
        $this->assertEquals($name, $user->name);
    }

    public function test_update_action_returns_true(): void
    {
        $user = $this->userGenerator->createModel();
        $data = [
            'name' => 'Jane Doe',
        ];

        $this->bindUserRepository(static function (MockInterface $mock) use ($user, $data): void {
            $mock->shouldReceive('update')
                ->with($user, $data)
                ->once()
                ->andReturnTrue();
        });

        $this->assertTrue($this->service->update($user, $data));
    }

    public function test_delete_action_returns_true(): void
    {
        $user = $this->userGenerator->createModel();

        $this->bindUserRepository(static function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('delete')
                ->with($user)
                ->once()
                ->andReturnTrue();
        });

        $this->assertTrue($this->service->delete($user));
    }
}
