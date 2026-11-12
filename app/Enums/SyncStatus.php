<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return __('sync.statuses.'.$this->value);
    }
}
