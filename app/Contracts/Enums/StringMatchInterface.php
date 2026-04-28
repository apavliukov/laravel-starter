<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

interface StringMatchInterface
{
    public static function fromString(string $value): ?self;

    public function toString(): string;
}
