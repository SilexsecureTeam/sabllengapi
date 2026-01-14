<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AboutUsController extends Controller
{
    /**
     * PUBLIC: Get About Us (Frontend)
     */
    public function index()
    {
        $aboutUs = AboutUs::first();

        if (!$aboutUs) {
            return response()->json([
                'message' => 'About us section not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'About us section retrieved successfully',
            'data' => $aboutUs
        ], 200);
    }

    /**
     * ADMIN: Create or Update About Us (Single Record)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'heading'        => 'required|string|max:255',
            'content'        => 'required|string',
            'founder_name'   => 'nullable|string|max:255',
            'founder_title'  => 'nullable|string|max:255',
            'founder_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $aboutUs = AboutUs::first();

        $founderImagePath = $aboutUs?->founder_image;

        if ($request->hasFile('founder_image')) {
            if ($founderImagePath && Storage::disk('public')->exists($founderImagePath)) {
                Storage::disk('public')->delete($founderImagePath);
            }

            $founderImagePath = $request->file('founder_image')->store('about-us', 'public');
        }

        $aboutUs = AboutUs::updateOrCreate(
            ['id' => $aboutUs?->id ?? 1],
            [
                'heading'        => $validated['heading'],
                'content'        => $validated['content'],
                'founder_name'   => $validated['founder_name'] ?? null,
                'founder_title'  => $validated['founder_title'] ?? null,
                'founder_image'  => $founderImagePath,
            ]
        );

        return response()->json([
            'message' => 'About us section saved successfully',
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
                'created_at' => $aboutUs->created_at,
                'updated_at' => $aboutUs->updated_at,
            ]
        ], 200);
    }


    /**
     * ADMIN: Delete founder image only
     */
    // public function deleteFounderImage()
    // {
    //     $aboutUs = AboutUs::firstOrFail();

    //     if ($aboutUs->founder_image && Storage::disk('public')->exists($aboutUs->founder_image)) {
    //         Storage::disk('public')->delete($aboutUs->founder_image);
    //     }

    //     $aboutUs->update(['founder_image' => null]);

    //     return response()->json([
    //         'message' => 'Founder image deleted successfully',
    //         'data' => $aboutUs
    //     ], 200);
    // }

    /**
     * OPTIONAL: Delete About Us entirely (usually NOT needed)
     */
    public function destroy()
    {
        $aboutUs = AboutUs::first();

        if (!$aboutUs) {
            return response()->json([
                'message' => 'About us section already deleted',
            ], 404);
        }

        // Delete founder image if it exists
        if ($aboutUs->founder_image && Storage::disk('public')->exists($aboutUs->founder_image)) {
            Storage::disk('public')->delete($aboutUs->founder_image);
        }

        // Delete the AboutUs record
        $aboutUs->delete();

        return response()->json([
            'message' => 'About us section deleted successfully'
        ], 200);
    }
}
