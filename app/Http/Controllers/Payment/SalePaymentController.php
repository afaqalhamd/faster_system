<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\General;

use App\Services\PaymentTransactionService;
use App\Services\AccountTransactionService;

use App\Http\Controllers\Sale\SaleController;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleOrder;
use App\Models\PaymentTransaction;
use App\Services\PartyService;

use Mpdf\Mpdf;

class SalePaymentController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    private $paymentTransactionService;
    private $accountTransactionService;
    private $partyService;

    public function __construct(
                                PaymentTransactionService $paymentTransactionService,
                                AccountTransactionService $accountTransactionService,
                                PartyService $partyService
                            )
    {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->partyService = $partyService;
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
     * @param string $type The transaction type (Sale::class or SaleOrder::class)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyCarrierFilter($query, string $type)
    {
        $user = auth()->user();
        if ($this->isCarrierUser()) {
            // For delivery users, filter payments based on:
            // 1. The carrier_id in the related models (Sale or SaleOrder)
            // 2. The user who created the payment (created_by field)
            if ($type === Sale::class) {
                // Filter by carrier_id in Sale model and created_by user
                $query->whereHas('transaction', function ($subQuery) use ($user) {
                    $subQuery->where('carrier_id', $user->carrier_id);
                })->where('created_by', $user->id);
            } elseif ($type === SaleOrder::class) {
                // Filter by carrier_id in SaleOrder model and created_by user
                $query->whereHas('transaction', function ($subQuery) use ($user) {
                    $subQuery->where('carrier_id', $user->carrier_id);
                })->where('created_by', $user->id);
            }
        }
        return $query;
    }

    /***
     * View Payment History
     *
     * */
    public function getSaleBillPaymentHistory($id) : JsonResponse{

        $data = $this->getSaleBillPaymentHistoryData($id);

        return response()->json([
            'status' => true,
            'message' => '',
            'data'  => $data,
        ]);

    }

    /**
     * Print Sale Bill Payment
     *
     * @param int $id, the ID of the sale bill payment
     * @return \Illuminate\View\View
     */
    public function printSaleBillPayment($id, $isPdf = false) : View {
        $payment = PaymentTransaction::with('paymentType')->find($id);

        $saleId = $payment->transaction_id;

        $sale = Sale::with('party')->find($saleId);

        $balanceData = $this->partyService->getPartyBalance($sale->party->id);

        return view('print.invoice-payment-receipt', compact('isPdf', 'sale', 'payment', 'balanceData'));
    }

    /**
     * Generate PDF using View: print() method
     * */
    public function pdfSaleBillPayment($id){
        $html = $this->printSaleBillPayment($id, isPdf:true);

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
         * Download PDF
         * 'D'
         * */
        $mpdf->Output('Sale-Bill-Payment-'.$id.'.pdf', 'D');
    }

    public function getSaleBillPayment($id) : JsonResponse{
        $model = Sale::with('party')->find($id);

        $data = [
            'party_id'  => $model->party->id,
            'party_name'  => $model->party->first_name.' '.$model->party->last_name,
            'balance'  => ($model->grand_total - $model->paid_amount),
            'invoice_id'  => $id,
            'form_heading' => __('payment.receive_payment'),
        ];

        return response()->json([
            'status' => true,
            'message' => '',
            'data'  => $data,
        ]);

    }

    public function deleteSaleBillPayment($paymentId) : JsonResponse{
        try {
            DB::beginTransaction();
            $paymentTransaction = PaymentTransaction::find($paymentId);
            if(!$paymentTransaction){
                throw new \Exception(__('payment.failed_to_delete_payment_transactions'));
            }

            //Sale model id
            $saleId = $paymentTransaction->transaction_id;

            // Find the related account transaction
            $accountTransactions = $paymentTransaction->accountTransaction;
            if ($accountTransactions->isNotEmpty()) {
                foreach ($accountTransactions as $accountTransaction) {
                    $accountId = $accountTransaction->account_id;
                    // Do something with the individual accountTransaction
                    $accountTransaction->delete(); // Or any other operation
                    //Update  account
                    $this->accountTransactionService->calculateAccounts($accountId);
                }
            }

            $paymentTransaction->delete();

            /**
             * Update Sale Model
             * Total Paid Amount
             * */
            $sale = Sale::find($saleId);
            if(!$this->paymentTransactionService->updateTotalPaidAmountInModel($sale)){
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            DB::commit();
            return response()->json([
                'status'    => true,
                'message' => __('app.record_deleted_successfully'),
                'data'  => $this->getSaleBillPaymentHistoryData($sale->id),
            ]);

        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }
    }

    public function storeSaleBillPayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $invoiceId          = $request->input('invoice_id');
            $transactionDate    = $request->input('transaction_date');
            $receiptNo          = $request->input('receipt_no');
            $paymentTypeId      = $request->input('payment_type_id');
            $payment            = $request->input('payment');
            $paymentNote        = $request->input('payment_note');

            $sale = Sale::find($invoiceId);

            if (!$sale) {
                throw new \Exception('Invoice not found');
            }

             // Validation rules
            $rules = [
                'transaction_date'  => 'required|date_format:'.implode(',', $this->getDateFormats()),
                'receipt_no'        => 'nullable|string|max:255',
                'payment_type_id'   => 'required|integer',
                'payment'           => 'required|numeric|gt:0',
            ];

            //validation message
            $messages = [
                'transaction_date.required' => 'Payment date is required.',
                'payment_type_id.required'  => 'Payment type is required.',
                'payment.required'          => 'Payment amount is required.',
                'payment.gt'                => 'Payment amount must be greater than zero.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            //Show validation message
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $paymentsArray = [
                'transaction_date'          => $transactionDate,
                'amount'                    => $payment,
                'payment_type_id'           => $paymentTypeId,
                'reference_no'              => $receiptNo,
                'note'                      => $paymentNote,
                'payment_from_unique_code'  => General::INVOICE_LIST->value,//Saving Sale-list page
            ];

            if(!$transaction = $this->paymentTransactionService->recordPayment($sale, $paymentsArray)){
                throw new \Exception(__('payment.failed_to_record_payment_transactions'));
            }

            /**
             * Update Sale Model
             * Total Paid Amunt
             * */
            if(!$this->paymentTransactionService->updateTotalPaidAmountInModel($sale)){
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            /**
             * Update Account Transaction entry
             * Call Services
             * @return boolean
             * */
            // $accountTransactionStatus = $this->accountTransactionService->saleAccountTransaction($sale);
            // if(!$accountTransactionStatus){
            //     throw new \Exception(__('payment.failed_to_update_account'));
            // }

            DB::commit();

            return response()->json([
                'status'    => true,
                'message' => __('app.record_saved_successfully'),
            ]);

        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }

    }

    /**
     * Datatabale
     * */
    public function datatableSaleBillPayment(Request $request){
        $data = PaymentTransaction::whereHasMorph(
            'transaction',
            [Sale::class],
            function (Builder $query, string $type) use($request) {
                //Class wise Apply filter
                if($type === Sale::class){
                     $query->when($request->party_id, function ($query) use ($request) {
                        $query->where('party_id', $request->party_id);
                    })
                     ->when($request->user_id, function ($query) use ($request) {
                        return $query->where('created_by', $request->user_id);
                    })
                     ->when($request->from_date, function ($query) use ($request) {
                        return $query->where('transaction_date', '>=', $this->toSystemDateFormat($request->from_date));
                    })
                    ->when($request->to_date, function ($query) use ($request) {
                        return $query->where('transaction_date', '<=', $this->toSystemDateFormat($request->to_date));
                    })
                    ->when($request->reference_no, function ($query) use ($request) {
                        // Split reference numbers by space, comma, or semicolon
                        $referenceNumbers = preg_split('/[\s,;]+/', trim($request->reference_no), -1, PREG_SPLIT_NO_EMPTY);

                        if (count($referenceNumbers) > 1) {
                            // Multiple reference numbers - use multiple LIKE conditions
                            return $query->where(function($subQuery) use ($referenceNumbers) {
                                foreach ($referenceNumbers as $refNo) {
                                    $subQuery->orWhere('reference_no', 'like', '%' . trim($refNo) . '%');
                                }
                            });
                        } else {
                            // Single reference number
                            return $query->where('reference_no', 'like', '%' . $request->reference_no . '%');
                        }
                    });

                    // Apply carrier filtering for delivery users
                    $query = $this->applyCarrierFilter($query, Sale::class);
                }

            }
        )->with('transaction.party');

        return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('created_at', function ($row) {
                        return $row->created_at->format(app('company')['date_format']);
                    })
                    ->addColumn('username', function ($row) {
                        return $row->user->username??'';
                    })
                    ->addColumn('sale_code', function ($row) {
                        return $row->transaction->sale_code??'';
                    })
                    ->addColumn('reference_no', function ($row) {
                        return $row->transaction->reference_no??'';
                    })
                    ->addColumn('party_name', function ($row) {
                        return $row->transaction->party->first_name." ".$row->transaction->party->last_name;
                    })
                    ->addColumn('payment', function ($row) {
                        return $this->formatWithPrecision($row->amount);
                    })
                    ->addColumn('action', function($row){
                            $id = $row->id;
                            $deleteUrl = route('sale.invoice.delete', ['id' => $id]);
                            $printUrl = route('sale.invoice.payment.print', ['id' => $id]);
                            $pdfUrl = route('sale.invoice.payment.pdf', ['id' => $id]);

                            $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $printUrl . '"></i><i class="bx bx-printer "></i> '.__('app.print').'</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $pdfUrl . '"></i><i class="bx bxs-file-pdf"></i> '.__('app.pdf').'</a>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
                                </li>
                            </ul>
                        </div>';
                            return $actionBtn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
    }

}
