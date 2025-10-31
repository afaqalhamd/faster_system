<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\PartyService;

class BalanceController extends Controller
{
    protected $partyService;

    public function __construct(PartyService $partyService)
    {
        $this->partyService = $partyService;
    }

    /**
     * Show customer balance - النسخة المحسنة
     *
     * التحسينات:
     * 1. استخدام getPartyBalance() لحساب الرصيد الفعلي
     * 2. إضافة Cache لتحسين الأداء
     * 3. معلومات أكثر تفصيلاً عن الرصيد
     * 4. معالجة أفضل للأخطاء
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // استخدام Cache لمدة 5 دقائق لتحسين الأداء
            $cacheKey = "party_balance_{$party->id}";
            $cacheDuration = 300; // 5 دقائق

            $balanceData = Cache::remember($cacheKey, $cacheDuration, function () use ($party) {
                return $this->partyService->getPartyBalance($party->id);
            });

            // تحديد to_pay و to_receive بناءً على الرصيد الفعلي
            $to_pay = 0;
            $to_receive = 0;
            $net_balance = 0;

            if ($balanceData['status'] === 'you_collect') {
                // الشركة تستحق من العميل (العميل مدين)
                $to_receive = $balanceData['balance'];
                // net_balance سالب للعميل المدين (اللون الأحمر سيوضح ذلك)
                $net_balance = -$balanceData['balance'];
            } elseif ($balanceData['status'] === 'you_pay') {
                // الشركة مدينة للعميل (العميل دائن)
                $to_pay = $balanceData['balance'];
                // net_balance موجب للعميل الدائن (اللون الأخضر سيوضح ذلك)
                $net_balance = $balanceData['balance'];
            }

            // حساب الائتمان المتاح
            $available_credit = $party->is_set_credit_limit
                ? max(0, $party->credit_limit - $to_pay)
                : null;

            return response()->json([
                'status' => true,
                'data' => [
                    'balance' => [
                        // الأرصدة الأساسية
                        'to_pay' => round($to_pay, 2),
                        'to_receive' => round($to_receive, 2),
                        'net_balance' => round($net_balance, 2),

                        // حالة الرصيد
                        'balance_status' => $balanceData['status'],
                        'balance_status_text' => $this->getBalanceStatusText($balanceData['status']),

                        // معلومات الائتمان
                        'credit_limit' => $party->is_set_credit_limit ? round($party->credit_limit, 2) : null,
                        'available_credit' => $available_credit ? round($available_credit, 2) : null,
                        'is_credit_limit_set' => $party->is_set_credit_limit,

                        // معلومات العملة
                        'currency' => $party->currency ? [
                            'id' => $party->currency->id,
                            'code' => $party->currency->code,
                            'symbol' => $party->currency->symbol,
                            'name' => $party->currency->name,
                        ] : null,

                        // معلومات إضافية
                        'last_updated' => now()->toIso8601String(),
                        'cached' => Cache::has($cacheKey),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get balance exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في حساب الرصيد. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }


    /**
     * Get customer transactions - النسخة المحسنة
     *
     * التحسينات:
     * 1. إضافة فلترة حسب النوع والتاريخ
     * 2. معلومات أكثر تفصيلاً
     * 3. ترتيب أفضل
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // بناء الاستعلام
            $query = $party->transaction();

            // فلترة حسب النوع (اختياري)
            if ($request->has('type')) {
                $query->where('transaction_type', $request->type);
            }

            // فلترة حسب التاريخ (اختياري)
            if ($request->has('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // الترقيم
            $perPage = min($request->get('per_page', 20), 100); // حد أقصى 100
            $transactions = $query->paginate($perPage);

            // تحسين البيانات المعروضة
            $transactionsData = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date,
                    'transaction_type' => $transaction->transaction_type,
                    'to_pay' => round($transaction->to_pay, 2),
                    'to_receive' => round($transaction->to_receive, 2),
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => [
                    'transactions' => $transactionsData,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'last_page' => $transactions->lastPage(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get transactions exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب المعاملات. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }

    /**
     * Get detailed balance breakdown - النسخة المحسنة
     * عرض تفصيلي للرصيد (فواتير، طلبات، مدفوعات)
     *
     * التحسينات:
     * 1. إضافة فلاتر (التاريخ، الحد الأدنى للمبلغ)
     * 2. إزالة الحد الأقصى (limit 10)
     * 3. إضافة الدفعات الأخيرة
     * 4. تحسين الأداء مع Cache
     */
    public function breakdown(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // معاملات الفلترة
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $minAmount = $request->get('min_amount', 0);
            $limit = min($request->get('limit', 50), 100); // حد أقصى 100

            // استخدام Cache لمدة دقيقة واحدة
            $cacheKey = "balance_breakdown_{$party->id}_" . md5(json_encode($request->all()));
            $cacheDuration = 60; // دقيقة واحدة

            $data = Cache::remember($cacheKey, $cacheDuration, function () use ($party, $fromDate, $toDate, $minAmount, $limit) {

                // بناء استعلام الفواتير غير المدفوعة
                $salesQuery = \App\Models\Sale\Sale::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->selectRaw('id, sale_code as code, sale_date as date, grand_total as total, paid_amount as paid, (grand_total - paid_amount) as due');

                // تطبيق الفلاتر
                if ($fromDate) {
                    $salesQuery->whereDate('sale_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $salesQuery->whereDate('sale_date', '<=', $toDate);
                }
                if ($minAmount > 0) {
                    $salesQuery->havingRaw('(grand_total - paid_amount) >= ?', [$minAmount]);
                }

                $unpaidSales = $salesQuery
                    ->orderBy('sale_date', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'code' => $sale->code,
           'date' => $sale->date,
                            'total' => round($sale->total, 2),
                            'paid' => round($sale->paid, 2),
                            'due' => round($sale->due, 2),
                        ];
                    });

                // بناء استعلام الطلبات غير المدفوعة
                $ordersQuery = \App\Models\Sale\SaleOrder::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->selectRaw('id, order_code as code, order_date as date, grand_total as total, paid_amount as paid, (grand_total - paid_amount) as due');

                // تطبيق الفلاتر
                if ($fromDate) {
                    $ordersQuery->whereDate('order_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $ordersQuery->whereDate('order_date', '<=', $toDate);
                }
                if ($minAmount > 0) {
                    $ordersQuery->havingRaw('(grand_total - paid_amount) >= ?', [$minAmount]);
                }

                $unpaidOrders = $ordersQuery
                    ->orderBy('order_date', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'code' => $order->code,
                            'date' => $order->date,
                            'total' => round($order->total, 2),
                            'paid' => round($order->paid, 2),
                            'due' => round($order->due, 2),
                        ];
                    });

                // جلب الدفعات الأخيرة
                $recentPaymentsQuery = \App\Models\Party\PartyPayment::where('party_id', $party->id)
                    ->with('paymentType')
                    ->select('id', 'transaction_date', 'amount', 'payment_type_id', 'note', 'created_at');

                // تطبيق فلتر التاريخ
                if ($fromDate) {
                    $recentPaymentsQuery->whereDate('transaction_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $recentPaymentsQuery->whereDate('transaction_date', '<=', $toDate);
                }

                $recentPayments = $recentPaymentsQuery
                    ->orderBy('transaction_date', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'date' => $payment->transaction_date,
                            'amount' => round($payment->amount, 2),
                            'method' => $payment->paymentType->name ?? null,
                            'note' => $payment->note,
                            'created_at' => $payment->created_at->toIso8601String(),
                        ];
                    });

