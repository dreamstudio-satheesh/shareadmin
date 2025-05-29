<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    public function edit()
    {
        $settings = DB::table('admin_settings')->first();
        return view('settings.edit', compact('settings'));
    }

    /**
     * Update admin settings.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function update(Request $request)
    {
        $request->validate([
            'buy_logic' => 'required|in:fixed_percent,offset_ltp',
            'buy_percent' => 'required|numeric|min:0',
            'stoploss_percent' => 'required|numeric|min:0',
            'auto_sell_time' => 'nullable|date_format:H:i',
        ]);

        DB::table('admin_settings')->update($request->only([
            'buy_logic',
            'buy_percent',
            'stoploss_percent',
            'auto_sell_time',
        ]) + ['updated_at' => now()]);

        return back()->with('success', 'Settings updated.');
    }

    public function logs()
    {
        $logs = \App\Models\OrderLog::latest()->paginate(50);
        return view('orders.logs', compact('logs'));
    }

    public function cronLogs()
    {
        $logs = \App\Models\CronLog::latest()->paginate(50);
        return view('logs.cron', compact('logs'));
    }
}
