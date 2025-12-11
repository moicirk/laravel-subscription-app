<?php

namespace App\Enums;

enum UsageMetricTypeEnum: string
{
    case REGISTER = 'register';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case SUBSCRIBE = 'subscribe';
    case UNSUBSCRIBE = 'unsubscribe';
}
