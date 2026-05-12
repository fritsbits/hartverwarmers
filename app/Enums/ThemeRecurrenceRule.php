<?php

namespace App\Enums;

enum ThemeRecurrenceRule: string
{
    case Fixed = 'fixed';
    case NthWeekday = 'nth_weekday';
    case Easter = 'easter';
    case VariableAnnual = 'variable_annual';
    case Lunar = 'lunar';
    case SchoolCalendar = 'school_calendar';
    case OneTimeEvent = 'one_time_event';
}
