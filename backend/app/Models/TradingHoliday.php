<?php

// app/Models/TradingHoliday.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'description'
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];
}