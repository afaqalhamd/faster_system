<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Accounts\AccountTransaction;
use App\Services\AccountTransactionService;
use App\Services\PaymentTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة معالجة استرداد المدفوعات التلقائي
 * Automatic Payment Reversal Service
 *
 * هذه الخدمة تتعامل مع إنشاء معاملات الاسترداد العكسية
 * This service handles creating reversal transactions for payments
 */
class PaymentReversalService
{
    private $accountTransactionService;
    private $paymentTransactionService;

    public function __construct(
        AccountTransactionService $accountTransactionService,
        PaymentTransactionService $paymentTransactionService
    ) {
        $this->accountTransactionService = $accountTransactionService;
        $this->paymentTransactionService = $paymentTransactionService;
    }

    /**
     * معالجة استرداد المدفوعات لطلب البيع
     * Process payment reversal for a sale order
     *
     * @param mixed $model - نموذج الطلب (SaleOrder, Sale, etc.)
     * @param string $newStatus - الحالة الجديدة
     * @param string $previousStatus - الحالة السابقة
     * @param string $reason - سبب الاسترداد
     * @return array
     */
    public function processPaymentReversal($model, string $newStatus, string $previousStatus, string $reason = null): array
    {
        try {
            // DB::beginTransaction();

            // فحص الشروط المطلوبة للاسترداد
            if (!$this->shouldProcessReversal($previousStatus, $newStatus)) {
                return [
                    'success' => true,
                    'message' => 'No reversal needed for this status transition',
                    'payments_reversed' => 0,
                    'total_refunded' => 0
                ];
            }

            Log::info('Starting automatic payment reversal process', [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);

            // الحصول على المدفوعات النشطة (غير المستردة)
            $activePayments = $this->getActivePayments($model);

            if ($activePayments->isEmpty()) {
                Log::info('No active payments found for reversal', [
                    'model_type' => get_class($model),
                    'model_id' => $model->id
                ]);

                return [
                    'success' => true,
                    'message' => 'No active payments found for reversal',
                    'payments_reversed' => 0,
                    'total_refunded' => 0
                ];
            }

            $reversedCount = 0;
            $totalRefunded = 0;
            $reversalData = [];

            // معالجة كل دفعة على حدة
            foreach ($activePayments as $payment) {
                $reversalResult = $this->createReversalPayment($payment, $newStatus, $reason);

                if ($reversalResult['success']) {
                    $reversedCount++;
                    $totalRefunded += abs($reversalResult['amount']);
                    $reversalData[] = $reversalResult;

                    Log::info('Payment reversal created successfully', [
                        'original_payment_id' => $payment->id,
                        'reversal_payment_id' => $reversalResult['reversal_payment_id'],
                        'amount' => $reversalResult['amount']
                    ]);
                } else {
                    Log::error('Failed to create payment reversal', [
                        'original_payment_id' => $payment->id,
                        'error' => $reversalResult['message']
                    ]);

                    throw new Exception("Failed to reverse payment ID {$payment->id}: " . $reversalResult['message']);
                }
            }

            // تحديث إجمالي المبلغ المدفوع في النموذج
            $this->updateModelPaidAmount($model);

            DB::commit();

            Log::info('Payment reversal process completed successfully', [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'payments_reversed' => $reversedCount,
                'total_refunded' => $totalRefunded
            ]);

            return [
                'success' => true,
                'message' => "Successfully reversed {$reversedCount} payments totaling {$totalRefunded}",
                'payments_reversed' => $reversedCount,
                'total_refunded' => $totalRefunded,
                'reversal_data' => $reversalData
            ];

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Payment reversal process failed', [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Payment reversal failed: ' . $e->getMessage(),
                'payments_reversed' => 0,
                'total_refunded' => 0
            ];
        }
    }

    /**
     * تحقق من ضرورة معالجة الاسترداد
     * Check if reversal should be processed
     */
    private function shouldProcessReversal(string $previousStatus, string $newStatus): bool
    {
        $triggerConditions = [
            'previous_status' => 'POD',
            'new_statuses' => ['Cancelled', 'Returned']
        ];

        return $previousStatus === $triggerConditions['previous_status'] &&
               in_array($newStatus, $triggerConditions['new_statuses']);
    }

    /**
     * احصل على المدفوعات النشطة للنموذج
     * Get active payments for the model
     */
    private function getActivePayments($model)
    {
        return $model->paymentTransaction()
            ->where('is_reversal', false)
            ->whereDoesntHave('reversalPayments')
            ->where('amount', '>', 0)
            ->with(['paymentType', 'accountTransaction'])
            ->get();
    }

