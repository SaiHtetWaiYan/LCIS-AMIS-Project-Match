<?php

namespace App\Enums;

/**
 * Which organisation a user works for. A match needs one confirmation from each of Lcis and Amis.
 */
enum UserSide: string
{
    case Lcis = 'lcis';
    case Amis = 'amis';
    case Admin = 'admin';

    public function label(): string
    {
        return __('users.sides.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $side) {
            $options[$side->value] = $side->label();
        }

        return $options;
    }
}