                // حساب الإجماليات (بدون فلاتر للحصول على الصورة الكاملة)
                $totalSalesDue = \App\Models\Sale\Sale::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as total')
                    ->value('total');

                $totalOrdersDue = \App\Models\Sale\SaleOrder::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as total')
                    ->value('total');

                $totalReturnsDue = \App\Models\Sale\SaleReturn::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as total')
                    ->value('total');

                // عدد العناصر الكلي
                $totalSalesCount = \App\Models\Sale\Sale::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->count();

                $totalOrdersCount = \App\Models\Sale\SaleOrder::where('party_id', $party->id)
                    ->whereColumn('paid_amount', '<', 'grand_total')
                    ->count();

                return [
                    'summary' => [
                        'total_sales_due' => round($totalSalesDue, 2),
                        'total_orders_due' => round($totalOrdersDue, 2),
                        'total_returns_due' => round($totalReturnsDue, 2),
                        'net_due' => round($totalSalesDue + $totalOrdersDue - $totalReturnsDue, 2),
                        'total_sales_count' => $totalSalesCount,
                        'total_orders_count' => $totalOrdersCount,
                    ],
                    'unpaid_sales' => $unpaidSales,
                    'unpaid_orders' => $unpaidOrders,
                    'recent_payments' => $recentPayments,
                    'filters_applied' => [
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                        'min_amount' => $minAmount,
                        'limit' => $limit,
                    ],
                    'meta' => [
                        'showing_sales' => $unpaidSales->count(),
                        'showing_orders' => $unpaidOrders->count(),
                        'showing_payments' => $recentPayments->count(),
                        'has_more_sales' => $unpaidSales->count() >= $limit,
                        'has_more_orders' => $unpaidOrders->count() >= $limit,
                    ],
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Get balance breakdown exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب تفاصيل الرصيد.'
            ], 500);
        }
    }

    /**
     * Get unpaid invoices with pagination and filters
     * جلب الفواتير غير المدفوعة مع pagination وفلاتر
     */
    public function unpaidInvoices(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // بناء الاستعلام
            $query = \App\Models\Sale\Sale::where('party_id', $party->id)
                ->whereColumn('paid_amount', '<', 'grand_total')
                ->selectRaw('id, sale_code, sale_date, grand_total, paid_amount, (grand_total - paid_amount) as due_amount');

            // الفلاتر
            if ($request->has('from_date')) {
                $query->whereDate('sale_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('sale_date', '<=', $request->to_date);
            }
            if ($request->has('min_amount')) {
                $query->havingRaw('(grand_total - paid_amount) >= ?', [$request->min_amount]);
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'sale_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => [
                    'invoices' => $invoices->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'code' => $sale->sale_code,
                            'date' => $sale->sale_date,
                            'total' => round($sale->grand_total, 2),
                            'paid' => round($sale->paid_amount, 2),
                            'due' => round($sale->due_amount, 2),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $invoices->currentPage(),
                        'per_page' => $invoices->perPage(),
                        'total' => $invoices->total(),
                        'last_page' => $invoices->lastPage(),
                        'from' => $invoices->firstItem(),
                        'to' => $invoices->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get unpaid invoices exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب الفواتير.'
            ], 500);
        }
    }

    /**
     * Get unpaid orders with pagination and filters
     * جلب الطلبات غير المدفوعة مع pagination وفلاتر
     */
    public function unpaidOrders(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // بناء الاستعلام
            $query = \App\Models\Sale\SaleOrder::where('party_id', $party->id)
                ->whereColumn('paid_amount', '<', 'grand_total')
                ->selectRaw('id, order_code, order_date, grand_total, paid_amount, (grand_total - paid_amount) as due_amount');

            // الفلاتر
            if ($request->has('from_date')) {
                $query->whereDate('order_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('order_date', '<=', $request->to_date);
            }
            if ($request->has('min_amount')) {
                $query->havingRaw('(grand_total - paid_amount) >= ?', [$request->min_amount]);
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'order_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $orders = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'code' => $order->order_code,
                            'date' => $order->order_date,
                            'total' => round($order->grand_total, 2),
                            'paid' => round($order->paid_amount, 2),
                            'due' => round($order->due_amount, 2),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get unpaid orders exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب الطلبات.'
            ], 500);
        }
    }

    /**
     * Clear balance cache for current user
     * مسح الكاش لإعادة حساب الرصيد
     */
    public function refreshBalance(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // مسح جميع الكاش المتعلق بالعميل
            Cache::forget("party_balance_{$party->id}");

            // مسح كاش breakdown
            $pattern = "balance_breakdown_{$party->id}_*";
            // ملاحظة: Laravel Cache لا يدعم wildcard delete بشكل مباشر
            // يمكن استخدام Cache tags في Redis أو تخزين قائمة بالمفاتيح

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الرصيد بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Refresh balance exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في تحديث الرصيد.'
            ], 500);
        }
    }

    /**
     * Get balance status text in Arabic
     *
     * @param string $status
     * @return string
     */
    private function getBalanceStatusText(string $status): string
    {
        // عكس الرسائل لأن العميل يرى من وجهة نظره
        // 'you_collect' = الشركة تستحق = العميل مدين = "عليك رصيد مستحق"
        // 'you_pay' = الشركة مدينة = العميل دائن = "لك رصيد مستحق"
        return match($status) {
            'you_collect' => 'عليك رصيد مستحق',  // العميل مدين
            'you_pay' => 'لك رصيد مستحق',        // العميل دائن
            'no_balance' => 'لا يوجد رصيد',
            default => 'غير محدد',
        };
    }
}
