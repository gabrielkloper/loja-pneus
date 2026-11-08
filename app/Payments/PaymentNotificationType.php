<?php

namespace App\Payments;

enum PaymentNotificationType: string
{
    case Paid = 'paid';
    case Failed = 'failed';
}
