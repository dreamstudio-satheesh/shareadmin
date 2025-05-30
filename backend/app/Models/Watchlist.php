<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    // guarded properties
    protected $guarded = [];
    protected $table = 'watchlists';
}
