<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryFee;
use Illuminate\Http\Request;

class DeliveryFeeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_name' => 'required|string',
            'lga_name' => 'required|string',
            'places' => 'required|string',
            'fee' => 'required|numeric|min:0',
        ]);

        $deliveryFee = DeliveryFee::create($validated);

        return response()->json([
            'message' => 'Delivery fee saved successfully',
            'data' => $deliveryFee
        ]);
    }

    public function index()
    {
        $fees = DeliveryFee::orderBy('state_name')
            ->orderBy('lga_name')
            ->orderBy('places')
            ->get();

        return response()->json([
            'message' => 'Delivery fees retrieved successfully',
            'data' => $fees
        ]);
    }

    public function update(Request $request, $id)
    {
        $deliveryFee = DeliveryFee::find($id);

        if (!$deliveryFee) {
            return response()->json([
                'message' => 'Delivery fee not found'
            ], 404);
        }

        $validated = $request->validate([
            'state_name' => 'sometimes|required|string',
            'lga_name' => 'sometimes|required|string',
            'places' => 'sometimes|required|string',
            'fee' => 'sometimes|required|numeric|min:0',
        ]);

        $deliveryFee->update($validated);

        return response()->json([
            'message' => 'Delivery fee updated successfully',
            'data' => $deliveryFee
        ]);
    }

    /**
     * Delete a delivery fee
     */
    public function destroy($id)
    {
        $deliveryFee = DeliveryFee::find($id);

        if (!$deliveryFee) {
            return response()->json([
                'message' => 'Delivery fee not found'
            ], 404);
        }

        $deliveryFee->delete();

        return response()->json([
            'message' => 'Delivery fee deleted successfully'
        ]);
    }

    public function getStates()
    {
        $states = DeliveryFee::select('state_name')->distinct()->get();
        return response()->json($states);
    }

    public function getLgas($state)
    {
        $lgas = DeliveryFee::where('state_name', $state)
            ->select('lga_name')
            ->distinct()
            ->get();
        return response()->json($lgas);
    }

    public function getPlaces($state, $lga)
    {
        $places = DeliveryFee::where('state_name', $state)
            ->where('lga_name', $lga)
            ->select('places', 'fee')
            ->get();
        return response()->json($places);
    }
}
