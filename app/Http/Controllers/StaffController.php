<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index()
    {
        if (! Auth::user() || Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = Staff::all();
        return response()->json(['data' => $staff], 200);
    }
    
    public function store(Request $request)
    {
        if (! $request->user() || $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // Validate request
        $validated = $request->validate([
            'full_name'              => 'required|string|max:255',
            'email'                  => 'required|email|unique:staff,email',
            'phone_number'           => 'nullable|string|max:20',
            'age'                    => 'nullable|integer',
            'salary'                 => 'nullable|string',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end'   => 'nullable|date_format:H:i',
            // 'staff_role'             => 'nullable|string|max:100',
            'staff_address'          => 'nullable|string|max:255',
            'additional_information' => 'nullable|string',
            'photo'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('staff_photos', 'public');
        }

        // Create staff
        $staff = Staff::create($validated);

        return response()->json([
            'message' => 'Staff member added successfully.',
            'data'    => $staff
        ], 201);
    }

    public function show($id)
    {
        if (! Auth::check() || Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json(['data' => $staff], 200);
    }
    /**
     * Update staff.
     */
    public function update(Request $request, $id)
    {
        if (! $request->user() || $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $validated = $request->validate([
            'full_name'              => 'sometimes|string|max:255',
            'email'                  => 'sometimes|email|unique:staff,email,' . $staff->id,
            'phone_number'           => 'nullable|string|max:20',
            'age'                    => 'nullable|integer',
            'salary'                 => 'nullable|string',
            'working_hours_start'    => 'nullable|date_format:H:i',
            'working_hours_end'      => 'nullable|date_format:H:i',
            'staff_role'             => 'sometimes|string|max:100',
            'staff_address'          => 'nullable|string|max:255',
            'additional_information' => 'nullable|string',
            'photo'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // If new photo uploaded, delete old one
        if ($request->hasFile('photo')) {
            if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
                Storage::disk('public')->delete($staff->photo);
            }
            $validated['photo'] = $request->file('photo')->store('staff_photos', 'public');
        }

        $staff->update($validated);

        return response()->json([
            'message' => 'Staff member updated successfully.',
            'data'    => $staff
        ], 200);
    }

    /**
     * Delete staff.
     */
    public function destroy(Request $request, $id)
    {
        if (! $request->user() || $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = Staff::find($id);

        if (! $staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        // Delete photo if exists
        if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
            Storage::disk('public')->delete($staff->photo);
        }

        $staff->delete();

        return response()->json(['message' => 'Staff deleted successfully'], 200);
    }
}
