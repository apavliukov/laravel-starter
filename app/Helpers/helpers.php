<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

if (! function_exists('get_model_table')) {
    /**
     * @param class-string<Model> $modelClass
     *
     * @return string
     */
    function get_model_table(string $modelClass): string
    {
        if (! class_exists($modelClass)) {
            return '';
        }

        return $modelClass::newModelInstance()->getTable();
    }
}
