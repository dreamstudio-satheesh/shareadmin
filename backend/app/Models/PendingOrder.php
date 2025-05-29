<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    protected $fillable = [
        'zerodha_account_id', 'symbol', 'qty', 'target_percent',
        'ltp_at_upload', 'target_price', 'stoploss_price', 'status',
        'reason', 'executed_price', 'executed_at', 'stoploss_triggered_at','ltp_source'
    ];

    public function account()
    {
        return $this->belongsTo(ZerodhaAccount::class, 'zerodha_account_id');
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }
}
