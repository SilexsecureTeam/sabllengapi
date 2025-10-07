<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
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
            'working_hours_end'   => 'nullable|date_format:H:i|after:working_hours_start',
            'staff_role'             => 'required|string|max:100',
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
}
