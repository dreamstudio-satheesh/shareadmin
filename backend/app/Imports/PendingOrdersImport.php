<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\PendingOrder;
use App\Services\LTPService;
use App\Traits\TradeHelpers;
use App\Models\ZerodhaAccount;
use App\Services\WatchlistManager;
use Illuminate\Support\Collection;
use App\Services\ZerodhaApiService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class PendingOrdersImport implements ToCollection
{
    use TradeHelpers;

    protected int $accountId;
    protected ZerodhaApiService $api;
    protected LTPService $ltpService;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
        $this->ltpService = new LTPService();

        $account = ZerodhaAccount::whereNotNull('access_token')->first();
        if (! $account || ! $account->access_token) {
            throw new \Exception('Missing access token. Please login to Zerodha before uploading orders.');
        }

        if ($account->token_expires_at && Carbon::now()->greaterThan(Carbon::parse($account->token_expires_at))) {
            throw new \Exception('Access token expired. Please re-login to Zerodha.');
        }

        $this->api = new ZerodhaApiService($account->api_key, $account->api_secret, $account->access_token);
    }

    public function collection(Collection $rows): void
    {
        $rows->shift(); // Skip header row

        foreach ($rows as $index => $row) {
            try {
                $symbolInput = trim($row[0] ?? '');
                $targetPercent = floatval($row[1] ?? 0);
                $qty = floatval($row[2] ?? 0);
                $product = strtoupper(trim($row[3] ?? ''));

                if (!$symbolInput || $qty <= 0 || !in_array($product, ['MIS', 'CNC'])) {
                    Log::warning("Row $index: Invalid or missing data.");
                    continue;
                }

                [$exchange, $symbol] = $this->parseSymbol($symbolInput);
                $fullSymbol = "$exchange:$symbol";

                // Ensure symbol is in watchlist
                WatchlistManager::ensureExists($fullSymbol);

                // Skip duplicate pending orders
                if (PendingOrder::where('zerodha_account_id', $this->accountId)
                    ->where('symbol', $fullSymbol)
                    ->where('status', 'pending')
                    ->exists()
                ) {
                    Log::info("Row $index: Duplicate order for $fullSymbol, skipped.");
                    continue;
                }

                $token = getTokenFromSymbol($fullSymbol);
                if (!$token) {
                    Log::warning("Row $index: Token not found for $fullSymbol.");
                    continue;
                }

                try {
                    [$ltp, $source] = $this->ltpService->get($exchange, $symbol, $token, $fullSymbol, $this->api, $index);
                    if (!$ltp) {
                        Log::warning("Row $index: LTP not found for $fullSymbol.");
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::error("Row $index: Failed to fetch LTP for $fullSymbol. Error: " . $e->getMessage());
                    continue;
                }

                $tickSize = $this->getTickSize($exchange, $symbol);
                $targetPrice = $this->snapToTick($ltp + ($ltp * $targetPercent / 100), $tickSize, true);
                $stoplossPrice = $this->snapToTick($ltp - ($ltp * getStoplossPercent() / 100), $tickSize, false);

                PendingOrder::create([
                    'zerodha_account_id' => $this->accountId,
                    'symbol' => $fullSymbol,
                    'qty' => $qty,
                    'target_percent' => $targetPercent,
                    'ltp_at_upload' => $ltp,
                    'target_price' => $targetPrice,
                    'stoploss_price' => $stoplossPrice,
                    'product' => $product,
                    'ltp_source' => $source,
                ]);

                Log::info("Row $index: Imported $fullSymbol with LTP $ltp from $source.");
            } catch (\Throwable $e) {
                Log::error("Row $index: Failed for symbol '" . ($row[0] ?? '') . "'. Error: " . $e->getMessage());
            }
        }
    }
}
