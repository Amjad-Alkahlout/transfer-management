<?php
namespace App\Enums;

enum FeeMode: string
{
    case INCLUDED = 'included';
    case EXCLUDED = 'excluded';
}
