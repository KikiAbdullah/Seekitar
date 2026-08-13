<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('offers:purge-expired')->hourly();
Schedule::command('requests:purge-expired')->hourly();
Schedule::command('orders:cleanup-pending')->daily();
Schedule::command('privacy:retention')->daily();
Schedule::command('uploads:purge-temporary')->hourly();
