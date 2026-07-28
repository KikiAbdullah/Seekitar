<?php

namespace App\Events;

use App\Models\Offer;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Offer $offer,
        public readonly Order $order,
    ) {}
}
