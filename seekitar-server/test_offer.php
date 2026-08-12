<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Store;
use App\Models\User;
use App\Enums\OfferStatus;

$req = CustomerRequest::factory()->create();

// Use a simple counter to ensure unique phone numbers
static $phoneCounter = 0;
$phone = '6289' . (time() * 10000 + (++$phoneCounter % 10000));

$u = User::factory()->create(['phone' => $phone]);
$store = Store::factory()->create(['user_id' => $u->id]);

echo "Request: {$req->id} Store: {$store->id}\n";

// Create first offer (pending)
$o1 = Offer::create([
    'request_id' => $req->id,
    'store_id' => $store->id,
    'price' => 100000,
    'notes' => 'first',
    'status' => OfferStatus::Pending,
    'expires_at' => now()->subHour(),
    'estimation_time' => 2,
]);
echo "Offer 1 created: {$o1->id} status={$o1->status->value}\n";

// Purge job runs - rejects it
$o1->status = OfferStatus::Rejected;
$o1->save();
echo "Offer 1 rejected: {$o1->status->value}\n";

// Try to create new offer from same store
try {
    $o2 = Offer::create([
        'request_id' => $req->id,
        'store_id' => $store->id,
        'price' => 90000,
        'notes' => 'second',
        'status' => OfferStatus::Pending,
        'expires_at' => now()->addHours(48),
        'estimation_time' => 2,
    ]);
    echo "Offer 2 created: {$o2->id} status={$o2->status->value}\n";
    echo "SUCCESS: Re-submission after rejection works!\n";
} catch (\Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}

$req->forceDelete();