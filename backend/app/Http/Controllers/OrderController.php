<?php

namespace App\Http\Controllers;


use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\ZerodhaAccount;
use App\Imports\PendingOrdersImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
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
            $query->whereRaw('1 = 1'); // future logic
        }

        if ($request->filled('account_id') && $request->account_id != 'all') {
            $query->where('zerodha_account_id', $request->account_id);
        }

        $orders = $query->paginate(20);
        $accounts = ZerodhaAccount::pluck('name', 'id');

        return view('orders.index', compact('orders', 'accounts'));
    }

    public function showUploadForm()
    {
        $accounts = ZerodhaAccount::where('status', 'active')->get();
        return view('orders.upload', compact('accounts'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'zerodha_account_id' => 'required|exists:zerodha_accounts,id',
        ]);

        try {
            Excel::import(new PendingOrdersImport($request->zerodha_account_id), $request->file('file'));
            return redirect()->back()->with('success', 'Orders uploaded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }
    }



    public function bulkDelete(Request $request)
    {
        Order::whereIn('id', $request->order_ids)->delete();
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Order::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }



    /**
     * Download a sample Excel file for order upload.
     *
     * @return \Illuminate\Http\Response
     */

    public function downloadSample()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Symbol', 'Target %', 'Qty', 'Product'],
            ['RELIANCE', '2.5', '10', 'MIS'],
            ['INFY', '1.5', '5', 'CNC'],
        ]);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'sample_orders.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return Response::download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
