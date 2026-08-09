<?php

namespace App\Enums;

enum AuctionCallStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case Unsold = 'unsold';
}
