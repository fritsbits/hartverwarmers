<?php

namespace App\Enums;

enum Guidance: string
{
    case ACTIVE_INDEPENDENT = 'active_independent';
    case ACTIVE_PARTICIPATOR = 'active_participator';
    case ACTIVE_PARTICIPATOR_DEPENDENT = 'active_participator_dependent';
    case PASSIVE_PARTICIPATOR = 'passive_participator';
    case PASSIVE_DEPENDENT = 'passive_dependent';

    public function title(): string
    {
        return config("enum.guidance.{$this->value}.title", $this->name);
    }

    public function description(): string
    {
        return config("enum.guidance.{$this->value}.description", '');
    }
}
