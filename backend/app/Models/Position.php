<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'zerodha_account_id',
        'symbol',
        'quantity',
        'average_price',
        'last_price',
        'pnl',
    ];

    public function account()
    {
        return $this->belongsTo(ZerodhaAccount::class, 'zerodha_account_id');
    }
}
