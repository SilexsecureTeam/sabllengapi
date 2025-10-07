<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customization;
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
        ]);

        return response()->json([
            'message' => 'Customization saved successfully',
            'data'    => $customization,
        ]);
    }
}
