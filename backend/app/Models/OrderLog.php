<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $fillable = ['pending_order_id', 'type', 'product', 'message'];

    public function order()
    {
        return $this->belongsTo(PendingOrder::class, 'pending_order_id');
    }
}
