<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Services\ItemTransactionService;
use App\Services\PaymentTransactionService;
use App\Services\AccountTransactionService;
use App\Services\ItemService;
use App\Services\PartyService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\General;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    protected $paymentTransactionService;
    protected $accountTransactionService;
    protected $itemTransactionService;
    protected $itemService;
    protected $partyService;
    protected $previousHistoryOfItems;

    public function __construct(
        PaymentTransactionService $paymentTransactionService,
        AccountTransactionService $accountTransactionService,
        ItemTransactionService $itemTransactionService,
        ItemService $itemService,
        PartyService $partyService
    ) {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->partyService = $partyService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * Get all sales
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Sale::with(['party', 'user']);

            // Apply filters if provided
            if ($request->has('party_id')) {
                $query->where('party_id', $request->party_id);
            }

            if ($request->has('from_date')) {
                $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
            }

            if ($request->has('to_date')) {
                $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
            }

            // Check if user can view other users' sales
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices')) {
                $query->where('created_by', auth()->user()->id);
            }

            $sales = $query->get();

            $formattedSales = $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sale_code' => $sale->sale_code,
                    'sale_date' => $this->toUserDateFormat($sale->sale_date),
                    'party_name' => $sale->party->getFullName(),
                    'grand_total' => $this->formatWithPrecision($sale->grand_total, comma: false),
                    'paid_amount' => $this->formatWithPrecision($sale->paid_amount, comma: false),
                    'balance' => $this->formatWithPrecision($sale->grand_total - $sale->paid_amount, comma: false),
                    'created_by' => $sale->user->username ?? '',
                    'created_at' => $this->toUserDateFormat($sale->created_at),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales retrieved successfully',
                'data' => $formattedSales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific sale
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $sale = Sale::with([
                'party',
                'user',
                'itemTransaction' => function ($query) {
                    $query->with(['item', 'tax', 'batch.itemBatchMaster', 'itemSerialTransaction.itemSerialMaster']);
                }
            ])->findOrFail($id);

            // Check if user can view other users' sales
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to view this sale'
                ], 403);
            }

            // Format item transactions
            $itemTransactions = $sale->itemTransaction->map(function ($transaction) {
                $data = [
                    'id' => $transaction->id,
                    'item_name' => $transaction->item->name,
                    'quantity' => $this->formatWithPrecision($transaction->quantity, comma: false),
                    'unit_price' => $this->formatWithPrecision($transaction->unit_price, comma: false),
                    'discount_amount' => $this->formatWithPrecision($transaction->discount_amount, comma: false),
                    'tax_amount' => $this->formatWithPrecision($transaction->tax_amount, comma: false),
                    'total' => $this->formatWithPrecision($transaction->total, comma: false),
                ];

                // Add batch information if available
                if ($transaction->batch && $transaction->batch->itemBatchMaster) {
                    $data['batch'] = [
                        'batch_no' => $transaction->batch->itemBatchMaster->batch_no,
                        'mfg_date' => $transaction->batch->itemBatchMaster->mfg_date ? $this->toUserDateFormat($transaction->batch->itemBatchMaster->mfg_date) : null,
                        'exp_date' => $transaction->batch->itemBatchMaster->exp_date ? $this->toUserDateFormat($transaction->batch->itemBatchMaster->exp_date) : null,
                    ];
                }

                // Add serial information if available
                if ($transaction->itemSerialTransaction->isNotEmpty()) {
                    $data['serials'] = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                        return [
                            'serial_code' => $serialTransaction->itemSerialMaster->serial_code
                        ];
                    });
                }

                return $data;
            });

            // Get payment records
            $paymentRecords = $this->paymentTransactionService->getPaymentRecordsArray($sale);

            $saleData = [
                'id' => $sale->id,
                'sale_code' => $sale->sale_code,
                'sale_date' => $this->toUserDateFormat($sale->sale_date),
                'reference_no' => $sale->reference_no,
                'party' => [
                    'id' => $sale->party->id,
                    'name' => $sale->party->getFullName(),
                    'phone' => $sale->party->phone,
                    'email' => $sale->party->email,
                ],
                'items' => $itemTransactions,
                'payments' => $paymentRecords,
                'grand_total' => $this->formatWithPrecision($sale->grand_total, comma: false),
                'paid_amount' => $this->formatWithPrecision($sale->paid_amount, comma: false),
                'balance' => $this->formatWithPrecision($sale->grand_total - $sale->paid_amount, comma: false),
                'note' => $sale->note,
                'created_by' => $sale->user->username ?? '',
                'created_at' => $this->toUserDateFormat($sale->created_at),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Sale retrieved successfully',
                'data' => $saleData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new sale
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validate request
            $validator = Validator::make($request->all(), [
                'party_id' => 'required|exists:parties,id',
                'sale_date' => 'required|date_format:' . implode(',', $this->getDateFormats()),
                'reference_no' => 'nullable|string|max:255',
                'prefix_code' => 'required|string|max:10',
                'count_id' => 'required|integer',
                'sale_code' => 'required|string|max:255|unique:sales',
                'note' => 'nullable|string',
                'round_off' => 'nullable|numeric',
                'grand_total' => 'required|numeric|min:0',
                'state_id' => 'nullable|exists:states,id',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:items,id',
                'items.*.warehouse_id' => 'required|exists:warehouses,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.sale_price' => 'required|numeric|min:0',
                'items.*.discount' => 'nullable|numeric|min:0',
                'items.*.discount_type' => 'nullable|in:percentage,fixed',
                'items.*.tax_id' => 'nullable|exists:taxes,id',
                'items.*.tax_type' => 'nullable|in:inclusive,exclusive',
                'items.*.total' => 'required|numeric|min:0',
                'payments' => 'nullable|array',
                'payments.*.payment_type_id' => 'required|exists:payment_types,id',
                'payments.*.amount' => 'required|numeric|min:0',
                'payments.*.note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create sale
            $sale = Sale::create([
                'party_id' => $request->party_id,
                'sale_date' => $this->toSystemDateFormat($request->sale_date),
                'reference_no' => $request->reference_no,
                'prefix_code' => $request->prefix_code,
                'count_id' => $request->count_id,
                'sale_code' => $request->sale_code,
                'note' => $request->note,
                'round_off' => $request->round_off,
                'grand_total' => $request->grand_total,
                'state_id' => $request->state_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Process items
            foreach ($request->items as $item) {
                $itemDetails = Item::find($item['item_id']);

                // Validate item quantity
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception(__('item.please_enter_item_quantity', ['item_name' => $itemDetails->name]));
                }

                // Validate regular item quantity
                $regularItemTransaction = $this->itemTransactionService->validateRegularItemQuantity(
                    $itemDetails,
                    $item['warehouse_id'],
                    $item['quantity'],
                    ItemTransactionUniqueCode::SALE->value
                );

                if (!$regularItemTransaction) {
                    throw new \Exception(__('item.failed_to_save_regular_item_record'));
                }

                // Record item transaction
                $transaction = $this->itemTransactionService->recordItemTransactionEntry($sale, [
                    'warehouse_id' => $item['warehouse_id'],
                    'transaction_date' => $this->toSystemDateFormat($request->sale_date),
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? null,
                    'tracking_type' => $itemDetails->tracking_type,
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['sale_price'],
                    'mrp' => $item['mrp'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? 'fixed',
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_id' => $item['tax_id'] ?? null,
                    'tax_type' => $item['tax_type'] ?? 'exclusive',
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => $item['total'],
                ]);

                if (!$transaction) {
                    throw new \Exception("Failed to record Item Transaction Entry!");
                }

                // Process batch or serial items if needed
                if ($itemDetails->tracking_type == 'serial' && isset($item['serials'])) {
                    foreach ($item['serials'] as $serial) {
                        $serialTransaction = $this->itemTransactionService->recordItemSerials(
                            $transaction->id,
                            ['serial_code' => $serial],
                            $item['item_id'],
                            $item['warehouse_id'],
                            ItemTransactionUniqueCode::SALE->value
                        );

                        if (!$serialTransaction) {
                            throw new \Exception(__('item.failed_to_save_serials'));
                        }
                    }
                } elseif ($itemDetails->tracking_type == 'batch' && isset($item['batch'])) {
                    $batchTransaction = $this->itemTransactionService->recordItemBatches(
                        $transaction->id,
                        [
                            'batch_no' => $item['batch']['batch_no'],
                            'mfg_date' => isset($item['batch']['mfg_date']) ? $this->toSystemDateFormat($item['batch']['mfg_date']) : null,
                            'exp_date' => isset($item['batch']['exp_date']) ? $this->toSystemDateFormat($item['batch']['exp_date']) : null,
                            'model_no' => $item['batch']['model_no'] ?? null,
                            'mrp' => $item['batch']['mrp'] ?? 0,
                            'color' => $item['batch']['color'] ?? null,
                            'size' => $item['batch']['size'] ?? null,
                            'quantity' => $item['quantity'],
                        ],
                        $item['item_id'],
                        $item['warehouse_id'],
                        ItemTransactionUniqueCode::SALE->value
                    );

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            }

            // Process payments
            if (isset($request->payments) && count($request->payments) > 0) {
                foreach ($request->payments as $payment) {
                    if ($payment['amount'] > 0) {
                        $paymentsArray = [
                            'transaction_date' => $this->toSystemDateFormat($request->sale_date),
                            'amount' => $payment['amount'],
                            'payment_type_id' => $payment['payment_type_id'],
                            'note' => $payment['note'] ?? null,
                            'payment_from_unique_code' => General::INVOICE->value,
                        ];

                        if (!$this->paymentTransactionService->recordPayment($sale, $paymentsArray)) {
                            throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                        }
                    }
                }
            }

            // Update total paid amount
            if (!$this->paymentTransactionService->updateTotalPaidAmountInModel($sale)) {
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            // Check credit limit
            if ($this->partyService->limitThePartyCreditLimit($request->party_id)) {
                // Credit limit check passed
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'data' => [
                    'id' => $sale->id,
                    'sale_code' => $sale->sale_code
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing sale
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find the sale
            $sale = Sale::findOrFail($id);

            // Check if user can update this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to update this sale'
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'party_id' => 'required|exists:parties,id',
                'sale_date' => 'required|date_format:' . implode(',', $this->getDateFormats()),
                'reference_no' => 'nullable|string|max:255',
                'note' => 'nullable|string',
                'round_off' => 'nullable|numeric',
                'grand_total' => 'required|numeric|min:0',
                'state_id' => 'nullable|exists:states,id',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:items,id',
                'items.*.warehouse_id' => 'required|exists:warehouses,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.sale_price' => 'required|numeric|min:0',
                'items.*.discount' => 'nullable|numeric|min:0',
                'items.*.discount_type' => 'nullable|in:percentage,fixed',
                'items.*.tax_id' => 'nullable|exists:taxes,id',
                'items.*.tax_type' => 'nullable|in:inclusive,exclusive',
                'items.*.total' => 'required|numeric|min:0',
                'payments' => 'nullable|array',
                'payments.*.payment_type_id' => 'required|exists:payment_types,id',
                'payments.*.amount' => 'required|numeric|min:0',
                'payments.*.note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Store previous history of items for later update
            $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($sale);

            // Update sale
            $sale->update([
                'party_id' => $request->party_id,
                'sale_date' => $this->toSystemDateFormat($request->sale_date),
                'reference_no' => $request->reference_no,
                'note' => $request->note,
                'round_off' => $request->round_off,
                'grand_total' => $request->grand_total,
                'state_id' => $request->state_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate,
                'updated_by' => auth()->id(),
            ]);

            // Delete existing item transactions
            $sale->itemTransaction()->delete();

            // Process items
            foreach ($request->items as $item) {
                $itemDetails = Item::find($item['item_id']);

                // Validate item quantity
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception(__('item.please_enter_item_quantity', ['item_name' => $itemDetails->name]));
                }

                // Validate regular item quantity
                $regularItemTransaction = $this->itemTransactionService->validateRegularItemQuantity(
                    $itemDetails,
                    $item['warehouse_id'],
                    $item['quantity'],
                    ItemTransactionUniqueCode::SALE->value
                );

                if (!$regularItemTransaction) {
                    throw new \Exception(__('item.failed_to_save_regular_item_record'));
                }

                // Record item transaction
                $transaction = $this->itemTransactionService->recordItemTransactionEntry($sale, [
                    'warehouse_id' => $item['warehouse_id'],
                    'transaction_date' => $this->toSystemDateFormat($request->sale_date),
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? null,
                    'tracking_type' => $itemDetails->tracking_type,
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['sale_price'],
                    'mrp' => $item['mrp'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? 'fixed',
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_id' => $item['tax_id'] ?? null,
                    'tax_type' => $item['tax_type'] ?? 'exclusive',
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => $item['total'],
                ]);

                if (!$transaction) {
                    throw new \Exception("Failed to record Item Transaction Entry!");
                }

                // Process batch or serial items if needed
                if ($itemDetails->tracking_type == 'serial' && isset($item['serials'])) {
                    foreach ($item['serials'] as $serial) {
                        $serialTransaction = $this->itemTransactionService->recordItemSerials(
                            $transaction->id,
                            ['serial_code' => $serial],
                            $item['item_id'],
                            $item['warehouse_id'],
                            ItemTransactionUniqueCode::SALE->value
                        );

                        if (!$serialTransaction) {
                            throw new \Exception(__('item.failed_to_save_serials'));
                        }
                    }
                } elseif ($itemDetails->tracking_type == 'batch' && isset($item['batch'])) {
                    $batchTransaction = $this->itemTransactionService->recordItemBatches(
                        $transaction->id,
                        [
                            'batch_no' => $item['batch']['batch_no'],
                            'mfg_date' => isset($item['batch']['mfg_date']) ? $this->toSystemDateFormat($item['batch']['mfg_date']) : null,
                            'exp_date' => isset($item['batch']['exp_date']) ? $this->toSystemDateFormat($item['batch']['exp_date']) : null,
                            'model_no' => $item['batch']['model_no'] ?? null,
                            'mrp' => $item['batch']['mrp'] ?? 0,
                            'color' => $item['batch']['color'] ?? null,
                            'size' => $item['batch']['size'] ?? null,
                            'quantity' => $item['quantity'],
                        ],
                        $item['item_id'],
                        $item['warehouse_id'],
                        ItemTransactionUniqueCode::SALE->value
                    );

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            }

            // Process payments if provided
            if (isset($request->payments) && count($request->payments) > 0) {
                // Delete existing payment transactions
                $sale->paymentTransaction()->delete();

                foreach ($request->payments as $payment) {
                    if ($payment['amount'] > 0) {
                        $paymentsArray = [
                            'transaction_date' => $this->toSystemDateFormat($request->sale_date),
                            'amount' => $payment['amount'],
                            'payment_type_id' => $payment['payment_type_id'],
                            'note' => $payment['note'] ?? null,
                            'payment_from_unique_code' => General::INVOICE->value,
                        ];

                        if (!$this->paymentTransactionService->recordPayment($sale, $paymentsArray)) {
                            throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                        }
                    }
                }
            }

            // Update total paid amount
            if (!$this->paymentTransactionService->updateTotalPaidAmountInModel($sale)) {
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            // Update previous history of items
            $this->itemTransactionService->updatePreviousHistoryOfItems($sale, $this->previousHistoryOfItems);

            // Check credit limit
            if ($this->partyService->limitThePartyCreditLimit($request->party_id)) {
                // Credit limit check passed
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_updated_successfully'),
                'data' => [
                    'id' => $sale->id,
                    'sale_code' => $sale->sale_code
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a sale
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sale = Sale::findOrFail($id);

            // Check if user can delete this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to delete this sale'
                ], 403);
            }

            // Check if sale has returns
            if ($sale->saleReturn()->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete sale with associated returns'
                ], 409);
            }

            // Store previous history of items for later update
            $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($sale);

            // Delete related records
            $sale->itemTransaction()->delete();
            $sale->paymentTransaction()->delete();
            $sale->delete();

            // Update previous history of items
            $this->itemTransactionService->updatePreviousHistoryOfItems(null, $this->previousHistoryOfItems);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}