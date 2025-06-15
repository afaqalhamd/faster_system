<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Items\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of items with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get pagination parameters from request or use defaults
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $page = $request->input('page', 1);

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('status', 1)
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Display the specified item.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand', 'itemGeneralQuantities'])
                    ->find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found'
            ], 404);
        }

        // No longer adding image_url to item

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    /**
     * Get items by category with pagination.
     *
     * @param  int  $categoryId
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemsByCategory($categoryId, Request $request)
    {
        // Get pagination parameters from request or use defaults
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $page = $request->input('page', 1);

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit'])
                    ->where('item_category_id', $categoryId)
                    ->where('status', 1)
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Search items by name or code with pagination.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $perPage = $request->input('per_page', 20); // Default 20 items per page

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('status', 1)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('item_code', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Get item by SKU.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemBySKU($sku)
    {
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('sku', $sku)
                    ->where('status', 1)
                    ->first();

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found with this SKU'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }
    
    /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'item_code' => 'string|max:100|unique:items,item_code,' . $id,
            'sku' => 'string|max:100|unique:items,sku,' . $id,
            'description' => 'nullable|string',
            'item_category_id' => 'exists:item_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'base_unit_id' => 'exists:units,id',
            'secondary_unit_id' => 'nullable|exists:units,id',
            'base_unit_multiplier' => 'nullable|numeric',
            'purchase_price' => 'numeric|min:0',
            'sale_price' => 'numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric',
            'alert_quantity' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'is_batch_tracking' => 'boolean',
            'is_serial_tracking' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($request->all());

        // Refresh the item with relationships
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Item updated successfully',
            'data' => $item
        ]);
    }
}