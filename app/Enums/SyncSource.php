<?php

namespace App\Enums;

enum SyncSource: string
{
    case Lcis = 'lcis';
    case Amis = 'amis';

    public function label(): string
    {
        return __('sync.sources.'.$this->value);
    }
}
