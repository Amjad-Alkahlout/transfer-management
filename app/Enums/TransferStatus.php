<?php
namespace App\Enums;

enum TransferStatus: string
{
case PENDING_PRICING = 'pending_pricing';
case AWAITING_APPROVAL = 'awaiting_approval';
case APPROVED = 'approved';
case COMPLETED = 'completed';
case CANCELLED = 'cancelled';
}
