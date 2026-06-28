<?php

declare(strict_types=1);

namespace App\Authorization\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakePolicyCommand extends Command
{
    protected $signature = 'make:authorization-policy {model : The model class short name (e.g. Post)}';

    protected $description = 'Create a policy extending the authorization AbstractPolicy';

    public function handle(): int
    {
        $model = Str::studly((string) $this->argument('model'));
        $targetPath = app_path("Policies/{$model}Policy.php");

        if (File::exists($targetPath)) {
            $this->error("Policy already exists: {$targetPath}");

            return self::FAILURE;
        }

        $stub = File::get(__DIR__.'/stubs/policy.stub');
        $contents = str_replace('{{ model }}', $model, $stub);

        File::ensureDirectoryExists(app_path('Policies'));
        File::put($targetPath, $contents);

        $this->info("Policy created: {$targetPath}");

        return self::SUCCESS;
    }
}
