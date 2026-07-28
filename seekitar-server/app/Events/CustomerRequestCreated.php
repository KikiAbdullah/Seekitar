<?php

namespace App\Events;

use App\Models\CustomerRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CustomerRequest $request) {}
}
