<?php

namespace App\Enums;

enum PlayerRole: string
{
    case Goalkeeper = 'P';
    case Defender = 'D';
    case Midfielder = 'C';
    case Forward = 'A';
}
