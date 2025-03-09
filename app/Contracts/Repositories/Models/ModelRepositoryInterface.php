<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Models;

use App\Contracts\Models\ModelInteractionInterface;
use Illuminate\Database\Eloquent\Model;

interface ModelRepositoryInterface extends ModelInteractionInterface
{
    /**
     * Create a model
     */
    public function create(array $data): Model;
}
