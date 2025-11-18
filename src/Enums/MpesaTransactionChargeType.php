<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Enums;

enum MpesaTransactionChargeType: string
{
    case BUSINESS = 'business';
    case MOBILE = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::BUSINESS => 'Business Payment',
            self::MOBILE => 'Mobile Payment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
