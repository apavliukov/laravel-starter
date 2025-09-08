<?php

declare(strict_types=1);

namespace App\Contracts\Services\Models;

use App\Contracts\Models\ModelInteractionInterface;
use Illuminate\Database\Eloquent\Model;

interface ModelInteractionServiceInterface extends ModelInteractionInterface
{
    public function create(array $data): ?Model;
}
