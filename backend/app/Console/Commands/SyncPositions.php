<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZerodhaAccount;
use App\Models\Position;
use App\Services\ZerodhaApiService;
use Illuminate\Support\Facades\Log;

class SyncPositions extends Command
{
    protected $signature = 'sync:positions';
    protected $description = 'Sync live Zerodha positions for all accounts';

    public function handle()
    {
        if (!isMarketOpen() || !isTradingDay()) {
            $this->info('Market is closed. Skipping SyncPositions.');
            return;
        }

        $accounts = ZerodhaAccount::whereNotNull('access_token')->get();

        foreach ($accounts as $account) {
            $this->info("Syncing positions for Account ID {$account->id}");

            $service = new ZerodhaApiService(
                $account->api_key,
                $account->api_secret,
                $account->access_token
            );

            try {
                $response = $service->get('portfolio/positions');
                $positions = $response['data']['net'] ?? [];

                foreach ($positions as $pos) {
                    $symbol = $pos['tradingsymbol'];
                    $quantity = $pos['quantity'];

                    if ($quantity == 0) continue;

                    Position::updateOrCreate(
                        [
                            'zerodha_account_id' => $account->id,
                            'symbol' => $symbol,
                        ],
                        [
                            'quantity' => $quantity,
                            'average_price' => $pos['average_price'],
                            'last_price' => $pos['last_price'],
                            'pnl' => $pos['pnl'],
                        ]
                    );
                }

                $this->info("✔ Account {$account->id} sync done.");

            } catch (\Throwable $e) {
                Log::error("[SyncPositions] Account {$account->id} failed: {$e->getMessage()}");
                $this->error("✘ Account {$account->id} error.");
            }
        }

        $this->info('✅ All accounts processed.');
    }
}
