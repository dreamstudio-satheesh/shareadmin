<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GttOrder extends Model
{
    protected $fillable = [
        'zerodha_account_id', 'symbol', 'trigger_price', 'target_price', 'status'
    ];

    public function account()
    {
        return $this->belongsTo(ZerodhaAccount::class, 'zerodha_account_id');
    }
}
