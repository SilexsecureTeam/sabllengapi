<?php

namespace App\Http\Controllers;

use App\Models\TrustedOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrustedOrganisationController extends Controller
{
    public function index()
    {
        $sections = TrustedOrganization::where('is_active', true)->get();

        if ($sections->isEmpty()) {
            return response()->json([
                'message' => 'No active sections found',
                'data' => []
            ], 404);
        }

        $data = $sections->map(function ($section) {
            return [
                'id' => $section->id,
                'heading' => $section->heading,
                'logos' => collect($section->logos ?? [])
                    ->map(function ($logo) {
                        return [
                            'id' => $logo['id'] ?? null,
                            'name' => $logo['name'] ?? null,
                            'url' => $logo['url'] ?? null, // ✅ already stored as URL
                        ];
                    })
                    ->values(),
                'is_active' => $section->is_active,
            ];
        });

        return response()->json([
            'message' => 'Sections retrieved successfully',
            'data' => $data
        ], 200);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'heading' => 'required|string|max:255',
            'logos' => 'nullable|array',
            'logos.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'logo_names' => 'nullable|array',
            'logo_names.*' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $logosData = [];

        if ($request->hasFile('logos')) {
            $logoNames = $request->input('logo_names', []);

            foreach ($request->file('logos') as $index => $logo) {
                $path = $logo->store('trusted-organizations', 'public');

                $logosData[] = [
                    'id' => $index + 1,
                    'name' => $logoNames[$index] ?? 'Organization ' . ($index + 1),
                    'url' => Storage::disk('public')->url($path),
                ];
            }
        }

        $section = TrustedOrganization::create([
            'heading' => $validated['heading'],
            'logos' => $logosData,
            'is_active' => $validated['is_active'] ?? true
        ]);

        return response()->json([
            'message' => 'Section created successfully',
            'data' => $section
        ], 201);
    }

    /**
     * Update section (replaces all logos if new ones uploaded)
     */
    public function update(Request $request, $id)
    {
        $section = TrustedOrganization::findOrFail($id);

        $validated = $request->validate([
            'heading' => 'sometimes|string|max:255',
            'logos' => 'nullable|array',
            'logos.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'logo_names' => 'nullable|array',
            'logo_names.*' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $logosData = $section->logos ?? [];

        if ($request->hasFile('logos')) {

            /** 🔥 Delete old images */
            foreach ($logosData as $oldLogo) {
                if (!empty($oldLogo['url'])) {
                    // Convert URL back to storage path
                    $path = str_replace(
                        Storage::disk('public')->url(''),
                        '',
                        $oldLogo['url']
                    );

                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            /** ✅ Store new images as URLs */
            $logosData = [];
            $logoNames = $request->input('logo_names', []);

            foreach ($request->file('logos') as $index => $logo) {
                $path = $logo->store('trusted-organizations', 'public');

                $logosData[] = [
                    'id' => $index + 1,
                    'name' => $logoNames[$index] ?? 'Organization ' . ($index + 1),
                    'url' => Storage::disk('public')->url($path),
                ];
            }
        }

        $section->update([
            'heading' => $validated['heading'] ?? $section->heading,
            'logos' => $request->hasFile('logos') ? $logosData : $section->logos,
            'is_active' => $validated['is_active'] ?? $section->is_active,
        ]);

        return response()->json([
            'message' => 'Section updated successfully',
            'data' => $section
        ], 200);
    }

    /**
     * Add a single logo to existing section
     */
    public function addLogo(Request $request, $id)
    {
        $section = TrustedOrganization::findOrFail($id);

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'name' => 'nullable|string|max:255'
        ]);

        $logosData = $section->logos ?? [];

        // Generate next ID safely
        $newId = collect($logosData)->max('id') + 1 ?? 1;

        /** ✅ Store logo */
        $path = $request->file('logo')->store('trusted-organizations', 'public');

        $logosData[] = [
            'id' => $newId,
            'name' => $validated['name'] ?? 'Organization ' . $newId,
            'url' => Storage::disk('public')->url($path),
        ];

        $section->update([
            'logos' => $logosData
        ]);

        return response()->json([
            'message' => 'Logo added successfully',
            'data' => $section
        ], 200);
    }

    /**
     * Delete a single logo
     */
    public function deleteLogo($id, $logoId)
    {
        $section = TrustedOrganization::findOrFail($id);
        $logosData = $section->logos ?? [];

        $logoIndex = array_search($logoId, array_column($logosData, 'id'));

        if ($logoIndex === false) {
            return response()->json([
                'message' => 'Logo not found'
            ], 404);
        }

        // Delete logo from storage
        if (
            !empty($logosData[$logoIndex]['path']) &&
            Storage::disk('public')->exists($logosData[$logoIndex]['path'])
        ) {
            Storage::disk('public')->delete($logosData[$logoIndex]['path']);
        }

        // Remove from array
        array_splice($logosData, $logoIndex, 1);

        // Reindex IDs
        $logosData = array_values($logosData);
        foreach ($logosData as $index => &$logo) {
            $logo['id'] = $index + 1;
        }

        $section->logos = $logosData;
        $section->save();

        return response()->json([
            'message' => 'Logo deleted successfully',
            'data' => $section
        ], 200);
    }

    /**
     * Delete entire section
     */
    public function destroy($id)
    {
        $section = TrustedOrganization::findOrFail($id);

        // Delete all logos from storage
        foreach ($section->logos ?? [] as $logo) {
            if (!empty($logo['path']) && Storage::disk('public')->exists($logo['path'])) {
                Storage::disk('public')->delete($logo['path']);
            }
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully'
        ], 200);
    }
}
