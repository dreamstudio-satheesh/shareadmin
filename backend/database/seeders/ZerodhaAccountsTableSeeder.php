<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZerodhaAccountsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('zerodha_accounts')->insert([
            [
                'name' => 'Satheesh',
                'api_key' => 'fwl8jd4xcan3r27d',
                'api_secret' => 'gtdf2vklrl65wjao04tykk2rxpc0u0oa',
                'access_token' => 'WeaP2sLqLqG29hk73vHvKZWyo02wlFol',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
