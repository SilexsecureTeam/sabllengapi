<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SyncEposStockJob;
use App\Models\EposnowSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EposnowSyncLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = EposnowSyncLog::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->order_id, fn($q) => $q->where('order_id', $request->order_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    public function show($id)
    {
        $log = EposnowSyncLog::with(['order', 'product'])->findOrFail($id);
        return response()->json($log);
    }

    public function retry($id)
    {
        $log = EposnowSyncLog::with(['order', 'product'])->findOrFail($id);

        if ($log->status !== 'failed') {
            return response()->json(['error' => 'Only failed logs can be retried'], 400);
        }

        if (empty($log->product->eposnow_product_id)) {
            return response()->json(['error' => 'Product missing EPOSNOW product id'], 400);
        }

        // Dispatch job using the same signature as the job constructor
        SyncEposStockJob::dispatch(
            orderId: $log->order_id,
            eposProductId: $log->product->eposnow_product_id,
            productId: $log->product_id,
            quantity: $log->quantity ?? 0,
            orderReference: $log->order->order_reference ?? $log->order_id
        );

        return response()->json(['message' => 'Retry queued successfully']);
    }

    public function logsByOrder($orderId)
    {
        $logs = EposnowSyncLog::where('order_id', $orderId)->latest()->get();
        return response()->json($logs);
    }
}
