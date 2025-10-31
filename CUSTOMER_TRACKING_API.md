# Customer Tracking Search API Documentation

## نظرة عامة
تم إنشاء API جديد للعملاء للبحث عن الشحنات باستخدام رقم التتبع. يدعم النظام البحث المحمي (للعملاء المسجلين) والبحث العام.

## الراوتس الجديدة

### 1. البحث المحمي (للعملاء المسجلين)
```
POST /api/customer/tracking/search
POST /api/customer/tracking/validate
```

### 2. البحث العام (بدون تسجيل دخول)
```
POST /api/customer/tracking/search-public
POST /api/customer/tracking/validate-public
```

## تفاصيل الـ APIs

### 1. البحث عن الشحنة
**Endpoint:** `POST /api/customer/tracking/search`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token} // للراوتس المحمية فقط
```

**Request Body:**
```json
{
    "tracking_number": "RR123456789US"
}
```

**Response - نجح البحث:**
```json
{
    "status": true,
    "message": "Shipment tracking found successfully",
    "data": {
        "tracking_info": {
            "id": 1,
            "tracking_number": "RR123456789US",
            "waybill_number": "WB123456789",
            "status": "in_transit",
            "estimated_delivery_date": "2024-01-15",
            "actual_delivery_date": null,
            "tracking_url": "https://tracking.example.com/RR123456789US",
            "notes": "Package is on the way",
            "created_at": "2024-01-10T10:00:00Z",
            "updated_at": "2024-01-12T14:30:00Z"
        },
        "order_info": {
            "id": 123,
            "order_code": "SO-2024-001",
            "status": "shipped",
            "total_amount": "250.00",
            "order_date": "2024-01-10T10:00:00Z"
        },
        "customer_info": {
            "name": "أحمد محمد",
            "email": "ahmed@example.com",
            "phone": "+966501234567"
        },
        "carrier_info": {
            "name": "DHL Express",
            "phone": "+966112345678",
            "email": "support@dhl.com"
        },
        "tracking_events": [
            {
                "id": 1,
                "event_date": "2024-01-12T16:45:00Z",
                "location": "In Transit",
                "status": "in_transit",
                "description": "Package is on the way to destination",
                "signature": null,
                "proof_image_url": null,
                "latitude": null,
                "longitude": null,
                "created_at": "2024-01-12T16:45:00Z"
            },
            {
                "id": 2,
                "event_date": "2024-01-11T09:15:00Z",
                "location": "Riyadh Sorting Facility",
                "status": "sorted",
                "description": "Package sorted and prepared for dispatch",
                "signature": null,
                "proof_image_url": null,
                "latitude": null,
                "longitude": null,
                "created_at": "2024-01-11T09:15:00Z"
            }
        ],
        "documents": [
            {
                "id": 1,
                "document_type": "waybill",
                "file_name": "waybill_123.pdf",
                "file_url": "https://example.com/documents/waybill_123.pdf",
                "notes": "Original waybill document",
                "uploaded_at": "2024-01-10T10:30:00Z"
            }
        ],
        "statistics": {
            "total_events": 2,
            "latest_event": {
                "date": "2024-01-12T16:45:00Z",
                "location": "In Transit",
                "description": "Package is on the way to destination"
            },
            "has_documents": true,
            "is_delivered": false
        }
    }
}
```

**Response - لم يتم العثور على الشحنة:**
```json
{
    "status": false,
    "message": "No shipment found with this tracking number",
    "error_code": "TRACKING_NOT_FOUND"
}
```

**Response - ليس لديك صلاحية (للراوتس المحمية):**
```json
{
    "status": false,
    "message": "You do not have access to this shipment",
    "error_code": "ACCESS_DENIED"
}
```

### 2. التحقق من صحة رقم التتبع
**Endpoint:** `POST /api/customer/tracking/validate`

**Request Body:**
```json
{
    "tracking_number": "RR123456789US"
}
```

**Response - رقم صحيح:**
```json
{
    "status": true,
    "valid": true,
    "message": "Tracking number format is valid",
    "pattern_matched": 1,
    "cleaned_number": "RR123456789US"
}
```

**Response - رقم غير صحيح:**
```json
{
    "status": true,
    "valid": false,
    "message": "Invalid tracking number format",
    "pattern_matched": null,
    "cleaned_number": "INVALID123"
}
```

## أنماط أرقام التتبع المدعومة

1. **International Format:** `RR123456789US` (حرفان + 9 أرقام + حرفان)
2. **Numeric Only:** `123456789012` (12-22 رقم)
3. **Alphanumeric:** `ABC1234567890` (10-30 حرف ورقم)
4. **UPS Format:** `1Z12345E0205271688` (1Z + 16 حرف/رقم)
5. **Spaced Format:** `1234 5678 9012 3456` (16 رقم مع مسافات اختيارية)
6. **Carrier Prefix:** `DHL12345678` (3 أحرف + 8-12 رقم)
7. **Tracking Number:** `TN123456789` (TN + 8-15 رقم)

## رموز الأخطاء

- `TRACKING_NOT_FOUND`: لم يتم العثور على رقم التتبع
- `ACCESS_DENIED`: ليس لديك صلاحية للوصول لهذه الشحنة
- `SEARCH_ERROR`: خطأ في عملية البحث

## حالات الشحنة المدعومة

- `pending`: قيد الانتظار
- `picked_up`: تم الاستلام
- `in_transit`: قيد التوصيل
- `out_for_delivery`: خارج للتوصيل
- `delivered`: تم التسليم
- `failed`: فشل التسليم
- `returned`: مرتجع
- `cancelled`: ملغى

## أمثلة الاستخدام

### JavaScript/Fetch
```javascript
// البحث عن شحنة
const searchTracking = async (trackingNumber) => {
    try {
        const response = await fetch('/api/customer/tracking/search-public', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tracking_number: trackingNumber
            })
        });
        
        const data = await response.json();
        
        if (data.status) {
            console.log('Tracking found:', data.data);
            return data.data;
        } else {
            console.error('Tracking not found:', data.message);
            return null;
        }
    } catch (error) {
        console.error('Search error:', error);
        return null;
    }
};

