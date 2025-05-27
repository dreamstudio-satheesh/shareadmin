<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZerodhaAccount;
use Illuminate\Support\Facades\Artisan;

class ZerodhaAccountController extends Controller
{
    public function index()
    {
        // return ZerodhaAccount::get();
        return view('zerodha_accounts.index', [
            'accounts' => ZerodhaAccount::all(),
        ]);
    }

    public function create()
    {
        return view('zerodha_accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'access_token' => 'nullable|string',
        ]);
        ZerodhaAccount::create($request->only('name', 'api_key', 'api_secret', 'access_token'));
        return redirect()->route('zerodha_accounts.index')->with('success', 'Account added.');
    }

    public function edit(ZerodhaAccount $zerodha_account)
    {
        return view('zerodha_accounts.edit', compact('zerodha_account'));
    }

    public function update(Request $request, ZerodhaAccount $zerodha_account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'access_token' => 'nullable|string',
        ]);
        $zerodha_account->update($request->only('name', 'api_key', 'api_secret', 'access_token'));
        return redirect()->route('zerodha_accounts.index')->with('success', 'Account updated.');
    }

    public function destroy(ZerodhaAccount $zerodha_account)
    {
        $zerodha_account->delete();
        return redirect()->route('zerodha_accounts.index')->with('success', 'Account deleted.');
    }

    public function updateToken(Request $request, ZerodhaAccount $zerodha_account)
    {
        $request->validate(['access_token' => 'required']);
        $zerodha_account->update(['access_token' => $request->access_token]);
        return back()->with('success', 'Access token updated.');
    }

    public function checkNow()
    {
        Artisan::call('zerodha:check-tokens');
        return back()->with('success', '✅ Zerodha token check executed.');
    }
}
