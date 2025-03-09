<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Models\Users;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Models\Users\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Generators\Models\Users\UserGenerator;
use Tests\Unit\Repositories\Models\ModelRepositoryTestCase;

#[Group('central')]
#[Group('models')]
#[Group('users')]
#[Group('repositories')]
#[CoversClass(UserRepository::class)]
/**
 * @property UserRepository $repository
 */
final class UserRepositoryTest extends ModelRepositoryTestCase
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
     * Get repository object
     *
     * @return UserRepository
     */
    protected function getRepository(): BaseRepository
    {
        return new UserRepository;
    }

    public function test_all_action_returns_empty_collection_if_no_users(): void
    {
        $this->modelClass::all()->each(fn ($user): ?bool => $user->delete());

        $this->assertEmpty($this->repository->all());
    }

    public function test_all_action_returns_collection_with_all_users(): void
    {
        $count = 3;
        $this->userGenerator->createUserCollection(count: $count);

        $this->assertEquals($count, $this->repository->all()->count());
    }

    public function test_all_action_returns_collection_with_all_attributes_if_default_parameters_are_set(): void
    {
        $this->userGenerator->createUserCollection();

        $allUsers = $this->repository->all();
        $user = $allUsers->first();

        $this->assertModelHasCorrectAttributes($user);
    }

    public function test_all_action_returns_collection_with_specific_attributes_only(): void
    {
        $this->userGenerator->createUserCollection();

        $columns = ['id', 'name'];
        $allUsers = $this->repository->all($columns);
        $user = $allUsers->first();

        $this->assertModelHasCorrectAttributes($user, $columns);
    }

    public function test_paginate_action_returns_empty_collection_if_no_users(): void
    {
        $this->modelClass::all()->each(fn ($user): ?bool => $user->delete());

        $this->assertEmpty($this->repository->paginate());
    }

    public function test_paginate_action_returns_collection_with_first_page(): void
    {
        $count = 16;
        $this->userGenerator->createUserCollection(count: $count);

        $this->assertEquals(15, $this->repository->paginate()->count());
    }

    public function test_paginate_action_returns_collection_with_specific_amount_of_items(): void
    {
        $perPage = 4;
        $this->userGenerator->createUserCollection(count: $perPage + 1);

        $this->assertEquals($perPage, $this->repository->paginate($perPage)->count());
    }

    public function test_paginate_action_returns_collection_with_all_attributes_if_default_parameters_are_set(): void
    {
        $this->userGenerator->createUserCollection();

        $allUsers = $this->repository->paginate();
        $user = $allUsers->items()[0];

        $this->assertModelHasCorrectAttributes($user);
    }

    public function test_paginate_action_returns_collection_with_specific_attributes_only(): void
    {
        $this->userGenerator->createUserCollection();

        $columns = ['id', 'name'];
        $allUsers = $this->repository->paginate(columns: $columns);
        $user = $allUsers->items()[0];

        $this->assertModelHasCorrectAttributes($user, $columns);
    }

    public function test_find_action_returns_null_if_user_is_deleted(): void
    {
        $user = $this->userGenerator->createModel();
        $userId = $user->id;

        $user->delete();

        $this->assertNull($this->repository->find($userId));
    }

    public function test_find_action_returns_correct_model_if_it_exists(): void
    {
        $user = $this->userGenerator->createModel();
        $retrievedUser = $this->repository->find($user->id);

        $this->assertInstanceOf($user::class, $retrievedUser);
        $this->assertEquals($user->id, $retrievedUser->id);
    }

    public function test_find_action_returns_model_with_all_attributes_if_default_parameters_are_set(): void
    {
        $user = $this->userGenerator->createModel();
        $retrievedUser = $this->repository->find($user->id);

        $this->assertModelHasCorrectAttributes($retrievedUser);
    }

    public function test_find_action_returns_model_with_specific_attributes_only(): void
    {
        $user = $this->userGenerator->createModel();
        $columns = ['id', 'name'];
        $retrievedUser = $this->repository->find($user->id, $columns);

        $this->assertModelHasCorrectAttributes($retrievedUser, $columns);
    }

    public function test_create_action_returns_model(): void
    {
        $data = $this->userGenerator->generateData(onlyFillable: true);
        $user = $this->repository->create($data);

        $this->assertDatabaseHas($user->getTable(), [
            'id' => $user->id,
        ]);
    }

    public function test_update_action_returns_true(): void
    {
        $user = $this->userGenerator->createModel();
        $updatedName = 'Jane Doe';

        $result = $this->repository->update($user, [
            'name' => $updatedName,
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas($user->getTable(), [
            'id' => $user->id,
            'name' => $updatedName,
        ]);
    }

    public function test_update_action_returns_false_if_model_doesnt_exist(): void
    {
        $user = $this->userGenerator->createModel();

        $user->delete();

        $result = $this->repository->update($user, [
            'name' => 'Jane Doe',
        ]);

        $this->assertFalse($result);
    }

    public function test_delete_action_returns_true(): void
    {
        $user = $this->userGenerator->createModel();

        $this->assertTrue($this->repository->delete($user));
        $this->assertDatabaseMissing($user->getTable(), [
            'id' => $user->id,
        ]);
    }

    public function test_delete_action_returns_null_if_model_doesnt_exist(): void
    {
        $user = $this->userGenerator->createModel();

        $user->delete();

        $this->assertNull($this->repository->delete($user));
    }
}
