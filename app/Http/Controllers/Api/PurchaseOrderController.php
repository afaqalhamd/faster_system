<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get pagination parameters from request or use defaults
        $perPage = $request->input('per_page', 20); // Default 20 items per page

        $purchaseOrders = PurchaseOrder::with(['party', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrders->items(),
            'pagination' => [
                'total' => $purchaseOrders->total(),
                'per_page' => $purchaseOrders->perPage(),
                'current_page' => $purchaseOrders->currentPage(),
                'last_page' => $purchaseOrders->lastPage(),
                'from' => $purchaseOrders->firstItem(),
                'to' => $purchaseOrders->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created purchase order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Implementation of purchase order creation would go here
        // This would include creating the purchase order and related item transactions

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order created successfully',
            'data' => [] // Return the created purchase order
        ], 201);
    }

    /**
     * Display the specified purchase order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'party',
            'user',
            'currency',
            'itemTransaction',
            'paymentTransaction',
            'statusHistory'
        ])->find($id);

        if (!$purchaseOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrder
        ]);
    }

    /**
     * Update the specified purchase order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::find($id);

        if (!$purchaseOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase order not found'
            ], 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'order_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'party_id' => 'sometimes|exists:parties,id',
            'order_status' => 'sometimes|string',
            'currency_id' => 'sometimes|exists:currencies,id',
            'exchange_rate' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Implementation of purchase order update would go here
        // This would include updating the purchase order and related item transactions

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order updated successfully',
            'data' => [] // Return the updated purchase order
        ]);
    }

    /**
     * Remove the specified purchase order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::find($id);

        if (!$purchaseOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase order not found'
            ], 404);
        }

        // Check if the purchase order can be deleted
        // Implementation would go here

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order deleted successfully'
        ]);
    }
}