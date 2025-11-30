<?php

namespace App\Jobs;

use App\Models\EposnowSyncLog;
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
    public int $productId;        // local product id
    public string $eposProductId; // EPOSNOW product id (string or int depending on API)
    public int $quantity;
    public string $orderReference;

    public int $tries = 3;
    public int $backoff = 15; // seconds

    public function __construct(int $orderId, string $eposProductId, int $productId, int $quantity, string $orderReference)
    {
        $this->orderId = $orderId;
        $this->eposProductId = (string) $eposProductId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->orderReference = $orderReference;
    }

    public function handle()
    {
        $eposApiUrl = rtrim(config('services.eposnow.api_url', ''), '/');
        $eposApiKey = config('services.eposnow.api_key');

        if (empty($eposApiUrl) || empty($eposApiKey)) {
            Log::error('EPOSNOW config missing');
            $this->createLog('failed', null, 'EPOSNOW config missing');
            return;
        }

        // 1) Read current local stock
        $currentStock = DB::table('product_stocks')
            ->where('product_id', $this->productId)
            ->value('CurrentStock');

        if ($currentStock === null) {
            $this->createLog('failed', null, 'Local product_stock row not found');
            return;
        }

        $newStock = $currentStock - $this->quantity;
        if ($newStock < 0) {
            $newStock = 0; // or decide to allow negative; here we clamp to 0
        }

        // 2) Prepare EPOS payload
        $payload = [
            'ProductId' => $this->eposProductId,
            'StockLevel' => $newStock,
            'Reason' => 'Online Sale',
            'OrderReference' => $this->orderReference,
        ];

        // 3) Call EPOS API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($eposApiKey . ':x'),
                'Content-Type'  => 'application/json',
            ])->timeout(10)->post("{$eposApiUrl}/api/v4/product/{$this->eposProductId}/stock", $payload);

            $ok = $response->successful();
            $body = $response->body();

            if (!$ok) {
                $this->createLog('failed', $body, "EPOS API returned status {$response->status()}");
                // let job be retried
                throw new \RuntimeException("EPOS API failed: HTTP {$response->status()}");
            }

            // 4) Try to atomically update local stock using optimistic check
            $updated = DB::table('product_stocks')
                ->where('product_id', $this->productId)
                ->where('CurrentStock', $currentStock) // safe-guard against race
                ->update(['CurrentStock' => $newStock, 'updated_at' => now()]);

            if ($updated === 0) {
                // Optimistic lock failed: another process changed stock. Log and create a failed log so you can investigate.
                $this->createLog('failed', $body, 'Local stock optimistic update failed (concurrent modification)');
                throw new \RuntimeException('Local stock update conflict');
            }

            // 5) Success
            $this->createLog('success', $body, null, $currentStock, $newStock);
        } catch (\Throwable $e) {
            Log::error('SyncEposStockJob error', [
                'order' => $this->orderId,
                'product' => $this->productId,
                'epos_product' => $this->eposProductId,
                'error' => $e->getMessage()
            ]);

            // rethrow to let Laravel handle the retry/backoff according to $tries
            throw $e;
        }
    }

    protected function createLog(string $status, ?string $response = null, ?string $error = null, ?int $oldStock = null, ?int $newStock = null)
    {
        EposnowSyncLog::create([
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'sync_type' => 'stock_update',
            'status' => $status,
            'quantity' => $this->quantity,
            'response' => $response,
            'error_message' => $error,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'synced_at' => now(),
        ]);
    }
}
