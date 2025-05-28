<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\Request;
use App\Jobs\ImportInstruments;
use Illuminate\Support\Facades\Artisan;

class InstrumentsController extends Controller
{
    public function index()
    {
        $instruments = Instrument::select('instrument_token', 'exchange', 'tradingsymbol', 'name', 'instrument_type', 'segment')->paginate(50);
        return view('instruments.index', compact('instruments'));
    }

    public function import()
    {
        ImportInstruments::dispatch(); // queued version
        return redirect()->back()->with('success', 'Instruments will Update the instruments table in the background.');
    }
}
