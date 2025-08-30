<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Enums;

/**
 *
 */
enum IdentificationType: string
{
    case NATIONAL_ID     = '01';
    case MILITARY_ID     = '02';
    case ALIEN_ID        = '03';
    case DIPLOMATIC_ID   = '04';
    case PASSPORT        = '05';

    /**
     * Get the code value as a string.
     */
    public function code(): string
    {
        return $this->value;
    }

    /**
     * Get an enum case from its case name (e.g., 'NATIONAL_ID').
     *
     * @param string $name
     * @return IdentificationType|null
     */
    public static function fromName(string $name): ?self
    {
        $normalized = strtoupper(trim($name));

        foreach (self::cases() as $case) {
            if ($case->name === $normalized) {
                return $case;
            }
        }

        return self::NATIONAL_ID;
    }

}