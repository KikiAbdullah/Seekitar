<?php

namespace App\Listeners;

use App\Events\OfferAccepted;
use App\Services\Contracts\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOfferAcceptedNotification implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(private readonly NotificationSender $notifier) {}

    public function handle(OfferAccepted $event): void
    {
        $this->notifier->notifyOfferAccepted($event->offer, $event->order);
    }
}
