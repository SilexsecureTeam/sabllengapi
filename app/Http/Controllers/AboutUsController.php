<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AboutUsController extends Controller
{
    /**
     * Get the about us section (PUBLIC - for frontend)
     */
    public function index()
    {
        $aboutUs = AboutUs::where('is_active', true)->first();

        if (!$aboutUs) {
            return response()->json([
                'message' => 'About us section not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'About us section retrieved successfully',
            'data' => [
                'id' => $aboutUs->id,
                'heading' => $aboutUs->heading,
                'content' => $aboutUs->content,
                'founder_name' => $aboutUs->founder_name,
                'founder_title' => $aboutUs->founder_title,
                'founder_image_path' => $aboutUs->founder_image,
                'founder_image_url' => $aboutUs->founder_image
                    ? asset('storage/' . $aboutUs->founder_image)
                    : null,
                'is_active' => $aboutUs->is_active
            ]
        ], 200);
    }

    /**
     * Create new about us section
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'heading' => 'required|string|max:255',
            'content' => 'required|string',
            'founder_name' => 'nullable|string|max:255',
            'founder_title' => 'nullable|string|max:255',
            'founder_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $founderImagePath = null;
        if ($request->hasFile('founder_image')) {
            $founderImagePath = $request->file('founder_image')->store('about-us', 'public');
        }

        $aboutUs = AboutUs::create([
            'heading' => $validated['heading'],
            'content' => $validated['content'],
            'founder_name' => $validated['founder_name'] ?? null,
            'founder_title' => $validated['founder_title'] ?? null,
            'founder_image' => $founderImagePath,
            'is_active' => $validated['is_active'] ?? true
        ]);

        return response()->json([
            'message' => 'About us section created successfully',
            'data' => $aboutUs
        ], 201);
    }

    /**
     * Update about us section
     */
    public function update(Request $request, $id)
    {
        $aboutUs = AboutUs::findOrFail($id);

        $validated = $request->validate([
            'heading' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'founder_name' => 'nullable|string|max:255',
            'founder_title' => 'nullable|string|max:255',
            'founder_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        // Handle founder image update
        $founderImagePath = $aboutUs->founder_image;

        if ($request->hasFile('founder_image')) {
            // Delete old image if exists
            if ($aboutUs->founder_image && Storage::disk('public')->exists($aboutUs->founder_image)) {
                Storage::disk('public')->delete($aboutUs->founder_image);
            }

            // Store new image
            $founderImagePath = $request->file('founder_image')->store('about-us', 'public');
        }

        $aboutUs->update([
            'heading' => $validated['heading'] ?? $aboutUs->heading,
            'content' => $validated['content'] ?? $aboutUs->content,
            'founder_name' => $validated['founder_name'] ?? $aboutUs->founder_name,
            'founder_title' => $validated['founder_title'] ?? $aboutUs->founder_title,
            'founder_image' => $request->hasFile('founder_image') ? $founderImagePath : $aboutUs->founder_image,
            'is_active' => $validated['is_active'] ?? $aboutUs->is_active
        ]);

        return response()->json([
            'message' => 'About us section updated successfully',
            'data' => $aboutUs
        ], 200);
    }

    /**
     * Delete founder image only
     */
    public function deleteFounderImage($id)
    {
        $aboutUs = AboutUs::findOrFail($id);

        if ($aboutUs->founder_image && Storage::disk('public')->exists($aboutUs->founder_image)) {
            Storage::disk('public')->delete($aboutUs->founder_image);
        }

        $aboutUs->founder_image = null;
        $aboutUs->save();

        return response()->json([
            'message' => 'Founder image deleted successfully',
            'data' => $aboutUs
        ], 200);
    }

    /**
     * Delete entire about us section
     */
    public function destroy($id)
    {
        $aboutUs = AboutUs::findOrFail($id);

        // Delete founder image if exists
        if ($aboutUs->founder_image && Storage::disk('public')->exists($aboutUs->founder_image)) {
            Storage::disk('public')->delete($aboutUs->founder_image);
        }

        $aboutUs->delete();

        return response()->json([
            'message' => 'About us section deleted successfully'
        ], 200);
    }
}
