<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with('account')->latest();

        // Optional filters
        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }

        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type != 'all') {
            // Only BUY supported now
            $query->whereRaw('1 = 1'); // placeholder
        }

        $orders = $query->paginate(20);

        return view('orders.index', compact('orders'));
    }
}
