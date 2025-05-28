<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;

class ImportInstruments implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        Artisan::call('instruments:import');
    }
}
