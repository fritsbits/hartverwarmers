<?php

namespace App\Enums;

enum ActivityDimension: string
{
    case PERSONAL = 'personal';
    case SOCIAL = 'social';
    case COMMUNAL = 'communal';

    public function title(): string
    {
        return config("enum.activitydimension.{$this->value}.title", $this->name);
    }
}
