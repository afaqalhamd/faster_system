<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Prefix;
use App\Models\Sale\SaleOrder;
use App\Models\Items\Item;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\App;
use App\Services\PaymentTypeService;
use App\Services\GeneralDataService;
use App\Services\PaymentTransactionService;
use App\Http\Requests\SaleOrderRequest;
use App\Services\AccountTransactionService;
use App\Services\ItemTransactionService;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\StatusHistoryService;
use App\Services\Communication\Email\SaleOrderEmailNotificationService;
use App\Services\Communication\Sms\SaleOrderSmsNotificationService;
use App\Enums\ItemTransactionUniqueCode;
use App\Services\SaleOrderStatusService; // Added this line

use Mpdf\Mpdf;
use App\Notifications\NewSaleOrderNotification;
use App\Models\User;
use App\Notifications\SaleOrderStatusNotification;

class SaleOrderController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    public $saleOrderEmailNotificationService;

    public $saleOrderSmsNotificationService;

    public $generalDataService;

    public $statusHistoryService;

    public $saleOrderStatusService; // Added this line

    public function __construct(
        PaymentTypeService                $paymentTypeService,
        PaymentTransactionService         $paymentTransactionService,
        AccountTransactionService         $accountTransactionService,
        ItemTransactionService            $itemTransactionService,
        SaleOrderEmailNotificationService $saleOrderEmailNotificationService,
        SaleOrderSmsNotificationService   $saleOrderSmsNotificationService,
        GeneralDataService                $generalDataService,
        StatusHistoryService              $statusHistoryService,
        SaleOrderStatusService            $saleOrderStatusService // Added this parameter
    )
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->saleOrderEmailNotificationService = $saleOrderEmailNotificationService;
        $this->saleOrderSmsNotificationService = $saleOrderSmsNotificationService;
        $this->generalDataService = $generalDataService;
        $this->statusHistoryService = $statusHistoryService;
        $this->saleOrderStatusService = $saleOrderStatusService; // Added this line
    }

    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());

        // Prepare empty item transactions structure similar to edit method
        $itemTransactionsJson = json_encode([]);

        // Get tax list for frontend
        $taxList = CacheService::get('tax')->toJson();

        $data = [
            'prefix_code' => $prefix->sale_order,
            'count_id' => ($lastCountId + 1),
        ];

        return view('sale.order.create', compact('data', 'selectedPaymentTypesArray', 'itemTransactionsJson', 'taxList'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId()
    {
        return SaleOrder::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the orders
     *
     * @return \Illuminate\View\View
     */
    public function list(): View
    {
        return view('sale.order.list');
    }


    /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id): View
    {
        $order = SaleOrder::with(['party',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ],
            'saleOrderStatusHistories' => [
                'changedBy'
            ]])->findOrFail($id);
        // Add formatted dates from ItemBatchMaster model
        $order->itemTransaction->each(function ($transaction) {
            if (!$transaction->batch?->itemBatchMaster) {
                return;
            }
            $batchMaster = $transaction->batch->itemBatchMaster;
            $batchMaster->mfg_date = $batchMaster->getFormattedMfgDateAttribute();
            $batchMaster->exp_date = $batchMaster->getFormattedExpDateAttribute();
        });

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $order->itemTransaction->map(function ($transaction) use ($allUnits) {
            $itemData = $transaction->toArray();

            // Use the getOnlySelectedUnits helper function
            $selectedUnits = getOnlySelectedUnits(
                $allUnits,
                $transaction->item->base_unit_id,
                $transaction->item->secondary_unit_id
            );

            // Add unitList to the item data
            $itemData['unitList'] = $selectedUnits->toArray();

            // Get item serial transactions with associated item serial master data
            $itemSerialTransactions = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                return $serialTransaction->itemSerialMaster->toArray();
            })->toArray();

            // Add itemSerialTransactions to the item data
            $itemData['itemSerialTransactions'] = $itemSerialTransactions;

            return $itemData;
        })->toArray();

        $itemTransactionsJson = json_encode($itemTransactions);

        //Default Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());

        //Get the previous payments
        $paymentHistory = $this->paymentTransactionService->getPaymentRecordsArray($order);

        $taxList = CacheService::get('tax')->toJson();

        return view('sale.order.edit', compact('taxList', 'order', 'itemTransactionsJson', 'selectedPaymentTypesArray', 'paymentHistory'));
    }

    /**
     * View Sale Order details
     *
     * @param int $id , the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id): View
    {
        $order = SaleOrder::with(['party',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ],
            'saleOrderStatusHistories' => [
                'changedBy'
            ]])->find($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($order));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        return view('sale.order.details', compact('order', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
    }

    /**
     * Print Sale Order
     *
     * @param int $id , the ID of the order
     * @return \Illuminate\View\View
     */
    public function print($id, $isPdf = false): View
    {
        $order = SaleOrder::with(['party',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ]])->find($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($order));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.order.order'),
        ];

        return view('print.sale-order.print', compact('isPdf', 'invoiceData', 'order', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
        //return view('sale.order.unused-print', compact('order','selectedPaymentTypesArray','batchTrackingRowCount'));
    }


    /**
     * Generate PDF using View: print() method
     * */
    public function generatePdf($id)
    {
        $html = $this->print($id, isPdf: true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
            'default_font' => 'dejavusans',
            //'direction' => 'rtl',
        ]);
        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML($html);
        /**
         * Display in browser
         * 'I'
         * Downloadn PDF
         * 'D'
         * */
        $mpdf->Output('Sale-Order-' . $id . '.pdf', 'D');
    }

    /**
     * Store Records
     * */
    public function store(SaleOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            // Get the validated data from the expenseRequest
            $validatedData = $request->validated();

            if ($request->operation == 'save') {
                // Create a new expense record using Eloquent and save it
                $newSaleOrder = SaleOrder::create($validatedData);

                // Send notification to all users with appropriate permissions
                $users = User::permission('sale.order.view')->get();
                foreach ($users as $user) {
                    $user->notify(new NewSaleOrderNotification($newSaleOrder));
                }

                $request->request->add(['sale_order_id' => $newSaleOrder->id]);
            } else {
                $fillableColumns = [
                    'party_id' => $validatedData['party_id'],
                    'order_date' => $validatedData['order_date'],
                    'due_date' => $validatedData['due_date'],
                    'prefix_code' => $validatedData['prefix_code'],
                    'count_id' => $validatedData['count_id'],
                    'order_code' => $validatedData['order_code'],
                    'note' => $validatedData['note'],
                    'round_off' => $validatedData['round_off'],
                    'grand_total' => $validatedData['grand_total'],
                    'state_id' => $validatedData['state_id'],
                    'carrier_id' => $validatedData['carrier_id'] ?? null,
                    'order_status' => $validatedData['order_status'],
                    'currency_id' => $validatedData['currency_id'],
                    'exchange_rate' => $validatedData['exchange_rate'],
                    'shipping_charge' => $validatedData['shipping_charge'] ?? 0,
                    'is_shipping_charge_distributed' => $validatedData['is_shipping_charge_distributed'] ?? 0,
                ];

                $newSaleOrder = SaleOrder::findOrFail($validatedData['sale_order_id']);
                $newSaleOrder->update($fillableColumns);
                $newSaleOrder->itemTransaction()->delete();
                // $newSaleOrder->accountTransaction()->delete();
                // // Check if paymentTransactions exist
                // $paymentTransactions = $newSaleOrder->paymentTransaction;
                // if ($paymentTransactions->isNotEmpty()) {
                //     foreach ($paymentTransactions as $paymentTransaction) {
                //         $accountTransactions = $paymentTransaction->accountTransaction;
                //         if ($accountTransactions->isNotEmpty()) {
                //             foreach ($accountTransactions as $accountTransaction) {
                //                 // Do something with the individual accountTransaction
                //                 $accountTransaction->delete(); // Or any other operation
                //             }
                //         }
                //     }
                // }
                // $newSaleOrder->paymentTransaction()->delete();


            }

            /**
             * Record Status Update History
             */
            $this->statusHistoryService->RecordStatusHistory($newSaleOrder);

            $request->request->add(['modelName' => $newSaleOrder]);

            /**
             * Save Table Items in Sale Order Items Table
             * */
            $saleOrderItemsArray = $this->saveSaleOrderItems($request);
            if (!$saleOrderItemsArray['status']) {
                throw new \Exception($saleOrderItemsArray['message']);
            }

            /**
             * Save Expense Payment Records
             * */
            $saleOrderPaymentsArray = $this->saveSaleOrderPayments($request);
            if (!$saleOrderPaymentsArray['status']) {
                throw new \Exception($saleOrderPaymentsArray['message']);
            }

            // Removed payment amount validation to allow saving orders regardless of payment status
            // Payment validation can be handled at a later stage

            /**
             * Update Sale Order Model
             * Total Paid Amunt
             * */
            if (!$this->paymentTransactionService->updateTotalPaidAmountInModel($request->modelName)) {
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            /**
             * Update Account Transaction entry
             * Call Services
             * @return boolean
             * */
            // $accountTransactionStatus = $this->accountTransactionService->saleOrderAccountTransaction($request->modelName);
            // if(!$accountTransactionStatus){
            //     throw new \Exception(__('payment.failed_to_update_account'));
            // }

            DB::commit();

            // Regenerate the CSRF token
            //Session::regenerateToken();

            return response()->json([
                'status' => false,
                'message' => __('app.record_saved_successfully'),
                'id' => $request->sale_order_id,

            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }

    }

    public function row_save(Request $request)
    {
//        $item_id = $request->item_id;
//        $item_location = $request->item_location;
        $t_id = $request->t_id;
        $qty = $request->qty;
        $item_Transaction = ItemTransaction::find($t_id);
        $item_Transaction->update([
            'quantity' => $qty,
            'status' => 'save',
        ]);
        return response()->json([
            'status' => true,
            'message' => $request->all(),
            'item_id' => $request->item_id,
        ], 200);
    }

    public function saveSaleOrderPayments($request)
    {
        $paymentCount = $request->row_count_payments;

        for ($i = 0; $i <= $paymentCount; $i++) {

            /**
             * If array record not exist then continue forloop
             * */
            if (!isset($request->payment_amount[$i])) {
                continue;
            }

            /**
             * Data index start from 0
             * */
            $amount = $request->payment_amount[$i];

            if ($amount > 0) {
                if (!isset($request->payment_type_id[$i])) {
                    return [
                        'status' => false,
                        'message' => __('payment.missed_to_select_payment_type') . "#" . $i,
                    ];
                }

                $paymentsArray = [
                    'transaction_date' => $request->order_date,
                    'amount' => $amount,
                    'payment_type_id' => $request->payment_type_id[$i],
                    'note' => $request->payment_note[$i],
                ];

                if (!$transaction = $this->paymentTransactionService->recordPayment($request->modelName, $paymentsArray)) {
                    throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                }

            }//amount>0
        }//for end

        // After all payments are processed, check if inventory should be deducted
        if (isset($request->modelName)) {
            $this->checkAndProcessInventoryDeduction($request->modelName);
        }

        return ['status' => true];
    }

    /**
     * Process inventory deduction after payment completion
     * This method should be called when payment is fully completed
     *
     * @param SaleOrder $saleOrder
     * @return JsonResponse
     */
    public function processInventoryDeduction(SaleOrder $saleOrder): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if order is already processed
            if ($saleOrder->inventory_status === 'deducted') {
                return response()->json([
                    'status' => false,
                    'message' => __('sale.inventory_already_deducted')
                ]);
            }

            // Only deduct inventory if status is POD
            if ($saleOrder->order_status !== 'POD') {
                return response()->json([
                    'status' => false,
                    'message' => __('sale.inventory_deduction_only_for_pod')
                ]);
            }

            // Process inventory deduction for each item
            foreach ($saleOrder->itemTransaction as $transaction) {
                // Update the transaction unique code from SALE_ORDER to SALE
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE->value
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterPayment($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterPayment($transaction);
                }
            }

            // Update sale order status
            $saleOrder->update([
                'inventory_status' => 'deducted',
                'inventory_deducted_at' => now()
            ]);

            // Record status history
            $this->statusHistoryService->RecordStatusHistory($saleOrder);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('sale.inventory_deducted_successfully')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update batch inventory after payment
     */
    private function updateBatchInventoryAfterPayment($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Check and process inventory deduction after payment update
     * This should be called whenever a payment is added to a sale order
     *
     * @param SaleOrder $saleOrder
     * @return bool
     */
    public function checkAndProcessInventoryDeduction(SaleOrder $saleOrder): bool
    {
        // Always return false since inventory deduction is now only handled by status change
        return false;
    }

    /**
     * Manual inventory deduction endpoint
     * For admin users to manually trigger inventory deduction
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function manualInventoryDeduction(Request $request, int $id): JsonResponse
    {
        $saleOrder = SaleOrder::with(['itemTransaction', 'paymentTransaction'])->findOrFail($id);

        // Only allow manual deduction if status is POD
        if ($saleOrder->order_status !== 'POD') {
            return response()->json([
                'status' => false,
                'message' => __('sale.inventory_deduction_only_for_pod')
            ], 400);
        }

        return $this->processInventoryDeduction($saleOrder);
    }

    public function saveSaleOrderItems($request)
    {
        $itemsCount = $request->row_count;

        for ($i = 0; $i < $itemsCount; $i++) {
            /**
             * If array record not exist then continue forloop
             * */
            if (!isset($request->item_id[$i])) {
                continue;
            }

            /**
             * Data index start from 0
             * */
            $itemDetails = Item::find($request->item_id[$i]);
            $itemName = $itemDetails->name;

            //validate input Quantity
            $itemQuantity = $request->quantity[$i];
            if (empty($itemQuantity) || $itemQuantity === 0 || $itemQuantity < 0) {
                return [
                    'status' => false,
                    'message' => ($itemQuantity < 0) ? __('item.item_qty_negative', ['item_name' => $itemName]) : __('item.please_enter_item_quantity', ['item_name' => $itemName]),
                ];
            }

            /**
             *
             * Item Transaction Entry
             * */
            $transaction = $this->itemTransactionService->recordItemTransactionEntry($request->modelName, [
                'warehouse_id' => $request->warehouse_id[$i],
                'transaction_date' => $request->order_date,
                'item_id' => $request->item_id[$i],
                'description' => $request->description[$i] ?? '',

                'tracking_type' => $itemDetails->tracking_type,

                'input_quantity' => $request->input_quantity[$i],
                'quantity' => $itemQuantity,
                'unit_id' => $request->unit_id[$i],
                'unit_price' => $request->sale_price[$i],
                'mrp' => $request->mrp[$i] ?? 0,

                'discount' => $request->discount[$i],
                'discount_type' => $request->discount_type[$i],
                'discount_amount' => $request->discount_amount[$i],

                'charge_type' => 'shipping',
                'charge_amount' => 0,

                'tax_id' => $request->tax_id[$i],
                'tax_type' => $request->tax_type[$i],
                'tax_amount' => $request->tax_amount[$i],

                'total' => $request->total[$i],

            ]);

            //return $transaction;
            if (!$transaction) {
                throw new \Exception("Failed to record Item Transaction Entry!");
            }

            /**
             * Tracking Type:
             * regular
             * batch
             * serial
             * */
            if ($itemDetails->tracking_type == 'serial') {
                //Serial validate and insert records
                if ($itemQuantity > 0) {
                    $jsonSerials = $request->serial_numbers[$i];
                    $jsonSerialsDecode = json_decode($jsonSerials);

                    /**
                     * Serial number count & Enter Quntity must be equal
                     * */
                    // $countRecords = (!empty($jsonSerialsDecode)) ? count($jsonSerialsDecode) : 0;
                    // if($countRecords != $itemQuantity){
                    //     throw new \Exception(__('item.opening_quantity_not_matched_with_serial_records'));
                    // }
                    if (!$jsonSerialsDecode) {
                        continue;
                    }
                    foreach ($jsonSerialsDecode as $serialNumber) {
                        $serialArray = [
                            'serial_code' => $serialNumber,
                        ];

                        $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::SALE_ORDER->value);

                        if (!$serialTransaction) {
                            throw new \Exception(__('item.failed_to_save_serials'));
                        }
                    }
                }
            } else if ($itemDetails->tracking_type == 'batch') {
                //Serial validate and insert records
                if ($itemQuantity > 0) {
                    /**
                     * Record Batch Entry for each batch
                     * */

                    $batchArray = [
                        'batch_no' => $request->batch_no[$i],
                        'mfg_date' => $request->mfg_date[$i] ? $this->toSystemDateFormat($request->mfg_date[$i]) : null,
                        'exp_date' => $request->exp_date[$i] ? $this->toSystemDateFormat($request->exp_date[$i]) : null,
                        'model_no' => $request->model_no[$i],
                        'mrp' => $request->mrp[$i] ?? 0,
                        'color' => $request->color[$i],
                        'size' => $request->size[$i],
                        'quantity' => $itemQuantity,
                    ];

                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $request->item_id[$i], $request->warehouse_id[$i], ItemTransactionUniqueCode::SALE_ORDER->value);

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            } else {
                //Regular item transaction entry already done before if() condition
            }


        }//for end

        /**
         * Update Shipping Cost
         * */
        $this->updateShippingCost($request->modelName);

        return ['status' => true];
    }

    public function updateShippingCost($model)
    {
        $itemTransactions = $model->refresh()->itemTransaction()->with('tax')->get();
        if ($itemTransactions->isNotEmpty() && $model->shipping_charge > 0 && $model->is_shipping_charge_distributed == 1) {

            //Calculate itemTransaction unit_price * quantity, give me sum of it
            //Use foreach
            $totalItemAmount = $itemTransactions->map(function ($itemTransaction) {
                return $itemTransaction->unit_price * $itemTransaction->quantity;
            })->sum();

            //Update charge_amount in itemTransaction model for each entry
            $itemTransactions->map(function ($itemTransaction) use ($model, $totalItemAmount) {
                $itemTransaction->charge_amount = ($model->shipping_charge / $totalItemAmount) * $itemTransaction->unit_price * $itemTransaction->quantity;
                /**
                 * Calculate Charge Tax Amount
                 * get the tax value from tax model, where tax is morph with itemTransaction as well
                 * Tax model has the taxrate column
                 */
                $itemTransaction->charge_tax_amount = ($itemTransaction->charge_amount * $itemTransaction->tax->rate) / 100;

                $itemTransaction->save();
            });

        }
    }


    /**
     * Helper method to check if current user has delivery role
     * @return bool
     */
    private function isDeliveryUser(): bool
    {
        $user = auth()->user();
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }

    /**
     * Apply delivery user filtering to query
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyDeliveryUserFilter($query)
    {
        if ($this->isDeliveryUser()) {
            // Delivery users can only see delivery status orders
            $query->where('order_status', 'Delivery');
        }
        return $query;
    }

    /**
     * Helper method to check if current user is associated with a carrier
     * @return bool
     */
    private function isCarrierUser(): bool
    {
        $user = auth()->user();
        return $user && $user->carrier_id && $user->role && strtolower($user->role->name) === 'delivery';
    }

    /**
     * Apply carrier filtering to query for delivery users
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyCarrierFilter($query)
    {
        $user = auth()->user();
        if ($user && $user->carrier_id) {
            // Carrier users can only see orders assigned to their carrier
            $query->where('carrier_id', $user->carrier_id)
                  ->whereIn('order_status', ['Delivery', 'Completed']);
        }
        return $query;
    }

    /**
     * Datatabale
     * */
    public function datatableList(Request $request)
    {

        $data = SaleOrder::with('user', 'party', 'sale', 'carrier')
            ->when($request->party_id, function ($query) use ($request) {
                return $query->where('party_id', $request->party_id);
            })
            ->when($request->user_id, function ($query) use ($request) {
                return $query->where('created_by', $request->user_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('order_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('order_date', '<=', $this->toSystemDateFormat($request->to_date));
            })
            // In your datatable-list method
            ->when($request->has('order_codes'), function ($query) use ($request) {
                $orderCodes = json_decode($request->order_codes);
                return $query->whereIn('order_code', $orderCodes);
            })
            ->when(!auth()->user()->can('sale.order.can.view.other.users.sale.orders'), function ($query) use ($request) {
                return $query->where('created_by', auth()->user()->id);
            })
            // Apply delivery user filter - restrict to completed orders only
            ->when($this->isDeliveryUser(), function ($query) {
                return $this->applyDeliveryUserFilter($query);
            })
            // Apply carrier filtering for delivery users
            ->when($this->isCarrierUser(), function ($query) {
                return $this->applyCarrierFilter($query);
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('order_code', 'like', "%{$searchTerm}%")
                            ->orWhere('grand_total', 'like', "%{$searchTerm}%")
                            ->orWhere('order_status', 'like', "%{$searchTerm}%")
                            ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                                $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                $userQuery->where('username', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('carrier', function ($carrierQuery) use ($searchTerm) {
                                $carrierQuery->where('name', 'like', "%{$searchTerm}%");
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('order_date', function ($row) {
                return $row->formatted_order_date;
            })
            ->addColumn('due_date', function ($row) {
                return $row->formatted_due_date;
            })
            ->addColumn('order_code', function ($row) {
                return $row->order_code;
            })
            ->addColumn('party_name', function ($row) {
                return $row->party->first_name . " " . $row->party->last_name;
            })
            ->addColumn('grand_total', function ($row) {
                return $this->formatWithPrecision($row->grand_total);
            })
            ->addColumn('balance', function ($row) {
                return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
            })
            ->addColumn('inventory_status', function ($row) {
                $status = $row->inventory_status ?? 'pending';
                $badgeClass = $status === 'deducted' ? 'bg-success' : 'bg-warning';
                $statusText = $status === 'deducted' ? __('Deducted') : __('Reserved');

                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('status', function ($row) {
                if ($row->sale) {
                    return [
                        'text' => "Converted to Sale",
                        'code' => $row->sale->sale_code,
                        'url' => route('sale.invoice.details', ['id' => $row->sale->id]),
                    ];
                }
                return [
                    'text' => "",
                    'code' => "",
                    'url' => "",
                ];
            })
            ->addColumn('carrier_name', function ($row) {
                return $row->carrier ? $row->carrier->name : 'N/A';
            })
            ->addColumn('color', function ($row) {
                $saleOrderStatus = $this->generalDataService->getSaleOrderStatus();

                // Find the status matching the given id
                return collect($saleOrderStatus)->firstWhere('id', $row->order_status)['color'];

            })
            ->addColumn('action', function ($row) {
                $id = $row->id;

                $editUrl = route('sale.order.edit', ['id' => $id]);

                //Verify is it converted or not
                // if ($row->sale) {
                //     $convertToSale = route('sale.invoice.details', ['id' => $row->sale->id]);
                //     $convertToSaleText = __('app.view_bill');
                //     $convertToSaleIcon = 'check-double';
                // } else {
                //     $convertToSale = route('sale.invoice.convert', ['id' => $id]);
                //     $convertToSaleText = __('sale.convert_to_sale');
                //     $convertToSaleIcon = 'transfer-alt';
                // }

                $detailsUrl = route('sale.order.details', ['id' => $id]);
                $printUrl = route('sale.order.print', ['id' => $id]);
                $pdfUrl = route('sale.order.pdf', ['id' => $id]);

                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bx bx-edit"></i> ' . __('app.edit') . '</a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="' . $detailsUrl . '"></i><i class="bx bx-show-alt"></i> ' . __('app.details') . '</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-printer "></i> ' . __('app.print') . '</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $pdfUrl . '"></i><i class="bx bxs-file-pdf"></i> ' . __('app.pdf') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-email" data-model="sale/order" data-id="' . $id . '" role="button"></i><i class="bx bx-envelope"></i> ' . __('app.send_email') . '</a>
                                </li>
                                 <li>
                                    <a class="dropdown-item make-payment" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-money"></i> ' . __('payment.receive_payment') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item payment-history" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> ' . __('payment.history') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-sms" data-model="sale/order" data-id="' . $id . '" role="button"></i><i class="bx bx-envelope"></i> ' . __('app.send_sms') . '</a>
                                </li>

                                <li>
                                    <a class="dropdown-item status-history" data-model="statusHistoryModal" data-id="' . $id . '" role="button"></i><i class="bx bx-book"></i> ' . __('app.status_history') . '</a>
                                </li>

                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id=' . $id . '><i class="bx bx-trash"></i> ' . __('app.delete') . '</button>
                                </li>
                            </ul>
                        </div>';
                return $actionBtn;
            })
            ->rawColumns(['action', 'inventory_status'])
            ->make(true);
    }

    /**
     * Delete Sale Order Records
     * @return JsonResponse
     * */
    public function delete(Request $request): JsonResponse
    {

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = SaleOrder::find($recordId);
            if (!$record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status' => false,
                    'message' => __('app.invalid_record_id', ['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */


        try {
            // Attempt deletion (as in previous responses)
            // SaleOrder::whereIn('id', $selectedRecordIds)->chunk(100, function ($orders) {
            //     foreach ($orders as $order) {
            //         $order->accountTransaction()->delete();
            //         //Load Sale Order Payment Transactions
            //         $payments = $order->paymentTransaction;
            //         foreach ($payments as $payment) {
            //             //Delete Payment Account Transactions
            //             $payment->accountTransaction()->delete();

            //             //Delete Sale Order Payment Transactions
            //             $payment->delete();
            //         }
            //     }
            // });

            // //Delete Sale Order
            // $deletedCount = SaleOrder::whereIn('id', $selectedRecordIds)->delete();

            // Attempt deletion (as in previous responses)
            SaleOrder::whereIn('id', $selectedRecordIds)->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    //Sale Account Update
                    foreach ($order->accountTransaction as $orderAccount) {
                        //get account if of model with tax accounts
                        $orderAccountId = $orderAccount->account_id;

                        //Delete sale and tax account
                        $orderAccount->delete();

                        //Update  account
                        $this->accountTransactionService->calculateAccounts($orderAccountId);
                    }//sale account

                    // Check if paymentTransactions exist
                    $paymentTransactions = $order->paymentTransaction;
                    if ($paymentTransactions->isNotEmpty()) {
                        foreach ($paymentTransactions as $paymentTransaction) {
                            // $accountTransactions = $paymentTransaction->accountTransaction;
                            // if ($accountTransactions->isNotEmpty()) {
                            //     foreach ($accountTransactions as $accountTransaction) {
                            //         //Sale Account Update
                            //         $accountId = $accountTransaction->account_id;
                            //         // Do something with the individual accountTransaction
                            //         $accountTransaction->delete(); // Or any other operation

                            //         $this->accountTransactionService->calculateAccounts($accountId);
                            //     }
                            // }

                            //delete Payment now
                            $paymentTransaction->delete();
                        }
                    }//isNotEmpty

                    //delete item Transactions
                    $order->itemTransaction()->delete();

                    //Delete Status History data
                    $order->statusHistory()->delete();

                    //Delete order
                    $order->delete();


                }//sales
            });

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => __('app.cannot_delete_records'),
            ], 409);
        }
    }

    /**
     * Prepare Email Content to view
     * */
    public function getEmailContent($id)
    {
        $model = SaleOrder::with('party')->find($id);

        $emailData = $this->saleOrderEmailNotificationService->saleOrderCreatedEmailNotification($id);

        $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'email' => $model->party->email,
            'subject' => $subject,
            'content' => $content,
        ];
        return $data;
    }

    /**
     * Prepare SMS Content to view
     * */
    public function getSMSContent($id)
    {
        $model = SaleOrder::with('party')->find($id);

        $emailData = $this->saleOrderSmsNotificationService->saleOrderCreatedSmsNotification($id);

        $mobile = ($emailData['status']) ? $emailData['data']['mobile'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'mobile' => $mobile,
            'content' => $content,
        ];
        return $data;
    }

    /***
     * View Status History
     *
     * */
    public function getStatusHistory($id): JsonResponse
    {
        try {
            $saleOrder = SaleOrder::findOrFail($id);

            // Use the SaleOrderStatusService to get the status history
            $history = $this->saleOrderStatusService->getStatusHistory($saleOrder);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve status history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sale order status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $saleOrder = SaleOrder::findOrFail($request->id);

            // Use the SaleOrderStatusService to update the status
            $result = $this->saleOrderStatusService->updateSaleOrderStatus(
                $saleOrder,
                $request->status,
                [
                    'notes' => $request->notes,
                    'proof_image' => $request->file('proof_image')
                ]
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale order status: ' . $e->getMessage()
            ], 500);
        }
    }

}
