<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $currencies = Currency::all();

        return response()->json([
            'status' => 'success',
            'data' => $currencies
        ]);
    }

    /**
     * Display the specified currency.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $currency
        ]);
    }

    /**
     * Get the company's default currency.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompanyCurrency()
    {
        $currency = Currency::where('is_company_currency', 1)->first();

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company currency not set'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $currency
        ]);
    }
}