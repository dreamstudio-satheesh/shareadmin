<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $table = 'pending_orders';
    protected $primaryKey = 'id';

    protected $fillable = [
        'zerodha_account_id',
        'symbol',
        'qty',
        'target_percent',
        'ltp_at_upload',
        'target_price',
        'stoploss_price',
        'status',
        'reason',
        'executed_price',
        'executed_at',
        'stoploss_triggered_at',
        'ltp_source',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'executed_at',
        'stoploss_triggered_at',
    ];

    public function account()
    {
        return $this->belongsTo(ZerodhaAccount::class, 'zerodha_account_id');
    }
}
