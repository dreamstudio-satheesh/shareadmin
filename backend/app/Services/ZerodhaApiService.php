<?php


namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ZerodhaApiService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected ?string $accessToken;

    public function __construct(string $apiKey, string $apiSecret, ?string $accessToken = null)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
    }

    public function getLoginUrl(): string
    {
        return "https://kite.zerodha.com/connect/login?v=3&api_key={$this->apiKey}";
    }

    public function generateSession(string $requestToken): string
    {
        $response = Http::asForm()->post('https://api.kite.trade/session/token', [
            'api_key'      => $this->apiKey,
            'request_token' => $requestToken,
            'checksum'     => $this->generateChecksum($requestToken),
        ]);

        if ($response->successful()) {
            return $response['data']['access_token'] ?? throw new \Exception('Missing access token');
        }

        throw new \Exception('Zerodha token generation failed: ' . $response->body());
    }

    protected function generateChecksum(string $requestToken): string
    {
        return hash("sha256", $this->apiKey . $requestToken . $this->apiSecret);
    }

    protected function request()
    {
        return Http::withHeaders([
            'X-Kite-Version' => '3',
            'Authorization' => 'token ' . $this->apiKey . ':' . $this->accessToken,
        ]);
    }

    // 🔹 GET user profile
    public function getProfile()
    {
        return $this->request()->get('https://api.kite.trade/user/profile')->json();
    }

    // 🔹 GET positions
    public function getPositions()
    {
        return $this->request()->get('https://api.kite.trade/portfolio/positions')->json();
    }

    // 🔹 GET holdings
    public function getHoldings()
    {
        return $this->request()->get('https://api.kite.trade/portfolio/holdings')->json();
    }

    // 🔹 GET orders
    public function getOrders()
    {
        return $this->request()->get('https://api.kite.trade/orders')->json();
    }

    // 🔹 Place a regular order
    public function placeOrder(array $params)
    {
        $response = $this->request()->asForm()->post('https://api.kite.trade/orders/regular', $params);
        if ($response->successful()) {
            return $response->json();
        }
        throw new \Exception('Order failed: ' . $response->body());
    }

    // 🔹 Cancel order
    public function cancelOrder(string $orderId)
    {
        return $this->request()->delete("https://api.kite.trade/orders/regular/{$orderId}")->json();
    }

    // 🔹 Get LTP
    public function getLTP(array $instruments)
    {
        $query = implode('&', array_map(fn($i) => 'i=' . urlencode($i), $instruments));

        $response = $this->request()->get("https://api.kite.trade/quote/ltp?$query");

        if ($response->failed()) {
            Log::error("getLTP failed: " . $response->body());
            throw new \Exception("LTP API request failed");
        }

        $json = $response->json();

        if (!isset($json['data']) || empty($json['data'])) {
            Log::warning("getLTP returned empty data for: " . implode(', ', $instruments));
        }

        return $json;
    }


    public function get(string $endpoint)
    {
        $response = Http::withHeaders([
            'Authorization' => 'token ' . $this->apiKey . ':' . $this->accessToken,
        ])->get("https://api.kite.trade/$endpoint");

        $response->throw(); // will raise exception on non-200
        return $response->json();
    }





    // 🔹 Get instruments data
    public function getInstrumentsCsv(): string
    {
        $response = $this->request()->get('https://api.kite.trade/instruments');

        if ($response->failed()) {
            throw new \Exception('Failed to fetch instruments CSV: ' . $response->body());
        }

        return $response->body();
    }
}
