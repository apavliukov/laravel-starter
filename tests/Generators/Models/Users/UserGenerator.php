<?php

declare(strict_types=1);

namespace Tests\Generators\Models\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Generators\Models\BaseModelGenerator;

/**
 * @method User makeModel(array $data = [])
 * @method User createModel(array $data = [])
 */
final class UserGenerator extends BaseModelGenerator
{
    /**
     * Get model factory
     */
    protected function getFactory(): Factory
    {
        return User::factory();
    }

    public function createUserCollection(array $data = [], int $count = 2): Collection
    {
        return $this->factory->count($count)->create($data);
    }
}
