<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Instrument;
use Illuminate\Support\Facades\Http;

class ImportInstruments extends Command
{
    protected $signature = 'instruments:import';
    protected $description = 'Import instruments from Kite API to the instruments table';

    public function handle(): void
    {
        $this->info('Fetching instrument data from Kite API...');
        $response = Http::timeout(20)->get('https://api.kite.trade/instruments');

        if (!$response->ok()) {
            $this->error('Failed to fetch instrument data.');
            return;
        }

        $csvData = $response->body();
        $lines = explode("\n", $csvData);
        $headers = str_getcsv(array_shift($lines));

        $count = 0;
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) !== count($headers)) continue;

            $data = array_combine($headers, $row);

            Instrument::updateOrCreate(
                ['instrument_token' => $data['instrument_token']],
                [
                    'exchange' => $data['exchange'],
                    'tradingsymbol' => $data['tradingsymbol'],
                    'name' => $data['name'] ?? null,
                    'last_price' => $data['last_price'] ?? 0,
                    'expiry' => $data['expiry'] ?? null,
                    'strike' => $data['strike'] ?? 0,
                    'tick_size' => $data['tick_size'] ?? 0,
                    'lot_size' => $data['lot_size'] ?? 0,
                    'instrument_type' => $data['instrument_type'] ?? null,
                    'segment' => $data['segment'] ?? null,
                ]
            );
            $count++;
        }

        $this->info("Imported {$count} instruments successfully.");
    }
}