<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\ZerodhaAccount;
use App\Services\ZerodhaApiService;
use Illuminate\Console\Command;

class ImportInstruments extends Command
{
    protected $signature = 'instruments:import';
    protected $description = 'Import equity instruments (NSE & BSE) using Zerodha API';

    public function handle(): void
    {
        $account = ZerodhaAccount::whereNotNull('access_token')->first();

        if (! $account) {
            $this->error('No Zerodha account with a valid access token found.');
            return;
        }

        $service = new ZerodhaApiService($account->api_key, $account->api_secret, $account->access_token);
        $this->info("Fetching instruments from Zerodha API...");

        try {
            $csvData = $service->getInstrumentsCsv();
            $lines = explode("\n", $csvData);
            $headers = str_getcsv(array_shift($lines));

            $count = 0;
            foreach (array_chunk($lines, 1000) as $chunk) {
                $upserts = [];

                foreach ($chunk as $line) {
                    $row = str_getcsv($line);
                    if (count($row) !== count($headers)) continue;

                    $data = array_combine($headers, $row);

                    if (
                        isset($data['instrument_type'], $data['exchange']) &&
                        $data['instrument_type'] === 'EQ' &&
                        in_array($data['exchange'], ['NSE', 'BSE'], true)
                    ) {
                        $upserts[] = [
                            'instrument_token' => $data['instrument_token'],
                            'exchange' => $data['exchange'],
                            'tradingsymbol' => $data['tradingsymbol'],
                            'name' => $data['name'] ?? null,
                            'last_price' => $data['last_price'] ?? 0,
                            'expiry' => (!empty($data['expiry']) && $data['expiry'] !== '0000-00-00') ? $data['expiry'] : null,
                            'strike' => $data['strike'] ?? 0,
                            'tick_size' => $data['tick_size'] ?? 0,
                            'lot_size' => $data['lot_size'] ?? 0,
                            'instrument_type' => $data['instrument_type'] ?? null,
                            'segment' => $data['segment'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($upserts)) {
                    Instrument::upsert(
                        $upserts,
                        ['instrument_token'], // Unique constraint
                        [
                            'exchange',
                            'tradingsymbol',
                            'name',
                            'last_price',
                            'expiry',
                            'strike',
                            'tick_size',
                            'lot_size',
                            'instrument_type',
                            'segment',
                            'updated_at'
                        ]
                    );

                    $count += count($upserts);
                }
            }

            $this->info("Imported {$count} equity instruments successfully.");
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }
    }
}
