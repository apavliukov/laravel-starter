<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

interface StringMatchInterface
{
    /**
     * Get string match for the specific value
     */
    public function toString(): string;

    /**
     * Get enum value from string match
     */
    public static function fromString(string $value): ?self;
}
