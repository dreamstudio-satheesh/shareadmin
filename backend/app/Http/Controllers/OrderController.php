<?php

namespace App\Http\Controllers;


use App\Models\ZerodhaAccount;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PendingOrdersImport;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
{
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

        Excel::import(new PendingOrdersImport($request->zerodha_account_id), $request->file('file'));

        return redirect()->back()->with('success', 'Orders uploaded successfully.');
    }



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
