# Sales API Documentation - SaleControllerApi

## نظرة عامة
يوفر SaleControllerApi مجموعة شاملة من APIs لإدارة المبيعات في تطبيق Flutter، بما في ذلك العمليات الأساسية CRUD وإرسال الإيميل والرسائل النصية وتحويل المبيعات إلى إرجاع.

## Base URL
```
{your-domain}/api
```

## Authentication
جميع المسارات تتطلب مصادقة باستخدام Sanctum token:
```
Authorization: Bearer {your-token}
```

---

## 1. المبيعات الأساسية (Basic Sales Operations)

### GET /sales-api
**الوصف:** الحصول على قائمة المبيعات مع فلترة وصفحات

**المعاملات (Query Parameters):**
- `party_id` (اختياري): معرف العميل
- `from_date` (اختياري): التاريخ من (YYYY-MM-DD)
- `to_date` (اختياري): التاريخ إلى (YYYY-MM-DD)
- `reference_no` (اختياري): الرقم المرجعي
- `per_page` (اختياري): عدد السجلات لكل صفحة (افتراضي: 15)

**مثال على الطلب:**
```http
GET /api/sales-api?party_id=1&from_date=2024-01-01&to_date=2024-12-31&per_page=20
```

**الاستجابة:**
```json
{
    "status": true,
    "message": "Sales retrieved successfully",
    "data": [
        {
            "id": 1,
            "sale_code": "SAL-0001",
            "sale_date": "01/01/2024",
            "reference_no": "REF001",
            "party": {
                "id": 1,
                "name": "اسم العميل",
                "phone": "123456789",
                "email": "customer@example.com"
            },
            "grand_total": "1000.00",
            "paid_amount": "500.00",
            "balance": "500.00",
            "note": "ملاحظة",
            "created_by": "admin",
            "created_at": "01/01/2024"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

### GET /sales-api/{id}
**الوصف:** الحصول على تفاصيل مبيعة محددة

**مثال على الطلب:**
```http
GET /api/sales-api/1
```

**الاستجابة:**
```json
{
    "status": true,
    "message": "Sale retrieved successfully",
    "data": {
        "id": 1,
        "sale_code": "SAL-0001",
        "sale_date": "01/01/2024",
        "reference_no": "REF001",
        "party": {
            "id": 1,
            "name": "اسم العميل",
            "phone": "123456789",
            "email": "customer@example.com"
        },
        "items": [
            {
                "id": 1,
                "item_id": 1,
                "item_name": "منتج 1",
                "item_code": "ITEM001",
                "warehouse_id": 1,
                "quantity": "5.00",
                "unit_id": 1,
                "unit_price": "100.00",
                "discount": "10.00",
                "discount_type": "fixed",
                "discount_amount": "10.00",
                "tax_id": 1,
                "tax_type": "exclusive",
                "tax_amount": "50.00",
                "total": "540.00",
                "description": "وصف الصنف",
                "batch": {
                    "batch_no": "BATCH001",
                    "mfg_date": "01/01/2024",
                    "exp_date": "31/12/2024",
                    "model_no": "MODEL001",
                    "color": "أحمر",
                    "size": "كبير"
                },
                "serials": [
                    {"serial_code": "SN001"},
                    {"serial_code": "SN002"}
                ]
            }
        ],
        "payments": [
            {
                "payment_type_id": 1,
                "amount": "500.00",
                "note": "دفعة نقدية"
            }
        ],
        "grand_total": "1000.00",
        "paid_amount": "500.00",
        "balance": "500.00",
        "round_off": "0.00",
        "note": "ملاحظة",
        "created_by": "admin",
        "created_at": "01/01/2024"
    }
}
```

---

## 2. إنشاء مبيعة جديدة (Create Sale)

### POST /sales-api
**الوصف:** إنشاء مبيعة جديدة

**البيانات المطلوبة:**
```json
{
    "party_id": 1,
    "sale_date": "2024-01-01",
    "reference_no": "REF001",
    "note": "ملاحظة",
    "round_off": 0,
    "grand_total": 1000.00,
    "state_id": 1,
    "currency_id": 1,
    "exchange_rate": 1,
    "items": [
        {
            "item_id": 1,
            "warehouse_id": 1,
            "quantity": 5,
            "unit_id": 1,
            "sale_price": 100.00,
            "discount": 10,
            "discount_type": "fixed",
            "discount_amount": 10,
            "tax_id": 1,
            "tax_type": "exclusive",
            "tax_amount": 50,
            "total": 540,
            "description": "وصف الصنف",
            "mrp": 110,
            "batch": {
                "batch_no": "BATCH001",
                "mfg_date": "2024-01-01",
                "exp_date": "2024-12-31",
                "model_no": "MODEL001",
                "color": "أحمر",
                "size": "كبير",
                "mrp": 110
            },
            "serials": ["SN001", "SN002"]
        }
    ],
    "payments": [
        {
            "payment_type_id": 1,
            "amount": 500,
            "note": "دفعة نقدية"
        }
    ]
}
```

**الاستجابة:**
```json
{
    "status": true,
    "message": "Record saved successfully",
    "data": {
        "id": 1,
        "sale_code": "SAL-0001"
    }
}
```

---

## 3. تحديث مبيعة (Update Sale)

### PUT /sales-api/{id}
**الوصف:** تحديث مبيعة موجودة

**البيانات المطلوبة:** نفس بيانات إنشاء مبيعة جديدة

**الاستجابة:**
```json
{
    "status": true,
    "message": "Record updated successfully",
    "data": {
        "id": 1,
        "sale_code": "SAL-0001"
    }
}
```

---

## 4. حذف مبيعة (Delete Sale)

### DELETE /sales-api/{id}
**الوصف:** حذف مبيعة

**الاستجابة:**
```json
{
    "status": true,
    "message": "Record deleted successfully"
}
```

---

## 5. تحويل المبيعة إلى إرجاع (Convert Sale to Return)

### POST /sales-api/{id}/convert-to-return
**الوصف:** تحويل مبيعة إلى إرجاع

**البيانات المطلوبة:**
```json
{
    "return_date": "2024-01-15",
    "note": "سبب الإرجاع",
    "items": [
        {
            "item_id": 1,
            "quantity": 2,
            "reason": "منتج معيب"
        }
    ]
}
```

**الاستجابة:**
```json
{
    "status": true,
    "message": "Sale converted to return successfully",
    "data": {
        "return_id": 1,
        "return_code": "RET-0001",
        "original_sale_code": "SAL-0001"
    }
}
```

---

## 6. إرسال الإيميل (Email Operations)

### POST /sales-api/{id}/send-email
**الوصف:** إرسال إيميل للعميل بتفاصيل المبيعة

**الاستجابة:**
```json
{
    "status": true,
    "message": "Email sent successfully",
    "data": {
        "email": "customer@example.com",
        "subject": "عنوان الإيميل",
        "content": "محتوى الإيميل"
    }
}
```

### GET /sales-api/{id}/email-content
**الوصف:** معاينة محتوى الإيميل قبل الإرسال

**الاستجابة:**
```json
{
    "status": true,
    "message": "Email content retrieved successfully",
    "data": {
        "email": "customer@example.com",
        "subject": "عنوان الإيميل",
        "content": "محتوى الإيميل"
    }
}
```

---

## 7. إرسال الرسائل النصية (SMS Operations)

### POST /sales-api/{id}/send-sms
**الوصف:** إرسال رسالة نصية للعميل بتفاصيل المبيعة

**الاستجابة:**
```json
{
    "status": true,
    "message": "SMS sent successfully",
    "data": {
        "mobile": "123456789",
        "content": "محتوى الرسالة النصية"
    }
}
```

### GET /sales-api/{id}/sms-content
**الوصف:** معاينة محتوى الرسالة النصية قبل الإرسال

**الاستجابة:**
```json
{
    "status": true,
    "message": "SMS content retrieved successfully",
    "data": {
        "mobile": "123456789",
        "content": "محتوى الرسالة النصية"
    }
}
```

---

## 8. الحصول على بيانات الأصناف المباعة (Get Sold Items Data)

### GET /sales-api/sold-items/{partyId}
**الوصف:** الحصول على جميع الأصناف المباعة لعميل معين

### GET /sales-api/sold-items/{partyId}/{itemId}
**الوصف:** الحصول على صنف محدد مباع لعميل معين

**الاستجابة:**
```json
{
    "status": true,
    "message": "Sold items data retrieved successfully",
    "data": {
        "party_name": "اسم العميل",
        "sold_items": [
            {
                "id": 1,
                "sale_id": 1,
                "sale_code": "SAL-0001",
                "sale_date": "01/01/2024",
                "warehouse_id": 1,
                "warehouse_name": "المستودع الرئيسي",
                "item_id": 1,
                "item_name": "منتج 1",
                "item_code": "ITEM001",
                "brand_name": "ماركة المنتج",
                "unit_price": "100.00",
                "quantity": "5.00",
                "discount_amount": "10.00",
                "tax_id": 1,
                "tax_name": "ضريبة القيمة المضافة",
                "tax_amount": "50.00",
                "total": "540.00"
            }
        ]
    }
}
```

---

## رموز الأخطاء (Error Codes)

### 400 - Bad Request
```json
{
    "status": false,
    "message": "Party email address is not available"
}
```

### 403 - Forbidden
```json
{
    "status": false,
    "message": "You do not have permission to view this sale"
}
```

### 404 - Not Found
```json
{
    "status": false,
    "message": "Sale not found"
}
```

### 409 - Conflict
```json
{
    "status": false,
    "message": "Cannot delete sale with associated returns"
}
```

### 422 - Validation Error
```json
{
    "status": false,
    "message": "Validation error",
    "errors": {
        "party_id": ["The party id field is required."],
        "items": ["The items field is required."]
    }
}
```

### 500 - Internal Server Error
```json
{
    "status": false,
    "message": "Internal server error message"
}
```

---

## ملاحظات مهمة

1. **التواريخ:** يجب أن تكون التواريخ بصيغة `YYYY-MM-DD`
2. **الأرقام:** يتم إرجاع الأرقام كنصوص للدقة في العمليات المالية
3. **الصلاحيات:** يتم فحص الصلاحيات للتأكد من قدرة المستخدم على عرض/تعديل المبيعات
4. **المعاملات:** جميع العمليات تتم داخل معاملات قاعدة البيانات لضمان تكامل البيانات
5. **المخزون:** يتم تحديث المخزون تلقائياً عند إنشاء أو تعديل أو حذف المبيعات

---

## أمثلة لتطبيق Flutter

### مثال على استدعاء API باستخدام Dart
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class SalesApiService {
  final String baseUrl = 'https://your-domain.com/api';
  final String token = 'your-bearer-token';
  
  Future<Map<String, dynamic>> getSales({
    int? partyId,
    String? fromDate,
    String? toDate,
    int perPage = 15
  }) async {
    final uri = Uri.parse('$baseUrl/sales-api').replace(queryParameters: {
      if (partyId != null) 'party_id': partyId.toString(),
      if (fromDate != null) 'from_date': fromDate,
      if (toDate != null) 'to_date': toDate,
      'per_page': perPage.toString(),
    });
    
    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> createSale(Map<String, dynamic> saleData) async {
    final response = await http.post(
      Uri.parse('$baseUrl/sales-api'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: json.encode(saleData),
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> sendEmail(int saleId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/sales-api/$saleId/send-email'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    return json.decode(response.body);
  }
}
```

هذا الملف يوفر دليلاً شاملاً لاستخدام جميع APIs المتاحة في SaleControllerApi لتطبيق Flutter.

