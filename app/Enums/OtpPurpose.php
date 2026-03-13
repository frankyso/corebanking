<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Registration = 'registration';
    case Transaction = 'transaction';
    case PinReset = 'pin_reset';
}
