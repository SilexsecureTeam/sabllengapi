<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ProductImport implements ToCollection, WithHeadingRow, ShouldQueue, WithChunkReading, WithBatchInserts
{
    use Importable;

    public function collection(Collection $rows)
    {
        $count = 0;

        foreach ($rows as $row) {
            // Skip completely empty rows
            if (empty(array_filter($row->toArray()))) {
                Log::info("Skipped empty row at index: {$count}");
                continue;
            }

            $count++;

            // dd($row);

            try {
                // 🟢 Get related records (or create if not existing)
                $category = $this->getOrCreateCategory($row['category'] ?? $row['categoryname'] ?? null);
                $brand = $this->getOrCreateBrand($row['brand'] ?? null);
                $supplier = $this->getOrCreateSupplier($row['supplier'] ?? null);

                // 🟡 Clean & prepare fields
                $costPrice = $this->cleanNumeric($row['costprice'] ?? null);
                $salePrice = $this->cleanNumeric($row['salepriceinctax'] ?? $row['saleprice'] ?? null);
                $marginPerc = $this->cleanNumeric($row['marginperc'] ?? null);

                $productCode = $this->cleanValue($row['productid'] ?? null);

                // Handle missing product_code safely
                if (empty($productCode)) {
                    $productCode = 'AUTO-' . strtoupper(Str::random(10));
                    Log::warning("Row {$count}: Missing ProductId — auto-generated as {$productCode}");
                }

                // 🟢 Create or update product record
                $product = Product::updateOrCreate(
                    [
                        'product_code' => $productCode,
                    ],
                    [
                        'category_id' => $category?->id,
                        'brand_id' => $brand?->id,
                        'supplier_id' => $supplier?->id,
                        'name' => $this->cleanValue($row['name'] ?? null),
                        'description' => $this->cleanValue($row['description'] ?? null),
                        'cost_price' => $costPrice,
                        'tax_rate' => $this->cleanNumeric($row['taxrate'] ?? 0),
                        'cost_inc_tax' => $this->cleanNumeric($row['costinctax'] ?? $costPrice),
                        'sale_price_inc_tax' => $salePrice,
                        'is_variable_price' => $this->cleanBoolean($row['isvariableprice'] ?? null),
                        'margin_perc' => $marginPerc,
                        'tax_exempt_eligible' => $this->cleanBoolean($row['taxexempteligible'] ?? null),
                        'rr_price' => $this->cleanNumeric($row['rrprice'] ?? null),
                        'bottle_deposit_item_name' => $this->cleanValue($row['bottledeposititemname'] ?? null),
                        'barcode' => $this->cleanValue($row['barcode'] ?? null),
                        'size' => $this->parseArray($row['size'] ?? null),
                        'colours' => $this->parseArray($row['colour'] ?? null),
                        'age_restriction' => $this->cleanValue($row['agerestriction'] ?? null),
                        'images' => [],
                    ]
                );

                Log::info("Row {$count}: Product {$product->name} [{$product->product_code}] imported successfully.");

            } catch (\Throwable $e) {
                Log::error("Row {$count}: Failed to import product. Error: " . $e->getMessage(), [
                    'row_data' => $row->toArray(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("✅ Product import completed. Total processed rows: {$count}");
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }

    private function getOrCreateCategory(?string $name)
    {
        if (!$name) {
            Log::warning("⚠️ Missing category name — product will not be linked to a category.");
            return null;
        }

        return Category::updateOrCreate(
            ['name' => trim($name)],
            ['is_active' => true]
        );
    }

    private function getOrCreateBrand(?string $name)
    {
        if (!$name) return null;

        return Brand::updateOrCreate(
            ['name' => trim($name)],
            ['is_active' => true]
        );
    }

    private function getOrCreateSupplier(?string $name)
    {
        if (!$name) return null;

        return Supplier::updateOrCreate(
            ['name' => trim($name)],
            ['is_active' => true]
        );
    }

    private function cleanValue($value)
    {
        return $value === null || $value === '' ? null : trim($value);
    }

    private function cleanNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function cleanBoolean($value)
    {
        $value = strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'yes', 'y'], true);
    }

    private function parseArray($value)
    {
        if (empty($value)) return [];
        return array_map('trim', explode(',', $value));
    }
}
