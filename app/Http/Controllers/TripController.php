<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Trip::query();

        // Handle search filtering
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhere('location', 'like', "%$search%");
        }

        // Handle price filtering
        if ($request->has('price_from')) {
            $price_from = $request->input('price_from');
            $query->where('price', '>=', $price_from);
        }

        if ($request->has('price_to')) {
            $price_to = $request->input('price_to');
            $query->where('price', '<=', $price_to);
        }

        // Handle order by
        if ($request->has('order_by')) {
            $order_by = $request->input('order_by');
            $direction = $request->input('direction', 'asc');
            $query->orderBy($order_by, $direction);
        }

        $trips = $query->get();

        return response()->json([
            'data' => $trips,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated_data = $request->validate([
            'slug' => 'required|unique:trips',
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        $trip = Trip::create($validated_data);

        return response()->json([
            'data' => $trip,
        ], 201);
    }

    /**
     * @param $slug
     * @return JsonResponse
     */
    public function show($slug): JsonResponse
    {
        $trip = Trip::where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $trip,
        ]);
    }

    /**
     * @param Request $request
     * @param $slug
     * @return JsonResponse
     */
    public function update(Request $request, $slug): JsonResponse
    {
        $trip = Trip::where('slug', $slug)->firstOrFail();

        $validated_data = $request->validate([
            'slug' => "required|unique:trips,slug,$trip->id",
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        $trip->update($validated_data);

        return response()->json([
            'data' => $trip,
        ]);
    }

    /**
     * @param $slug
     * @return JsonResponse
     */
    public function destroy($slug): JsonResponse
    {
        $trip = Trip::where('slug', $slug)->firstOrFail();
        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully',
        ]);
    }

    /**
     * @param Request $request
     * @param $slug
     * @return JsonResponse
     */
    public function book(Request $request, $slug): JsonResponse
    {
        $trip = Trip::where('slug', $slug)->firstOrFail();

        $validated_data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $total_price = $trip->price * $validated_data['quantity'];

        $booking = $trip->bookings()->create([
            'user_id' => auth()->id(),
            'quantity' => $validated_data['quantity'],
            'total_price' => $total_price,
        ]);

        return response()->json([
            'data' => $booking,
        ], 201);
    }
}
