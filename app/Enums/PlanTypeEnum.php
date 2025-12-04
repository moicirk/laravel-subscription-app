<?php

namespace App\Enums;

enum PlanTypeEnum: string
{
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
