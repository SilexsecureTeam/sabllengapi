<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index()
    {
        $products = Product::with(['tag', 'category', 'subcategory', 'brand', 'supplier', 'customization'])->get();

        return response()->json($products, 200);
    }

    public function customizableProducts()
    {
        // Fetch all products where 'customize' is true, eager-load relations
        $products = Product::with(['category', 'subcategory', 'brand', 'supplier', 'customization'])
            ->where('customize', true)
            ->get();

        // Transform each product to show detailed info
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => number_format($product->price, 2),
                'image' => $product->image ? asset('storage/' . $product->image) : null,
                'customize' => $product->customize,
                'category' => $product->category?->name,
                'subcategory' => $product->subcategory?->name,
                'brand' => $product->brand?->name,
                'supplier' => $product->supplier?->name,
                'customization' => $product->customization,
                'created_at' => $product->created_at->toDateString(),
            ];
        });

        return response()->json([
            'count' => $data->count(),
            'products' => $data,
        ], 200);
    }


    public function store(Request $request)
    {

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|integer',
            'brand_id' => 'nullable|exists:brands,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'current_stock' => 'required|integer|min:1',
            'total_stock' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric',
            'tax_rate' => 'nullable|string',
            'tax_rate_value' => 'nullable|numeric',
            'cost_inc_tax' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
            'sales_price_inc_tax' => 'nullable|numeric',
            'is_variable_price' => 'boolean',
            'margin_perc' => 'nullable|numeric',
            'tax_exempt_eligible' => 'boolean',
            'rr_price' => 'nullable|numeric',
            'bottle_deposit_item_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:13|unique:products,barcode|regex:/^\d{13}$/',
            'size' => 'nullable|array',
            'colours' => 'nullable|array',
            'product_code' => 'nullable|string|max:255|unique:products,product_code',
            'age_restriction' => 'nullable|integer',
            'customize' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'coupon_id' => 'nullable|exists:coupons,id'
        ]);

        // Handle image uploads
        $imagesData = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $imagesData[] = [
                    'id' => $index + 1,
                    'path' => $path, // only path saved
                ];
            }
        }


        // Handle colors with IDs
        $colorsData = [];
        if (!empty($validated['colours'])) {
            foreach ($validated['colours'] as $index => $color) {
                $colorsData[] = [
                    'id' => $index + 1,
                    'value' => $color
                ];
            }
        }

        $product = Product::create([
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'images' => $imagesData ?: null,
            'cost_price' => $validated['cost_price'] ?? null,
            'tax_rate' => $validated['tax_rate'] ?? null,
            'tax_rate_value' => $validated['tax_rate_value'] ?? 0.00,
            'cost_inc_tax' => $validated['cost_inc_tax'] ?? null,
            'sales_price' => $validated['sales_price'] ?? null,
            'sales_price_inc_tax' => $validated['sales_price_inc_tax'] ?? null,
            'is_variable_price' => $validated['is_variable_price'] ?? false,
            'margin_perc' => $validated['margin_perc'] ?? null,
            'tax_exempt_eligible' => $validated['tax_exempt_eligible'] ?? false,
            'rr_price' => $validated['rr_price'] ?? null,
            'bottle_deposit_item_name' => $validated['bottle_deposit_item_name'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'size' => $validated['size'] ?? null,
            'colours' => $colorsData ?: null,
            'product_code' => $validated['product_code'] ?? null,
            'age_restriction' => $validated['age_restriction'] ?? null,
            'customize' => $validated['customize'] ?? false,
            'coupon_id' => $validated['coupon_id'] ?? null,
        ]);

        // Find existing inventory or create a new record
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $product->id],
            [
                // Common mirrored fields from the product
                'name'           => $product->name,
                'barcode'        => $product->barcode,
                'supplier_id'    => $product->supplier_id,
                'brand'          => optional($product->brand)->name,
                'category_name'  => optional($product->category)->name,

                // Prices
                'cost_price'     => $product->cost_price ?? 0,
                'sales_price'    => $product->sales_price ?? 0,

                // Stock defaults
                'current_stock'  => 0,
                'total_stock'    => 0,
                'on_order'       => 0,

                // Calculated defaults
                'total_cost'     => 0,
                'total_value'    => 0,
                'margin'         => 0,
                'margin_percentage' => 0,

                // Optional fields
                'measure'        => 'unit',
                'unit_of_sale'   => 'each',
            ]
        );

        // Amount of stock being added
        $addedStock = $request->current_stock ?? 1;

        // Increment both current and total stock
        $inventory->increment('current_stock', $addedStock);
        $inventory->increment('total_stock', $addedStock);

        // Recalculate totals and margins based on new stock
        $inventory->update([
            'total_cost'       => $inventory->current_stock * ($inventory->cost_price ?? 0),
            'total_value'      => $inventory->current_stock * ($inventory->sales_price ?? 0),
            'margin'           => ($inventory->sales_price ?? 0) - ($inventory->cost_price ?? 0),
            'margin_percentage' => ($inventory->cost_price > 0)
                ? ((($inventory->sales_price ?? 0) - ($inventory->cost_price ?? 0)) / $inventory->cost_price) * 100
                : 0,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('category', 'subcategory', 'brand', 'supplier', 'coupon'),
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'subcategory', 'brand', 'supplier', 'customization', 'coupon'])->findOrFail($id);

        return response()->json($product, 200);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:sub_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric',
            'tax_rate' => 'nullable|string',
            'tax_rate_value' => 'nullable|numeric',
            'cost_inc_tax' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
            'sales_price_inc_tax' => 'nullable|numeric',
            'is_variable_price' => 'sometimes|boolean',
            'margin_perc' => 'nullable|numeric',
            'tax_exempt_eligible' => 'sometimes|boolean',
            'rr_price' => 'nullable|numeric',
            'bottle_deposit_item_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'size' => 'nullable|array',
            'colours' => 'nullable|array',
            'product_code' => 'nullable|string|max:255|unique:products,product_code,' . $product->id,
            'age_restriction' => 'nullable|integer',
            'customize' => 'sometimes|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'coupon_id' => 'nullable|exists:coupons,id',
        ]);

        // 🖼️ Handle images - DELETE old, STORE new
        $imagesData = $product->getRawOriginal('images')
            ? json_decode($product->getRawOriginal('images'), true)
            : [];

        if ($request->hasFile('images')) {
            // Delete ALL existing images from storage
            foreach ($imagesData as $oldImage) {
                if (!empty($oldImage['path']) && Storage::disk('public')->exists($oldImage['path'])) {
                    Storage::disk('public')->delete($oldImage['path']);
                }
            }

            // Store ALL new images
            $imagesData = [];
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $imagesData[] = [
                    'id' => $index + 1,
                    'path' => $path,
                ];
            }
        }

        // 🎨 Handle colours
        $colorsData = [];
        if (!empty($validated['colours'])) {
            foreach ($validated['colours'] as $index => $color) {
                $colorsData[] = [
                    'id' => $index + 1,
                    'value' => $color
                ];
            }
        }

        // 💾 Update product
        $updateData = [
            'category_id' => $validated['category_id'] ?? $product->category_id,
            'subcategory_id' => $validated['subcategory_id'] ?? $product->subcategory_id,
            'brand_id' => $validated['brand_id'] ?? $product->brand_id,
            'supplier_id' => $validated['supplier_id'] ?? $product->supplier_id,
            'name' => $validated['name'] ?? $product->name,
            'description' => $validated['description'] ?? $product->description,
            'cost_price' => $validated['cost_price'] ?? $product->cost_price,
            'tax_rate' => $validated['tax_rate'] ?? $product->tax_rate,
            'tax_rate_value' => $validated['tax_rate_value'] ?? $product->tax_rate_value,
            'cost_inc_tax' => $validated['cost_inc_tax'] ?? $product->cost_inc_tax,
            'sales_price' => $validated['sales_price'] ?? $product->sales_price,
            'sales_price_inc_tax' => $validated['sales_price_inc_tax'] ?? $product->s,
            'is_variable_price' => $validated['is_variable_price'] ?? $product->is_variable_price,
            'margin_perc' => $validated['margin_perc'] ?? $product->margin_perc,
            'tax_exempt_eligible' => $validated['tax_exempt_eligible'] ?? $product->tax_exempt_eligible,
            'rr_price' => $validated['rr_price'] ?? $product->rr_price,
            'bottle_deposit_item_name' => $validated['bottle_deposit_item_name'] ?? $product->bottle_deposit_item_name,
            'barcode' => $validated['barcode'] ?? $product->barcode,
            'size' => $validated['size'] ?? $product->size,
            'colours' => $colorsData ?: $product->colours,
            'product_code' => $validated['product_code'] ?? $product->product_code,
            'age_restriction' => $validated['age_restriction'] ?? $product->age_restriction,
            'customize' => $validated['customize'] ?? $product->customize,
            'coupon_id' => $validated['coupon_id'] ?? null,
        ];

        // Only update images if new ones were uploaded
        if ($request->hasFile('images')) {
            $updateData['images'] = $imagesData;
        }

        $product->update($updateData);

        // Load relationships and format response
        $product->load(['category', 'subcategory', 'brand', 'supplier', 'coupon']);

        $productImages = collect($product->images ?? [])->map(function ($img) {
            return [
                'id'   => $img['id'] ?? null,
                'path' => $img['path'] ?? null,
                'url'  => isset($img['path']) ? asset('storage/' . $img['path']) : null,
            ];
        })->values()->toArray();

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => array_merge(
                $product->toArray(),
                ['images' => $productImages]
            ),
        ], 200);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // 🖼️ Delete product images from storage
        if ($product->images && is_array($product->images)) {
            foreach ($product->images as $image) {
                if (isset($image['path'])) {
                    Storage::disk('public')->delete($image['path']);
                }
            }
        }

        // 💾 Delete the product record
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }


    public function getProductsForDropdown()
    {
        $products = Product::select('id', 'name', 'barcode', 'product_code')
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }
}
