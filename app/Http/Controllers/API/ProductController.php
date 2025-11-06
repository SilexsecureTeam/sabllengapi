<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index()
    {
        $products = Product::with(['tag', 'category', 'subcategory', 'brand', 'supplier', 'customization'])->paginate(10);

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
            'subcategory_id' => 'nullable|exists:sub_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric',
            'tax_rate' => 'nullable|string',
            'tax_rate_value' => 'nullable|numeric',
            'cost_inc_tax' => 'nullable|numeric',
            'sale_price_inc_tax' => 'nullable|numeric',
            'is_variable_price' => 'boolean',
            'margin_perc' => 'nullable|numeric',
            'tax_exempt_eligible' => 'boolean',
            'rr_price' => 'nullable|numeric',
            'bottle_deposit_item_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
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
            'sale_price_inc_tax' => $validated['sale_price_inc_tax'] ?? null,
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

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('category', 'subcategory', 'brand', 'supplier', 'coupon'),
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with([ 'category', 'subcategory', 'brand', 'supplier', 'customization', 'coupon'])->findOrFail($id);

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
            'sale_price_inc_tax' => 'nullable|numeric',
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

        // 🖼️ Handle images
        $imagesData = $product->getRawOriginal('images') ? json_decode($product->getRawOriginal('images'), true) : [];

        if ($request->hasFile('images')) {
            // Delete existing images
            foreach ($imagesData as $oldImage) {
                if (!empty($oldImage['path']) && Storage::disk('public')->exists($oldImage['path'])) {
                    Storage::disk('public')->delete($oldImage['path']);
                }
            }

            // Store new images
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
        $product->update([
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
            'sale_price_inc_tax' => $validated['sale_price_inc_tax'] ?? $product->sale_price_inc_tax,
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
            'images' => $imagesData,
            'coupon_id' => $validated['coupon_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh(['category', 'subcategory', 'brand', 'supplier', 'coupon']),
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
}
