<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminSetting;

class AdminSettingSeeder extends Seeder
{
    public function run()
    {
        AdminSetting::updateOrCreate([], [
            'buy_logic' => 'fixed_percent',
            'buy_percent' => 2.0,
            'stoploss_percent' => 1.0,
            'auto_sell_time' => '15:10:00',
        ]);
    }
}
