<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Services\ServiceTestCase;

// TODO move here all common service tests
abstract class ModelServiceTestCase extends ServiceTestCase
{
    /** @var class-string<Model> */
    protected string $modelClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = $this->getModelClass();
    }

    /**
     * Get model class
     */
    abstract protected function getModelClass(): string;

    protected function assertModelHasCorrectAttributes(Model $model, array $columns = []): void
    {
        $columns = $columns === [] ? Schema::getColumnListing($model->getTable()) : $columns;
        $attributes = array_keys($model->getAttributes());

        sort($columns);
        sort($attributes);

        $this->assertEquals($columns, $attributes);
    }
}
