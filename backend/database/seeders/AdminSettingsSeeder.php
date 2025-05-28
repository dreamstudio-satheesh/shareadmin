<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_settings')->insert([
            'buy_logic' => 'fixed_percent',
            'buy_percent' => 1.00,
            'stoploss_percent' => 0.50,
            'auto_sell_time' => '15:20:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
