<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    use HasFactory;

     protected $fillable = [
        'instrument_token',
        'exchange',
        'tradingsymbol',
        'name',
        'last_price',
        'expiry',
        'strike',
        'instrument_type',
        'segment',
        'lot_size',
        'tick_size',
    ];
}
