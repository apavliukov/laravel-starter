<?php

declare(strict_types=1);

namespace App\Enums\Policies;

use App\Contracts\Enums\HasLabelsInterface;
use App\Contracts\Enums\StringMatchInterface;
use App\Traits\Enums\HasLabels;
use App\Traits\Enums\HasValues;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

enum Role: string implements HasLabelsInterface, StringMatchInterface
{
    use HasLabels;
    use HasValues;

    case ADMIN = 'admin';
    case MEMBER = 'member';

    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string>|string|Translator|Application|null
     */
    public function label(): array|string|Translator|Application|null
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::MEMBER => __('Member'),
        };
    }

    public function layout(): string
    {
        return match ($this) {
            self::ADMIN => 'platform',
            self::MEMBER => 'member',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::MEMBER => 'zinc',
        };
    }
}
