<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZerodhaAccount;
use App\Services\ZerodhaApiService;
use Exception;

class ZerodhaAuthController extends Controller
{
    public function redirect($accountId)
    {
        $account = ZerodhaAccount::findOrFail($accountId);

        $kite = new ZerodhaApiService($account->api_key, $account->api_secret);
        $loginUrl = $kite->getLoginUrl();

        session([
            'api_key' => $account->api_key,
            'api_secret' => $account->api_secret,
            'account_id' => $account->id,
        ]);

        return redirect($loginUrl);
    }

    public function callback(Request $request)
    {
        $requestToken = $request->get('request_token');

        $apiKey = session('api_key');
        $apiSecret = session('api_secret');
        $accountId = session('account_id');

        try {
            $kite = new ZerodhaApiService($apiKey, $apiSecret);
            $accessToken = $kite->generateSession($requestToken);

            ZerodhaAccount::where('id', $accountId)->update([
                'access_token' => $accessToken,
            ]);

            return redirect()->route('zerodha_accounts.index')->with('success', 'Access token updated!');
        } catch (Exception $e) {
            return redirect()->route('zerodha_accounts.index')->with('error', 'Token failed: ' . $e->getMessage());
        }
    }
}
