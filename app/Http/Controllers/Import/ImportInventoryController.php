<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\InventoryImport;
use App\Imports\ProductImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportInventoryController extends Controller
{
    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            // Use the correct method for queued imports
            Excel::queueImport(new InventoryImport, $request->file('file'));

            return response()->json([
                'status'  => 'queued',
                'message' => 'Inventory import started and is processing in background.',
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to queue import: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function importProduct(Request $request)
    {
        // ✅ Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            // ✅ Queue the Product import
            Excel::queueImport(new ProductImport, $request->file('file'));

            return response()->json([
                'status'  => 'queued',
                'message' => 'Product import started and is processing in the background.',
            ], 202);
        } catch (\Exception $e) {
            // ❌ Handle any errors gracefully
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to queue product import: ' . $e->getMessage(),
            ], 500);
        }
    }
}
