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
        $products = Product::with(['categories', 'tags'])->paginate(10);

        return response()->json($products, 200);
    }

    public function store(Request $request)
    {
       
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'discounted_price' => 'nullable|numeric',
            'tax_included' => 'required|boolean',
            'expiration_start' => 'nullable|date',
            'expiration_end' => 'nullable|date|after_or_equal:expiration_start',
            'stock_quantity' => 'nullable|integer',
            'unlimited' => 'required|boolean',
            'stock_status' => 'nullable|string|in:In Stock,Out of Stock',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'colors' => 'nullable|array',
            'colors.*' => 'string',
            'customize' => 'required|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        //  dd('test');
        // Handle images with incremental IDs
        $imagesData = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $imagesData[] = [
                    'id' => $index + 1,
                    'path' => $path
                ];
            }
        }

       

        // Handle colors with incremental IDs
        $colorsData = [];
        if (!empty($validated['colors'])) {
            foreach ($validated['colors'] as $index => $color) {
                $colorsData[] = [
                    'id' => $index + 1,
                    'value' => $color
                ];
            }
        }

        // Create product
        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'discounted_price' => $validated['discounted_price'] ?? null,
            'tax_included' => $validated['tax_included'],
            'expiration_start' => $validated['expiration_start'] ?? null,
            'expiration_end' => $validated['expiration_end'] ?? null,
            'stock_quantity' => $validated['unlimited'] ? null : ($validated['stock_quantity'] ?? 0),
            'unlimited' => $validated['unlimited'],
            'stock_status' => $validated['stock_status'],
            'customize' => $validated['customize'],
            'colors' => $colorsData ?: null,
            'images' => $imagesData ?: null,
        ]);

        // Attach categories and tags
        if (isset($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }

        if (isset($validated['tags'])) {
            $product->tags()->sync($validated['tags']);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('categories', 'tags')
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with(['categories', 'tags'])->findOrFail($id);

        return response()->json($product, 200);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'discounted_price' => 'nullable|numeric',
            'tax_included' => 'sometimes|boolean',
            'expiration_start' => 'nullable|date',
            'expiration_end' => 'nullable|date|after_or_equal:expiration_start',
            'stock_quantity' => 'nullable|integer',
            'unlimited' => 'sometimes|boolean',
            'stock_status' => 'nullable|string|in:In Stock,Out of Stock',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'colors' => 'nullable|array',
            'colors.*' => 'string',
            'customize' => 'sometimes|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        // Handle images
        $imagesData = $product->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $imagesData[] = [
                    'id' => count($imagesData) + 1,
                    'path' => $path
                ];
            }
        }

        // Handle colors
        $colorsData = [];
        if (!empty($validated['colors'])) {
            foreach ($validated['colors'] as $index => $color) {
                $colorsData[] = [
                    'id' => $index + 1,
                    'value' => $color
                ];
            }
        }

        $product->update(array_merge($validated, [
            'slug' => isset($validated['name']) ? Str::slug($validated['name']) : $product->slug,
            'colors' => $colorsData ?: $product->colors,
            'images' => $imagesData,
        ]));

        if (isset($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }
        if (isset($validated['tags'])) {
            $product->tags()->sync($validated['tags']);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('categories', 'tags')
        ], 200);
    }

    /**
     * Remove a product.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete product images from storage
        if ($product->images) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image['path']);
            }
        }

        $product->categories()->detach();
        $product->tags()->detach();
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
