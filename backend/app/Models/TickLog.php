<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TickLog extends Model
{
   protected $fillable = ['instrument_token', 'last_price', 'received_at'];

}
