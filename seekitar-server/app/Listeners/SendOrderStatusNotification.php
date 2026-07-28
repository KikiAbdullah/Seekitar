<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\Contracts\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(private readonly NotificationSender $notifier) {}

    public function handle(OrderStatusChanged $event): void
    {
        $this->notifier->notifyOrderStatusChanged($event->order);
    }
}
