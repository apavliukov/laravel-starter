<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\Models\ModelRepositoryInterface;
use App\Repositories\Models\Users\UserRepository;
use App\Services\Models\BaseModelInteractionService;
use App\Services\Models\Users\UserInteractionService;
use Illuminate\Support\ServiceProvider;
use Tests\Generators\Models\Users\UserGenerator;

class ModelRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->bindModelsRepositories();
    }

    protected function bindModelsRepositories(): void
    {
        // Services
        $this->app->when(UserInteractionService::class)
            ->needs(ModelRepositoryInterface::class)
            ->give(UserRepository::class);

        // Generators
        $this->app->when(UserGenerator::class)
            ->needs(BaseModelInteractionService::class)
            ->give(UserInteractionService::class);
    }
}
