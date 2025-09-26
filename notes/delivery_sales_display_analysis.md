# تحليل شامل: عرض المبيعات للمستخدمين المُوصِلين (Delivery Users)

## الوضع الحالي (Current State)

### 📊 البيانات المتاحة:
1. **Sale Orders** (طلبات البيع) - `SaleOrderController`
   - حالة "Delivery" متاحة
   - الفلترة الحالية: يرى المُوصِل طلبات بحالة "Delivery" فقط

2. **Sales/Invoices** (فواتير البيع) - `SaleController`
   - لا يوجد فلترة خاصة للمُوصِلين حالياً
   - يعرض جميع الفواتير حسب الصلاحيات العادية

3. **العلاقة بين الجداول:**
   - Sale Order → Sale (عند التحويل)
   - Sale Order.sale_id يشير إلى Sale.id

---

## 🎯 خيارات العرض للمستخدمين المُوصِلين

### الخيار الأول: عرض طلبات البيع فقط (Sale Orders Only)
**الوضع الحالي - مُطبق بالفعل**

```php
// في SaleOrderController
private function applyDeliveryUserFilter($query)
{
    if ($this->isDeliveryUser()) {
        $query->where('order_status', 'Delivery');
    }
    return $query;
}
```

**المزايا:**
✅ مُطبق ومُختبر  
✅ يركز على المهام المطلوبة  
✅ لا يعرض معلومات غير ضرورية  

**العيوب:**
❌ لا يرى الفواتير المُحولة  
❌ معلومات محدودة عن حالة الدفع  

---

### الخيار الثاني: عرض الفواتير المرتبطة بطلبات التوصيل
**المقترح الأول**

```php
// في SaleController - إضافة فلترة للمُوصِلين
private function isDeliveryUser(): bool
{
    $user = auth()->user();
    return $user && $user->role && strtolower($user->role->name) === 'delivery';
}

private function applyDeliveryUserFilterForSales($query)
{
    if ($this->isDeliveryUser()) {
        // عرض الفواتير المرتبطة بطلبات توصيل فقط
        $query->whereHas('saleOrder', function($q) {
            $q->where('order_status', 'Delivery');
        });
    }
    return $query;
}

// في datatableList
->when($this->isDeliveryUser(), function ($query) {
    return $this->applyDeliveryUserFilterForSales($query);
})
```

**المزايا:**
✅ يرى الفواتير الكاملة  
✅ معلومات دفع واضحة  
✅ إمكانية طباعة الفواتير  

**العيوب:**
❌ قد يرى معلومات أكثر من اللازم  
❌ يحتاج تعديل في SaleController  

---

### الخيار الثالث: نظام مُوحد (Unified Dashboard)
**المقترح الثاني - الأكثر شمولية**

#### إنشاء DeliveryController منفصل:

```php
<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeliveryDashboardController extends Controller
{
    /**
     * عرض لوحة تحكم المُوصِل
     */
    public function index()
    {
        // التحقق من صلاحية المُوصِل
        if (!$this->isDeliveryUser()) {
            abort(403);
        }
        
        return view('delivery.dashboard');
    }

    /**
     * عرض طلبات التوصيل مع الفواتير المرتبطة
     */
    public function datatableList(Request $request)
    {
        if (!$this->isDeliveryUser()) {
            abort(403);
        }

        // جلب طلبات التوصيل مع الفواتير المرتبطة
        $data = SaleOrder::with(['user', 'party', 'sale.party'])
            ->where('order_status', 'Delivery')
            ->when($request->party_id, function ($query) use ($request) {
                return $query->where('party_id', $request->party_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('order_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('order_date', '<=', $this->toSystemDateFormat($request->to_date));
            });

        return DataTables::of($data)
            ->addColumn('order_info', function ($row) {
                return [
                    'order_code' => $row->order_code,
                    'order_date' => $row->formatted_order_date,
                    'party_name' => $row->party->first_name . " " . $row->party->last_name,
                    'grand_total' => $this->formatWithPrecision($row->grand_total),
                ];
            })
            ->addColumn('sale_info', function ($row) {
                if ($row->sale) {
                    return [
                        'sale_code' => $row->sale->sale_code,
                        'sale_date' => $row->sale->formatted_sale_date,
                        'paid_amount' => $this->formatWithPrecision($row->sale->paid_amount),
                        'balance' => $this->formatWithPrecision($row->sale->grand_total - $row->sale->paid_amount),
                        'status' => $row->sale->grand_total == $row->sale->paid_amount ? 'Paid' : 'Pending',
                    ];
                }
                return [
                    'sale_code' => 'Not Converted',
                    'sale_date' => '',
                    'paid_amount' => 0,
                    'balance' => 0,
                    'status' => 'Order Only',
                ];
            })
            ->addColumn('delivery_actions', function ($row) {
                $actions = [];
                
                // عرض تفاصيل الطلب
                $actions[] = [
                    'type' => 'view_order',
                    'url' => route('sale.order.details', ['id' => $row->id]),
                    'text' => 'عرض الطلب',
                ];
                
                // عرض الفاتورة إذا متاحة
                if ($row->sale) {
                    $actions[] = [
                        'type' => 'view_invoice',
                        'url' => route('sale.invoice.details', ['id' => $row->sale->id]),
                        'text' => 'عرض الفاتورة',
                    ];
                    
                    $actions[] = [
                        'type' => 'print_invoice',
                        'url' => route('sale.invoice.print', ['id' => $row->sale->id, 'invoiceFormat' => 'format-1']),
                        'text' => 'طباعة الفاتورة',
                    ];
                }
                
                // إجراءات التوصيل
                $actions[] = [
                    'type' => 'mark_delivered',
                    'url' => '#',
                    'text' => 'تم التوصيل',
                    'onclick' => "markAsDelivered({$row->id})",
                ];
                
                return $actions;
            })
            ->rawColumns(['delivery_actions'])
            ->make(true);
    }

    private function isDeliveryUser(): bool
    {
        $user = auth()->user();
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }
}
```

