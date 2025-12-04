<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELED = 'canceled';
}
