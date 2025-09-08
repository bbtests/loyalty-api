<?php

namespace App\Http\Controllers;

use App\Models\CashbackPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbackPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $cashbackPayments = CashbackPayment::with(['user', 'transaction'])->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Cashback payments retrieved successfully',
            'data' => [
                'items' => $cashbackPayments,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0',
            'payment_provider' => 'required|string|max:255',
            'provider_transaction_id' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'payment_details' => 'sometimes|array',
        ]);

        $cashbackPayment = CashbackPayment::create($validated);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Cashback payment created successfully',
            'data' => [
                'item' => $cashbackPayment,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashbackPayment $cashbackPayment): JsonResponse
    {
        $cashbackPayment->load(['user', 'transaction']);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Cashback payment retrieved successfully',
            'data' => [
                'item' => $cashbackPayment,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashbackPayment $cashbackPayment): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'transaction_id' => 'sometimes|exists:transactions,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_provider' => 'sometimes|string|max:255',
            'provider_transaction_id' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'payment_details' => 'sometimes|array',
        ]);

        $cashbackPayment->update($validated);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Cashback payment updated successfully',
            'data' => [
                'item' => $cashbackPayment,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashbackPayment $cashbackPayment): JsonResponse
    {
        $cashbackPayment->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Cashback payment deleted successfully',
            'data' => [],
        ]);
    }
}
