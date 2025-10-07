<?php

namespace App\Imports;

use App\Models\Inventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithUpserts;

class InventoryImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading, WithBatchInserts, WithUpserts
{
    use Importable;

    public function model(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        return new Inventory([
            'name'             => $this->cleanValue($row['name'] ?? null),
            'barcode'          => $this->cleanValue($row['barcode'] ?? null),
            'brand'            => $this->cleanValue($row['brand'] ?? null),
            'supplier'         => $this->cleanValue($row['supplier'] ?? null),
            'order_code'       => $this->cleanValue($row['ordercode'] ?? $row['order_code'] ?? null),
            'category_name'    => $this->cleanValue($row['categoryname'] ?? $row['category_name'] ?? null),
            'current_stock'    => $this->cleanNumeric($row['currentstock'] ?? $row['current_stock'] ?? null),
            'total_stock'      => $this->cleanNumeric($row['totalstock'] ?? $row['total_stock'] ?? null),
            'on_order'         => $this->cleanNumeric($row['onorder'] ?? $row['on_order'] ?? null),
            'cost_price'       => $this->cleanNumeric($row['costprice'] ?? $row['cost_price'] ?? null),
            'sales_price'      => $this->cleanNumeric($row['saleprice'] ?? $row['sales_price'] ?? $row['sale_price'] ?? null),
            'total_cost'       => $this->cleanNumeric($row['totalcost'] ?? $row['total_cost'] ?? null),
            'total_value'      => $this->cleanNumeric($row['totalvalue'] ?? $row['total_value'] ?? null),
            'margin'           => $this->cleanNumeric($row['margin'] ?? null),
            'margin_percentage' => $this->cleanNumeric($row['marginperc'] ?? $row['margin_perc'] ?? $row['margin_percentage'] ?? null),
            'measure'          => $this->cleanValue($row['measure'] ?? null),
            'unit_of_sale'     => $this->cleanValue($row['unitofsale'] ?? $row['unit_of_sale'] ?? null),
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Define unique columns for upserts (if needed)
     */
    public function uniqueBy()
    {
        return ['barcode']; // Assuming barcode is unique
    }

    /**
     * Clean string values
     */
    private function cleanValue($value)
    {
        if ($value === null || $value === '') {
            return '';  // Return empty string instead of null
        }

        return trim($value);
    }

    /**
     * Clean and convert numeric values
     */
    private function cleanNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove any non-numeric characters except decimal point and minus sign
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}