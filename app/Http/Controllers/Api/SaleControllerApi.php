<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use App\Models\Items\Item;
use App\Models\Prefix;
use App\Services\ItemTransactionService;
use App\Services\PaymentTransactionService;
use App\Services\AccountTransactionService;
use App\Services\ItemService;
use App\Services\PartyService;
use App\Services\PaymentTypeService;
use App\Services\Communication\Email\SaleEmailNotificationService;
use App\Services\Communication\Sms\SaleSmsNotificationService;
use App\Services\Communication\Email\SaleReturnEmailNotificationService;
use App\Services\Communication\Sms\SaleReturnSmsNotificationService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\General;
use App\Enums\App;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class SaleControllerApi extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    protected $paymentTransactionService;
    protected $accountTransactionService;
    protected $itemTransactionService;
    protected $itemService;
    protected $partyService;
    protected $paymentTypeService;
    protected $saleEmailNotificationService;
    protected $saleSmsNotificationService;
    protected $saleReturnEmailNotificationService;
    protected $saleReturnSmsNotificationService;
    protected $previousHistoryOfItems;
    protected $companyId;

    public function __construct(
        PaymentTransactionService $paymentTransactionService,
        AccountTransactionService $accountTransactionService,
        ItemTransactionService $itemTransactionService,
        ItemService $itemService,
        PartyService $partyService,
        PaymentTypeService $paymentTypeService,
        SaleEmailNotificationService $saleEmailNotificationService,
        SaleSmsNotificationService $saleSmsNotificationService,
        SaleReturnEmailNotificationService $saleReturnEmailNotificationService,
        SaleReturnSmsNotificationService $saleReturnSmsNotificationService
    ) {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->partyService = $partyService;
        $this->paymentTypeService = $paymentTypeService;
        $this->saleEmailNotificationService = $saleEmailNotificationService;
        $this->saleSmsNotificationService = $saleSmsNotificationService;
        $this->saleReturnEmailNotificationService = $saleReturnEmailNotificationService;
        $this->saleReturnSmsNotificationService = $saleReturnSmsNotificationService;
        $this->previousHistoryOfItems = [];
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
    }

       /**
     * Get all sales with filters
     *
     * @param Request $request
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

            if ($request->has('from_date') && $request->from_date) {
                $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
            }

            if ($request->has('to_date') && $request->to_date) {
                $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
            }

            if ($request->has('reference_no') && $request->reference_no) {
                $query->where('reference_no', 'like', "%{$request->reference_no}%");
            }

            // Check if user can view other users' sales
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices')) {
                $query->where('created_by', auth()->id());
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $sales = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Debug information
            Log::info('Sales API Query: ' . $query->toSql());
            Log::info('Sales Count: ' . $sales->count());
            Log::info('Total Sales: ' . $sales->total());

            $formattedSales = $sales->getCollection()->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sale_code' => $sale->sale_code,
                    'sale_date' => $this->toUserDateFormat($sale->sale_date),
                    'reference_no' => $sale->reference_no,
                    'party' => [
                        'id' => $sale->party->id ?? null,
                        'name' => $sale->party ? $sale->party->getFullName() : 'Unknown',
                        'phone' => $sale->party->phone ?? null,
                        'email' => $sale->party->email ?? null,
                    ],
                    'grand_total' => $this->formatWithPrecision($sale->grand_total, comma: false),
                    'paid_amount' => $this->formatWithPrecision($sale->paid_amount, comma: false),
                    'balance' => $this->formatWithPrecision($sale->grand_total - $sale->paid_amount, comma: false),
                    'note' => $sale->note,
                    'created_by' => $sale->user->username ?? '',
                    'created_at' => $this->toUserDateFormat($sale->created_at),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales retrieved successfully',
                'data' => $formattedSales,
                'pagination' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Sales API Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get a specific sale details
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
                    'item_id' => $transaction->item_id,
                    'item_name' => $transaction->item->name,
                    'item_code' => $transaction->item->item_code,
                    'warehouse_id' => $transaction->warehouse_id,
                    'quantity' => $this->formatWithPrecision($transaction->quantity, comma: false),
                    'unit_id' => $transaction->unit_id,
                    'unit_price' => $this->formatWithPrecision($transaction->unit_price, comma: false),
                    'discount' => $this->formatWithPrecision($transaction->discount, comma: false),
                    'discount_type' => $transaction->discount_type,
                    'discount_amount' => $this->formatWithPrecision($transaction->discount_amount, comma: false),
                    'tax_id' => $transaction->tax_id,
                    'tax_type' => $transaction->tax_type,
                    'tax_amount' => $this->formatWithPrecision($transaction->tax_amount, comma: false),
                    'total' => $this->formatWithPrecision($transaction->total, comma: false),
                    'description' => $transaction->description,
                ];

                // Add batch information if available
                if ($transaction->batch && $transaction->batch->itemBatchMaster) {
                    $data['batch'] = [
                        'batch_no' => $transaction->batch->itemBatchMaster->batch_no,
                        'mfg_date' => $transaction->batch->itemBatchMaster->mfg_date ? $this->toUserDateFormat($transaction->batch->itemBatchMaster->mfg_date) : null,
                        'exp_date' => $transaction->batch->itemBatchMaster->exp_date ? $this->toUserDateFormat($transaction->batch->itemBatchMaster->exp_date) : null,
                        'model_no' => $transaction->batch->itemBatchMaster->model_no,
                        'color' => $transaction->batch->itemBatchMaster->color,
                        'size' => $transaction->batch->itemBatchMaster->size,
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
                'prefix_code' => $sale->prefix_code,
                'count_id' => $sale->count_id,
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
                'round_off' => $this->formatWithPrecision($sale->round_off, comma: false),
                'note' => $sale->note,
                'state_id' => $sale->state_id,
                'currency_id' => $sale->currency_id,
                'exchange_rate' => $sale->exchange_rate,
                'created_by' => $sale->user->username ?? '',
                'created_at' => $this->toUserDateFormat($sale->created_at),
                'updated_at' => $this->toUserDateFormat($sale->updated_at),
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
     * Create a new sale
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
                'sale_date' => 'required|date',
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

            // Generate sale code
            $prefix = Prefix::findOrNew($this->companyId);
            $lastCountId = $this->getLastCountId();
            $saleCode = $prefix->sale . '-' . str_pad(($lastCountId + 1), 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'party_id' => $request->party_id,
                'sale_date' => $request->sale_date,
                'reference_no' => $request->reference_no,
                'prefix_code' => $prefix->sale,
                'count_id' => ($lastCountId + 1),
                'sale_code' => $saleCode,
                'note' => $request->note,
                'round_off' => $request->round_off ?? 0,
                'grand_total' => $request->grand_total,
                'state_id' => $request->state_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
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
                    'transaction_date' => $request->sale_date,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? null,
                    'tracking_type' => $itemDetails->tracking_type,
                    'input_quantity' => $item['quantity'],
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
                            'mfg_date' => isset($item['batch']['mfg_date']) ? $item['batch']['mfg_date'] : null,
                            'exp_date' => isset($item['batch']['exp_date']) ? $item['batch']['exp_date'] : null,
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
                            'transaction_date' => $request->sale_date,
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
                'sale_date' => 'required|date',
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
                'sale_date' => $request->sale_date,
                'reference_no' => $request->reference_no,
                'note' => $request->note,
                'round_off' => $request->round_off ?? 0,
                'grand_total' => $request->grand_total,
                'state_id' => $request->state_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
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
                    'transaction_date' => $request->sale_date,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? null,
                    'tracking_type' => $itemDetails->tracking_type,
                    'input_quantity' => $item['quantity'],
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
                            'mfg_date' => isset($item['batch']['mfg_date']) ? $item['batch']['mfg_date'] : null,
                            'exp_date' => isset($item['batch']['exp_date']) ? $item['batch']['exp_date'] : null,
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
                            'transaction_date' => $request->sale_date,
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

            // Update account transactions before deleting
            foreach ($sale->accountTransaction as $saleAccount) {
                $saleAccountId = $saleAccount->account_id;
                $saleAccount->delete();
                $this->accountTransactionService->calculateAccounts($saleAccountId);
            }

            // Delete payment transactions and update accounts
            $paymentTransactions = $sale->paymentTransaction;
            if ($paymentTransactions->isNotEmpty()) {
                foreach ($paymentTransactions as $paymentTransaction) {
                    $paymentTransaction->delete();
                }
            }

            $itemIdArray = [];
            // Delete item transactions
            foreach ($sale->itemTransaction as $itemTransaction) {
                $itemId = $itemTransaction->item_id;
                $itemTransaction->delete();
                $itemIdArray[] = $itemId;
            }

            // Delete the sale
            $sale->delete();

            // Update previous history of items
            $this->itemTransactionService->updatePreviousHistoryOfItems(null, $this->previousHistoryOfItems);

            // Update item stock
            if (count($itemIdArray) > 0) {
                foreach ($itemIdArray as $itemId) {
                    $this->itemService->updateItemStock($itemId);
                }
            }

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

    /**
     * Convert sale to return
     *
     * @param int $id
     * @return JsonResponse
     */
    public function convertToReturn(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find the sale
            $sale = Sale::with([
                'party',
                'itemTransaction' => [
                    'item',
                    'tax',
                    'batch.itemBatchMaster',
                    'itemSerialTransaction.itemSerialMaster'
                ]
            ])->findOrFail($id);

            // Check if user can convert this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to convert this sale'
                ], 403);
            }

            // Validate request for return details
            $validator = Validator::make($request->all(), [
                'return_date' => 'required|date',
                'note' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:items,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate return code
            $prefix = Prefix::findOrNew($this->companyId);
            $lastReturnCountId = $this->getLastReturnCountId();
            $returnCode = $prefix->sale_return . '-' . str_pad(($lastReturnCountId + 1), 4, '0', STR_PAD_LEFT);

            // Calculate return total
            $returnTotal = 0;
            foreach ($request->items as $itemData) {
                // Find the original item transaction
                $originalTransaction = $sale->itemTransaction->where('item_id', $itemData['item_id'])->first();
                if ($originalTransaction) {
                    $returnTotal += ($originalTransaction->unit_price * $itemData['quantity']);
                }
            }

            // Create sale return
            $saleReturn = SaleReturn::create([
                'return_date' => $request->return_date,
                'reference_no' => $sale->sale_code,
                'prefix_code' => $prefix->sale_return,
                'count_id' => ($lastReturnCountId + 1),
                'return_code' => $returnCode,
                'party_id' => $sale->party_id,
                'state_id' => $sale->state_id,
                'note' => $request->note,
                'round_off' => 0,
                'grand_total' => $returnTotal,
                'paid_amount' => 0,
                'currency_id' => $sale->currency_id,
                'exchange_rate' => $sale->exchange_rate,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Process return items
            foreach ($request->items as $itemData) {
                $originalTransaction = $sale->itemTransaction->where('item_id', $itemData['item_id'])->first();
                if (!$originalTransaction) {
                    throw new \Exception("Item not found in original sale");
                }

                $itemDetails = Item::find($itemData['item_id']);

                // Validate return quantity
                if ($itemData['quantity'] > $originalTransaction->quantity) {
                    throw new \Exception("Return quantity cannot exceed sold quantity for item: " . $itemDetails->name);
                }

                // Record return item transaction
                $transaction = $this->itemTransactionService->recordItemTransactionEntry($saleReturn, [
                    'warehouse_id' => $originalTransaction->warehouse_id,
                    'transaction_date' => $request->return_date,
                    'item_id' => $itemData['item_id'],
                    'description' => $itemData['reason'] ?? 'Return from sale: ' . $sale->sale_code,
                    'tracking_type' => $itemDetails->tracking_type,
                    'input_quantity' => $itemData['quantity'],
                    'quantity' => $itemData['quantity'],
                    'unit_id' => $originalTransaction->unit_id,
                    'unit_price' => $originalTransaction->unit_price,
                    'mrp' => $originalTransaction->mrp,
                    'discount' => $originalTransaction->discount,
                    'discount_type' => $originalTransaction->discount_type,
                    'discount_amount' => ($originalTransaction->discount_amount / $originalTransaction->quantity) * $itemData['quantity'],
                    'tax_id' => $originalTransaction->tax_id,
                    'tax_type' => $originalTransaction->tax_type,
                    'tax_amount' => ($originalTransaction->tax_amount / $originalTransaction->quantity) * $itemData['quantity'],
                    'total' => $originalTransaction->unit_price * $itemData['quantity'],
                ]);

                if (!$transaction) {
                    throw new \Exception("Failed to record return item transaction!");
                }

                // Handle batch/serial items if needed
                if ($itemDetails->tracking_type == 'batch' && $originalTransaction->batch) {
                    $batchTransaction = $this->itemTransactionService->recordItemBatches(
                        $transaction->id,
                        [
                            'batch_no' => $originalTransaction->batch->itemBatchMaster->batch_no,
                            'mfg_date' => $originalTransaction->batch->itemBatchMaster->mfg_date,
                            'exp_date' => $originalTransaction->batch->itemBatchMaster->exp_date,
                            'model_no' => $originalTransaction->batch->itemBatchMaster->model_no,
                            'mrp' => $originalTransaction->batch->itemBatchMaster->mrp,
                            'color' => $originalTransaction->batch->itemBatchMaster->color,
                            'size' => $originalTransaction->batch->itemBatchMaster->size,
                            'quantity' => $itemData['quantity'],
                        ],
                        $itemData['item_id'],
                        $originalTransaction->warehouse_id,
                        ItemTransactionUniqueCode::SALE_RETURN->value
                    );

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sale converted to return successfully',
                'data' => [
                    'return_id' => $saleReturn->id,
                    'return_code' => $saleReturn->return_code,
                    'original_sale_code' => $sale->sale_code
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
     * Send email notification for sale
     *
     * @param int $id
     * @return JsonResponse
     */
    public function sendEmail($id): JsonResponse
    {
        try {
            $sale = Sale::with('party')->findOrFail($id);

            // Check if user can send email for this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to send email for this sale'
                ], 403);
            }

            // Check if party has email
            if (empty($sale->party->email)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Party email address is not available'
                ], 400);
            }

            // Get email content
            $emailData = $this->saleEmailNotificationService->saleCreatedEmailNotification($id);

            if (!$emailData['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $emailData['message']
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Email sent successfully',
                'data' => [
                    'email' => $sale->party->email,
                    'subject' => $emailData['data']['subject'] ?? '',
                    'content' => $emailData['data']['content'] ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send SMS notification for sale
     *
     * @param int $id
     * @return JsonResponse
     */
    public function sendSms($id): JsonResponse
    {
        try {
            $sale = Sale::with('party')->findOrFail($id);

            // Check if user can send SMS for this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to send SMS for this sale'
                ], 403);
            }

            // Check if party has mobile number
            if (empty($sale->party->mobile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Party mobile number is not available'
                ], 400);
            }

            // Get SMS content
            $smsData = $this->saleSmsNotificationService->saleCreatedSmsNotification($id);

            if (!$smsData['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $smsData['message']
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'SMS sent successfully',
                'data' => [
                    'mobile' => $smsData['data']['mobile'] ?? $sale->party->mobile,
                    'content' => $smsData['data']['content'] ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email content for preview
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getEmailContent($id): JsonResponse
    {
        try {
            $sale = Sale::with('party')->findOrFail($id);

            // Check if user can view this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to view this sale'
                ], 403);
            }

            $emailData = $this->saleEmailNotificationService->saleCreatedEmailNotification($id);

            $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
            $content = ($emailData['status']) ? $emailData['data']['content'] : '';

            return response()->json([
                'status' => true,
                'message' => 'Email content retrieved successfully',
                'data' => [
                    'email' => $sale->party->email,
                    'subject' => $subject,
                    'content' => $content,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS content for preview
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getSmsContent($id): JsonResponse
    {
        try {
            $sale = Sale::with('party')->findOrFail($id);

            // Check if user can view this sale
            if (!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices') && $sale->created_by !== auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to view this sale'
                ], 403);
            }

            $smsData = $this->saleSmsNotificationService->saleCreatedSmsNotification($id);

            $mobile = ($smsData['status']) ? $smsData['data']['mobile'] : '';
            $content = ($smsData['status']) ? $smsData['data']['content'] : '';

            return response()->json([
                'status' => true,
                'message' => 'SMS content retrieved successfully',
                'data' => [
                    'mobile' => $mobile,
                    'content' => $content,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sold items data for return
     *
     * @param int $partyId
     * @param int|null $itemId
     * @return JsonResponse
     */
    public function getSoldItemsData($partyId, $itemId = null): JsonResponse
    {
        try {
            $sales = Sale::with([
                'party',
                'itemTransaction' => fn($query) => $query->when($itemId, fn($q) => $q->where('item_id', $itemId)),
                'itemTransaction.item.brand',
                'itemTransaction.item.tax',
                'itemTransaction.warehouse'
            ])
                ->where('party_id', $partyId)
                ->get();

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No sales records found for this party'
                ], 404);
            }

            // Extract the first party name for display
            $partyName = $sales->first()->party->getFullName();

            $data = $sales->map(function ($sale) {
                return [
                    'sold_items' => $sale->itemTransaction->map(function ($transaction) use ($sale) {
                        return [
                            'id' => $transaction->id,
                            'sale_id' => $sale->id,
                            'sale_code' => $sale->sale_code,
                            'sale_date' => $this->toUserDateFormat($sale->sale_date),
                            'warehouse_id' => $transaction->warehouse_id,
                            'warehouse_name' => $transaction->warehouse->name,
                            'item_id' => $transaction->item_id,
                            'item_name' => $transaction->item->name,
                            'item_code' => $transaction->item->item_code,
                            'brand_name' => $transaction->item->brand->name ?? '',
                            'unit_price' => $this->formatWithPrecision($transaction->unit_price, comma: false),
                            'quantity' => $this->formatWithPrecision($transaction->quantity, comma: false),
                            'discount_amount' => $this->formatWithPrecision($transaction->discount_amount, comma: false),
                            'tax_id' => $transaction->tax_id,
                            'tax_name' => $transaction->item->tax->name ?? '',
                            'tax_amount' => $this->formatWithPrecision($transaction->tax_amount, comma: false),
                            'total' => $this->formatWithPrecision($transaction->total, comma: false),
                        ];
                    })->toArray(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Sold items data retrieved successfully',
                'data' => [
                    'party_name' => $partyName,
                    'sold_items' => $data->flatMap(function ($sale) {
                        return $sale['sold_items'];
                    })->toArray(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get last count ID for sales
     *
     * @return int
     */
    private function getLastCountId(): int
    {
        return Sale::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * Get last count ID for sale returns
     *
     * @return int
     */
    private function getLastReturnCountId(): int
    {
        return SaleReturn::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }
}

