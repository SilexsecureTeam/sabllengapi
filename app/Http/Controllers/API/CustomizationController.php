<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customization;
use App\Models\Product;
use Illuminate\Http\Request;

class CustomizationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'image'        => 'nullable|image|mimes:png,jpeg,jpg,svg|max:2048',
            'text'         => 'nullable|string|max:255',
            'instruction'  => 'nullable|string',
            'position'     => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
            'coordinates'  => 'nullable|array', // e.g., { "x": 50, "y": 100 }
            'image_size'   => 'nullable|string'
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('customizations', 'public');
        }

        $customization = Customization::create([
            'product_id'  => $request->product_id,
            'user_id'     => $request->user()->id ?? null,
            'image_path'  => $path,
            'text'        => $request->text,
            'instruction' => $request->instruction,
            'position'    => $request->position ?? 'center',
            'coordinates' => $request->coordinates,
            'image_size'  => $request->input('image_size'),
        ]);

        return response()->json([
            'message' => 'Customization saved successfully',
            'data'    => $customization,
        ]);
    }

    // public function index(Request $request)
    // {
    //     $customizations = Customization::with(['product:id,name,price', 'user:id,name,email'])
    //         ->when(
    //             $request->product_id,
    //             fn($q) =>
    //             $q->where('product_id', $request->product_id)
    //         )
    //         ->when(
    //             $request->user_id,
    //             fn($q) =>
    //             $q->where('user_id', $request->user_id)
    //         )
    //         ->latest()
    //         ->paginate(20);

    //     return response()->json([
    //         'status' => true,
    //         'data'   => $customizations,
    //     ]);
    // }

    public function showCustomizedProduct($id)
    {
        $product = Product::with([
            'category',
            'subcategory',
            'brand',
            'supplier',
            'customizations'
        ])
            ->whereHas('customizations')
            ->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => number_format($product->sale_price ?? 0, 2),

            'customizations' => $product->customizations->map(function ($custom) {
                return [
                    'id' => $custom->id,
                    'text' => $custom->text,
                    'instruction' => $custom->instruction,
                    'position' => $custom->position,
                    'coordinates' => $custom->coordinates,
                    'image' => $custom->image
                        ? asset('storage/' . $custom->image)
                        : null,
                    'image_size' => $custom->image_size
                ];
            }),
        ], 200);
    }
}