    /**
     * إنشاء معاملة استرداد لدفعة واحدة
     * Create reversal payment for a single payment
     */
    private function createReversalPayment(PaymentTransaction $originalPayment, string $newStatus, string $reason = null): array
    {
        try {
            // إنشاء سبب الاسترداد
            $reversalReason = $reason ?: "Automatic refund due to {$newStatus} after delivery";

            // إنشاء معاملة الاسترداد
            $reversalPayment = PaymentTransaction::create([
                'transaction_type' => $originalPayment->transaction_type,
                'transaction_id' => $originalPayment->transaction_id,
                'transaction_date' => now(),
                'amount' => -abs($originalPayment->amount), // قيمة سالبة للاسترداد
                'payment_type_id' => $originalPayment->payment_type_id,
                'note' => $reversalReason,
                'reference_no' => 'REV-' . $originalPayment->id . '-' . time(),
                'payment_from_unique_code' => $originalPayment->payment_from_unique_code,
                // حقول الاسترداد
                'is_reversal' => true,
                'original_payment_id' => $originalPayment->id,
                'reversal_reason' => $reversalReason,
                'reversed_at' => now(),
                'reversed_by' => auth()->id() ?? 1,
            ]);

            // معالجة المعاملات المحاسبية العكسية
            $accountResult = $this->processReversalAccountTransactions($originalPayment, $reversalPayment);

            if (!$accountResult['success']) {
                throw new Exception($accountResult['message']);
            }

            return [
                'success' => true,
                'reversal_payment_id' => $reversalPayment->id,
                'amount' => $reversalPayment->amount,
                'message' => 'Reversal payment created successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة المعاملات المحاسبية العكسية
     * Process reversal account transactions
     */
    private function processReversalAccountTransactions(PaymentTransaction $originalPayment, PaymentTransaction $reversalPayment): array
    {
        try {
            $originalAccountTransactions = $originalPayment->accountTransaction;

            if ($originalAccountTransactions->isEmpty()) {
                return ['success' => true, 'message' => 'No account transactions to reverse'];
            }

            foreach ($originalAccountTransactions as $accountTransaction) {
                // إنشاء معاملة محاسبية عكسية
                $reversalAccountData = [
                    'transaction_date' => now(),
                    'account_id' => $accountTransaction->account_id,
                    // عكس القيم: إذا كان Credit يصبح Debit والعكس
                    'debit_amount' => $accountTransaction->credit_amount ?? 0,
                    'credit_amount' => $accountTransaction->debit_amount ?? 0,
                ];

                $reversalAccountTransaction = $reversalPayment->accountTransaction()->create($reversalAccountData);

                if (!$reversalAccountTransaction) {
                    throw new Exception("Failed to create reversal account transaction for account ID {$accountTransaction->account_id}");
                }

                // إعادة حساب رصيد الحساب
                $this->accountTransactionService->calculateAccounts($accountTransaction->account_id);

                Log::debug('Reversal account transaction created', [
                    'original_account_transaction_id' => $accountTransaction->id,
                    'reversal_account_transaction_id' => $reversalAccountTransaction->id,
                    'account_id' => $accountTransaction->account_id
                ]);
            }

            return ['success' => true, 'message' => 'Account transactions reversed successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * تحديث إجمالي المبلغ المدفوع في النموذج
     * Update total paid amount in the model
     */
    private function updateModelPaidAmount($model): void
    {
        try {
            $this->paymentTransactionService->updateTotalPaidAmountInModel($model);

            Log::info('Model paid amount updated after reversal', [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'new_paid_amount' => $model->fresh()->paid_amount ?? 0
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update model paid amount after reversal', [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage()
            ]);
            // لا نقوم برمي الاستثناء هنا حتى لا نعطل العملية الرئيسية
        }
    }

    /**
     * احصل على تقرير تفصيلي للاستردادات
     * Get detailed reversal report
     */
    public function getReversalReport($model): array
    {
        $allPayments = $model->paymentTransaction()->with(['originalPayment', 'reversalPayments'])->get();

        $originalPayments = $allPayments->where('is_reversal', false);
        $reversalPayments = $allPayments->where('is_reversal', true);

        return [
            'total_original_payments' => $originalPayments->count(),
            'total_original_amount' => $originalPayments->sum('amount'),
            'total_reversal_payments' => $reversalPayments->count(),
            'total_reversal_amount' => abs($reversalPayments->sum('amount')),
            'net_amount' => $originalPayments->sum('amount') + $reversalPayments->sum('amount'),
            'original_payments' => $originalPayments->toArray(),
            'reversal_payments' => $reversalPayments->toArray()
        ];
    }
}
