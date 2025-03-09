<?php

declare(strict_types=1);

namespace App\Traits\Enums;

/**
 * @phpstan-ignore trait.unused
 */
trait HasLabels
{
    /**
     * Get all labels
     *
     * @return array<string>
     */
    public static function getLabels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $case): array => [$case->value => $case->label()])
            ->toArray();
    }
}
