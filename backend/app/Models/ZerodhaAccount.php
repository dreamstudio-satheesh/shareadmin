<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZerodhaAccount extends Model
{
    protected $fillable = ['name', 'api_key', 'api_secret', 'access_token', 'status'];

    public function pendingOrders()
    {
        return $this->hasMany(PendingOrder::class);
    }

    public function gttOrders()
    {
        return $this->hasMany(GttOrder::class);
    }
}
