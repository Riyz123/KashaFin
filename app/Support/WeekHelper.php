<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class WeekHelper
{
    public static function startOfWeek(User $user, Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek($user->settings->weekStartCarbonConstant());
    }
}
