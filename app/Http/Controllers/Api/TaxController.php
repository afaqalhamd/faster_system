<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    /**
     * Display a listing of taxes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $taxes = Tax::with('user')->get();

        return response()->json([
            'status' => 'success',
            'data' => $taxes
        ]);
    }

    /**
     * Store a newly created tax in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:taxes',
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tax = Tax::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tax created successfully',
            'data' => $tax
        ], 201);
    }

    /**
     * Display the specified tax.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $tax = Tax::with('user')->find($id);

        if (!$tax) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tax not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tax
        ]);
    }

    /**
     * Update the specified tax in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $tax = Tax::find($id);

        if (!$tax) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tax not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:taxes,name,' . $id,
            'rate' => 'numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tax->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tax updated successfully',
            'data' => $tax
        ]);
    }

    /**
     * Remove the specified tax from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $tax = Tax::find($id);

        if (!$tax) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tax not found'
            ], 404);
        }

        $tax->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tax deleted successfully'
        ]);
    }
}