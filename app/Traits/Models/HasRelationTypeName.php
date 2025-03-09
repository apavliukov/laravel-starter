<?php

declare(strict_types=1);

namespace App\Traits\Models;

use Illuminate\Support\Str;

trait HasRelationTypeName
{
    /**
     * Relation type name for polymorphic relationships
     */
    public static function getRelationTypeName(): string
    {
        $tableName = get_model_table(self::class);

        return Str::singular($tableName);
    }
}
