<?php

namespace App\Enums;

enum UserPaymentMethodTypeEnum: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case STRIPE = 'stripe';
}
