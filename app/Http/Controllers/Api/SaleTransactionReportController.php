<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\Sale;
use App\Services\ItemTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleTransactionReportController extends Controller
{
    use FormatsDateInputs;
    use FormatNumber;

    protected $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService) {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Get all sale records without filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSaleRecords(): JsonResponse
    {
        try {
            $sales = Sale::with('party')->get();

            if($sales->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Records Found!!'
                ], 404);
            }

            $recordsArray = [];

            foreach ($sales as $sale) {
                $recordsArray[] = [
                    'id' => $sale->id,
                    'sale_date' => $this->toUserDateFormat($sale->sale_date),
                    'invoice_code' => $sale->sale_code,
                    'party_name' => $sale->party->getFullName(),
                    'grand_total' => $this->formatWithPrecision($sale->grand_total, comma:false),
                    'paid_amount' => $this->formatWithPrecision($sale->paid_amount, comma:false),
                    'balance' => $this->formatWithPrecision($sale->grand_total - $sale->paid_amount, comma:false),
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'All sale records retrieved successfully',
                'data' => $recordsArray
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all sale item records without filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSaleItemRecords(): JsonResponse
    {
        try {
            $sales = Sale::with(['party', 'itemTransaction.item.brand', 'itemTransaction.warehouse'])->get();

            if($sales->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Records Found!!'
                ], 404);
            }

            $recordsArray = [];

            foreach ($sales as $sale) {
                foreach($sale->itemTransaction as $transaction) {
                    $recordsArray[] = [
                        'sale_id' => $sale->id,
                        'sale_date' => $this->toUserDateFormat($sale->sale_date),
                        'invoice_code' => $sale->sale_code,
                        'party_name' => $sale->party->getFullName(),
                        'warehouse' => $transaction->warehouse->name ?? 'N/A',
                        'item_name' => $transaction->item->name,
                        'brand_name' => $transaction->item->brand->name ?? 'N/A',
                        'unit_price' => $this->formatWithPrecision($transaction->unit_price, comma:false),
                        'quantity' => $this->formatWithPrecision($transaction->quantity, comma:false),
                        'discount_amount' => $this->formatWithPrecision($transaction->discount_amount, comma:false),
                        'tax_amount' => $this->formatWithPrecision($transaction->tax_amount, comma:false),
                        'total' => $this->formatWithPrecision($transaction->total, comma:false),
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'All sale item records retrieved successfully',
                'data' => $recordsArray
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all sale payment records without filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSalePaymentRecords(): JsonResponse
    {
        try {
            $sales = Sale::with(['party', 'paymentTransaction.paymentType'])->get();

            if($sales->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Records Found!!'
                ], 404);
            }

            $recordsArray = [];

            foreach ($sales as $sale) {
                foreach($sale->paymentTransaction as $transaction) {
                    $recordsArray[] = [
                        'sale_id' => $sale->id,
                        'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
                        'invoice_code' => $sale->sale_code,
                        'party_name' => $sale->party->getFullName(),
                        'payment_type' => $transaction->paymentType->name ?? 'N/A',
                        'amount' => $this->formatWithPrecision($transaction->amount, comma:false),
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'All sale payment records retrieved successfully',
                'data' => $recordsArray
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}