<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

interface StringMatchInterface
{
    public function toString(): string;

    public static function fromString(string $value): ?self;
}