---

### الخيار الرابع: تحسين العرض الحالي (Enhanced Current View)
**المقترح الثالث - تحسين ما هو موجود**

```php
// تحسين SaleOrderController للمُوصِلين
->addColumn('delivery_details', function ($row) {
    if (!$this->isDeliveryUser()) {
        return null;
    }
    
    $deliveryInfo = [
        'customer_phone' => $row->party->mobile ?? '',
        'customer_address' => $row->party->address ?? '',
        'delivery_notes' => $row->notes ?? '',
        'payment_status' => $row->sale ? 
            ($row->sale->grand_total == $row->sale->paid_amount ? 'مدفوع' : 'غير مدفوع') : 
            'لم يتم التحويل',
    ];
    
    return $deliveryInfo;
})
->addColumn('delivery_actions', function ($row) {
    if (!$this->isDeliveryUser()) {
        return $this->getRegularActions($row); // الإجراءات العادية
    }
    
    // إجراءات خاصة بالمُوصِلين
    $deliveryActions = '<div class="btn-group">';
    
    // عرض الطلب
    $deliveryActions .= '<a href="' . route('sale.order.details', ['id' => $row->id]) . '" 
                            class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-show-alt"></i> عرض
                         </a>';
    
    // طباعة الفاتورة إذا متاحة
    if ($row->sale) {
        $deliveryActions .= '<a href="' . route('sale.invoice.print', ['id' => $row->sale->id, 'invoiceFormat' => 'format-1']) . '" 
                                target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-printer"></i> طباعة
                             </a>';
    }
    
    // تحديث حالة التوصيل
    $deliveryActions .= '<button class="btn btn-sm btn-success mark-delivered" 
                            data-id="' . $row->id . '">
                            <i class="bx bx-check"></i> تم التوصيل
                         </button>';
    
    $deliveryActions .= '</div>';
    
    return $deliveryActions;
})
```

---

## 🚀 التوصيات

### للبداية السريعة (Quick Implementation):
**استخدم الخيار الرابع** - تحسين العرض الحالي
- أقل تعقيداً
- يبني على ما هو موجود
- يمكن تطبيقه بسرعة

### للمدى الطويل (Long-term Solution):
**استخدم الخيار الثالث** - النظام المُوحد
- أكثر مرونة وقابلية للتوسع
- تجربة مستخدم محسنة
- إمكانية إضافة مزايا خاصة بالتوصيل

### الهيكل المقترح للمسارات (Routes):

```php
// في routes/web.php
Route::group(['prefix' => 'delivery', 'middleware' => ['auth', 'role:delivery']], function () {
    Route::get('/', [DeliveryDashboardController::class, 'index'])->name('delivery.dashboard');
    Route::get('/datatable-list', [DeliveryDashboardController::class, 'datatableList'])->name('delivery.datatable.list');
    Route::post('/mark-delivered/{id}', [DeliveryDashboardController::class, 'markAsDelivered'])->name('delivery.mark.delivered');
});
```

---

## 📋 خطة التنفيذ المقترحة

### المرحلة الأولى (فورية):
1. ✅ **مُكتملة**: فلترة طلبات البيع للمُوصِلين
2. **إضافة معلومات التوصيل** في العرض الحالي
3. **تحسين الإجراءات المتاحة** للمُوصِلين

### المرحلة الثانية (قصيرة المدى):
1. **إنشاء DeliveryController** منفصل
2. **تصميم واجهة خاصة** بالمُوصِلين
3. **إضافة مزايا تتبع التوصيل**

### المرحلة الثالثة (متوسطة المدى):
1. **نظام إشعارات التوصيل**
2. **تتبع موقع المُوصِل** (GPS)
3. **تقارير أداء التوصيل**

---

## 🎯 الخلاصة والتوصية النهائية

**للتطبيق الفوري:** ابدأ بـ **الخيار الرابع** (تحسين العرض الحالي)
- سريع التنفيذ
- يحسن تجربة المُوصِل
- لا يتطلب تغييرات جذرية

**للمستقبل:** خطط لـ **الخيار الثالث** (النظام المُوحد)
- نظام شامل ومرن
- إمكانيات توسع كبيرة
- تجربة مستخدم متميزة

هل تريد أن نبدأ بتطبيق أحد هذه الخيارات؟
