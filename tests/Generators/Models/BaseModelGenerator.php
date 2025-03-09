<?php

declare(strict_types=1);

namespace Tests\Generators\Models;

use App\Services\Models\BaseModelInteractionService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Tests\Generators\BaseGenerator;

abstract class BaseModelGenerator extends BaseGenerator
{
    protected Factory $factory;

    public function __construct(protected BaseModelInteractionService $baseModelInteractionService)
    {
        $this->factory = $this->getFactory();
    }

    /**
     * Get model factory
     */
    abstract protected function getFactory(): Factory;

    public function getBaseData(): array
    {
        return $this->factory->definition();
    }

    public function getFillableData(): array
    {
        $modelClass = $this->factory->modelName();
        $fillableAttributes = (new $modelClass)->getFillable();

        return Arr::only(
            $this->getBaseData(),
            $fillableAttributes
        );
    }

    public function generateData(array $data = [], bool $onlyFillable = false): array
    {
        $baseData = $onlyFillable ? $this->getFillableData() : $this->getBaseData();

        return array_merge($baseData, $data);
    }

    public function generateDataFromModel(Model $model, array $data = [], bool $onlyFillable = false): array
    {
        $modelData = array_merge($model->getAttributes(), $data);

        return $this->generateData($modelData, $onlyFillable);
    }

    public function makeModel(array $data = []): Model
    {
        return $this->factory->make($data);
    }

    public function createModel(array $data = []): Model
    {
        return $this->factory->create($data);
    }
}
