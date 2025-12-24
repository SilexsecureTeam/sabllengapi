<?php

namespace App\Jobs;

use App\Models\EposnowSyncLog;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEposStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public int $productId;
    public int $quantity;
    public string $eposProductId;
    public string $orderReference;
    public string $paymentMethod;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(
        int $orderId,
        string $eposProductId,
        int $productId,
        int $quantity,
        string $orderReference,
        string $paymentMethod = 'pos'   
    ) {
        $this->orderId        = $orderId;
        $this->eposProductId  = (string) $eposProductId;
        $this->productId      = $productId;
        $this->quantity       = $quantity;
        $this->orderReference = $orderReference;
        $this->paymentMethod  = $paymentMethod; // ✅ now initialized
    }

    public function handle(): void
    {
        $baseUrl    = rtrim(config('services.eposnow.api_url'), '/');
        $authToken  = config('services.eposnow.auth_token');
        $locationId = config('services.eposnow.location_id');

        if (empty($authToken) || empty($locationId)) {
            $this->logFailure('Missing EPOS Now auth token or location ID');
            return;
        }

        // 1️⃣ Get local stock
        $currentStock = DB::table('inventories')
            ->where('product_id', $this->productId)
            ->value('current_stock');

        if ($currentStock === null) {
            $this->logFailure('No current stock in your local table');
            return;
        }

        // 2️⃣ EPOS Now expects negative quantity for stock reduction
        $adjustmentQty = -abs($this->quantity);

        // 3️⃣ Build payload
        $payload = [
            'ProductId'      => (int) $this->eposProductId,
            'LocationId'     => (int) $locationId,
            'AdjustmentType' => 'Sale',
            'Quantity'       => $adjustmentQty,
            'Reference'      => $this->orderReference,

        ];

        try {
            // 4️⃣ Send stock adjustment to EPOS Now
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$authToken}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->post("{$baseUrl}/Inventory/AdjustStock", $payload);

            if (! $response->successful()) {
                $this->logFailure(
                    'EPOS API error',
                    $response->json() ?? $response->body()
                );

                throw new \RuntimeException(
                    "EPOS Now stock sync failed ({$response->status()})"
                );
            }
            // Log::info('EPOS STOCK JOB HIT', [
            //     'order_id' => $this->orderId,
            //     'product_id' => $this->productId,
            //     'qty' => $this->quantity,
            // ]);

            // 5️⃣ Update local stock safely (optimistic lock)
            $newStock = max(0, $currentStock - $this->quantity);

            $updated = DB::table('inventories')
                ->where('product_id', $this->productId)
                ->where('current_stock', $currentStock)
                ->update([
                    'current_stock' => $newStock,
                    'updated_at'   => now(),
                ]);

            if ($updated === 0) {
                $this->logFailure('Stock changed concurrently, update skipped');
                return;
            }

            // 6️⃣ Success log
            EposnowSyncLog::create([
                'order_id'     => $this->orderId,
                'product_id'   => $this->productId,
                'sync_type'    => 'stock_update',
                'status'       => 'success',
                'quantity'     => $this->quantity,
                'old_stock'    => $currentStock,
                'new_stock'    => $newStock,
                'response'     => $response->json(),
                'payment_method'    => $this->paymentMethod,
                    'synced_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EPOS Now Stock Sync Exception', [
                'order_id'   => $this->orderId,
                'product_id' => $this->productId,
                'message'    => $e->getMessage(),
            ]);

            throw $e; // allow queue retry
        }
    }

    /**
     * Log a failed sync attempt
     */
    protected function logFailure(string $message, $response = null): void
    {
        EposnowSyncLog::create([
            'order_id'     => $this->orderId,
            'product_id'   => $this->productId,
            'sync_type'    => 'stock_update',
            'status'       => 'failed',
            'quantity'     => $this->quantity,
            'error_message' => $message,
            'response'     => $response,
            'payment_method' => $this->paymentMethod ?? 'pos',
            'synced_at'    => now(),
        ]);
    }
}
