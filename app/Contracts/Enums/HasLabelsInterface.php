<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

interface HasLabelsInterface
{
    /**
     * Get label for specific level
     *
     * @return array<string>|string|Translator|Application|null
     */
    public function label(): array|string|Translator|Application|null;

    /**
     * Get all labels
     *
     * @return array<string>
     */
    public static function getLabels(): array;
}
