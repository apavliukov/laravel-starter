<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Models;

use App\Repositories\Models\BaseModelRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Repositories\RepositoryTestCase;

/**
 * @property BaseModelRepository $repository
 */
// TODO move here all common repository tests
abstract class ModelRepositoryTestCase extends RepositoryTestCase
{
    /** @var class-string<Model> */
    protected string $modelClass;

    /**
     * Get model class
     */
    abstract protected function getModelClass(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = $this->getModelClass();
    }

    protected function assertModelHasCorrectAttributes(Model $model, array $columns = []): void
    {
        $columns = $columns === [] ? Schema::getColumnListing($model->getTable()) : $columns;
        $attributes = array_keys($model->getAttributes());

        sort($columns);
        sort($attributes);

        $this->assertEquals($columns, $attributes);
    }
}