// التحقق من صحة رقم التتبع
const validateTracking = async (trackingNumber) => {
    try {
        const response = await fetch('/api/customer/tracking/validate-public', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tracking_number: trackingNumber
            })
        });
        
        const data = await response.json();
        return data.valid;
    } catch (error) {
        console.error('Validation error:', error);
        return false;
    }
};
```

### Flutter/Dart
```dart
// البحث عن شحنة
Future<Map<String, dynamic>?> searchTracking(String trackingNumber) async {
  try {
    final response = await http.post(
      Uri.parse('${baseUrl}/api/customer/tracking/search-public'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'tracking_number': trackingNumber}),
    );
    
    final data = jsonDecode(response.body);
    
    if (data['status'] == true) {
      return data['data'];
    } else {
      print('Tracking not found: ${data['message']}');
      return null;
    }
  } catch (error) {
    print('Search error: $error');
    return null;
  }
}

// التحقق من صحة رقم التتبع
Future<bool> validateTrackingNumber(String trackingNumber) async {
  try {
    final response = await http.post(
      Uri.parse('${baseUrl}/api/customer/tracking/validate-public'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'tracking_number': trackingNumber}),
    );
    
    final data = jsonDecode(response.body);
    return data['valid'] ?? false;
  } catch (error) {
    print('Validation error: $error');
    return false;
  }
}
```

## الأمان والحماية

1. **Rate Limiting:** تم تطبيق حدود على عدد الطلبات
2. **Input Validation:** التحقق من صحة البيانات المدخلة
3. **Access Control:** التحكم في الوصول للشحنات (للراوتس المحمية)
4. **Error Handling:** معالجة شاملة للأخطاء
5. **Data Sanitization:** تنظيف البيانات قبل البحث

## ملاحظات مهمة

1. **الراوتس العامة** لا تتطلب تسجيل دخول ولكن لا تعرض معلومات العميل الحساسة
2. **الراوتس المحمية** تتطلب تسجيل دخول وتعرض معلومات كاملة للعميل المالك فقط
3. **البحث يدعم** رقم التتبع ورقم البوليصة
4. **التنظيف التلقائي** لأرقام التتبع (إزالة المسافات والرموز)
5. **دعم أنماط متعددة** من أرقام التتبع العالمية

تم تنفيذ النظام بنجاح وهو جاهز للاستخدام! 🚀
