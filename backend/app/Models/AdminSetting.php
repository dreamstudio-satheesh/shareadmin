<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = ['buy_logic', 'buy_percent', 'stoploss_percent', 'auto_sell_time'];
}

