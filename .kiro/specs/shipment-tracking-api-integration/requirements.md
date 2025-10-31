# متطلبات ربط API البحث عن تتبع الشحنة

## مقدمة

هذا المشروع يهدف إلى ربط API البحث عن تتبع الشحنة الموجود في الخادم مع تطبيق Flutter للعملاء. التطبيق يحتوي حالياً على واجهة بحث احترافية (TrackingSearchDelegate) ولكنها غير مربوطة بـ API الخادم. نحتاج إلى إنشاء طبقة خدمات وإدارة الحالة لربط الواجهة مع الخادم.

## المصطلحات

- **TrackingSearchDelegate**: واجهة البحث الموجودة في التطبيق
- **ShipmentTrackingController**: API Controller في الخادم
- **TrackingService**: خدمة Flutter للتواصل مع API
- **TrackingBloc**: إدارة حالة البحث والتتبع
- **CustomerApp**: تطبيق Flutter للعملاء
- **API_Endpoint**: نقطة اتصال API في الخادم
- **TrackingNumber**: رقم تتبع الشحنة
- **ShipmentData**: بيانات الشحنة المسترجعة من API

## المتطلبات

### المتطلب 1: إعداد خدمة API للتتبع

**قصة المستخدم:** كمطور، أريد إنشاء خدمة API للتواصل مع خادم التتبع، حتى أتمكن من البحث عن الشحنات وجلب بياناتها.

#### معايير القبول

1. WHEN المطور ينشئ TrackingApiService، THE CustomerApp SHALL تحتوي على خدمة للتواصل مع API endpoints
2. WHEN TrackingApiService يستدعي searchByTrackingNumber، THE CustomerApp SHALL ترسل طلب HTTP إلى /api/shipment-tracking/search
3. WHEN TrackingApiService يستدعي validateTrackingNumber، THE CustomerApp SHALL ترسل طلب HTTP إلى /api/shipment-tracking/validate
4. WHEN API يرجع خطأ في الشبكة، THE TrackingApiService SHALL تتعامل مع الأخطاء وترجع رسائل مناسبة
5. WHERE المستخدم مسجل دخول، THE TrackingApiService SHALL تضمن authentication token في الطلبات

### المتطلب 2: إدارة حالة البحث والتتبع

**قصة المستخدم:** كمطور، أريد إدارة حالة البحث والتتبع باستخدام BLoC pattern، حتى أتمكن من فصل منطق العمل عن واجهة المستخدم.

#### معايير القبول

1. WHEN المطور ينشئ TrackingBloc، THE CustomerApp SHALL تحتوي على إدارة حالة للبحث والتتبع
2. WHEN TrackingBloc يستقبل SearchTrackingEvent، THE CustomerApp SHALL تبدأ عملية البحث وتظهر loading state
3. WHEN البحث ينجح، THE TrackingBloc SHALL ينتقل إلى TrackingFoundState مع بيانات الشحنة
4. WHEN البحث يفشل، THE TrackingBloc SHALL ينتقل إلى TrackingErrorState مع رسالة الخطأ
5. WHEN المستخدم يلغي البحث، THE TrackingBloc SHALL ينتقل إلى TrackingInitialState

### المتطلب 3: ربط واجهة البحث مع API

**قصة المستخدم:** كعميل، أريد أن تعمل واجهة البحث الموجودة مع خادم التتبع، حتى أتمكن من البحث عن شحناتي الفعلية.

#### معايير القبول

1. WHEN العميل يدخل رقم تتبع في TrackingSearchDelegate، THE CustomerApp SHALL تتحقق من صحة الرقم باستخدام API
2. WHEN العميل يضغط على البحث، THE CustomerApp SHALL ترسل طلب بحث إلى API وتظهر loading indicator
3. WHEN API ترجع بيانات الشحنة، THE CustomerApp SHALL تعرض النتائج في واجهة البحث
4. WHEN API ترجع خطأ "لم يتم العثور على الشحنة"، THE CustomerApp SHALL تعرض رسالة مناسبة للعميل
5. WHEN العميل يختار نتيجة البحث، THE CustomerApp SHALL تنتقل إلى صفحة تفاصيل التتبع

### المتطلب 4: عرض تفاصيل التتبع

**قصة المستخدم:** كعميل، أريد رؤية تفاصيل شحنتي الكاملة، حتى أتمكن من متابعة حالة الطلب والأحداث.

#### معايير القبول

1. WHEN العميل يفتح تفاصيل التتبع، THE CustomerApp SHALL تعرض معلومات الشحنة الأساسية
2. WHEN تفاصيل التتبع تحتوي على أحداث، THE CustomerApp SHALL تعرض timeline للأحداث مرتبة حسب التاريخ
3. WHEN تفاصيل التتبع تحتوي على مستندات، THE CustomerApp SHALL تعرض قائمة بالمستندات القابلة للتحميل
4. WHEN العميل يحدث الصفحة، THE CustomerApp SHALL تجلب أحدث بيانات التتبع من API
5. WHERE الشحنة تحتوي على معلومات الناقل، THE CustomerApp SHALL تعرض تفاصيل شركة الشحن

### المتطلب 5: معالجة الأخطاء وتجربة المستخدم

**قصة المستخدم:** كعميل، أريد تجربة مستخدم سلسة حتى عند حدوث أخطاء، حتى أفهم ما يحدث وكيف أتعامل مع المشكلة.

#### معايير القبول

1. WHEN يحدث خطأ في الشبكة، THE CustomerApp SHALL تعرض رسالة خطأ واضحة مع خيار إعادة المحاولة
2. WHEN رقم التتبع غير صحيح، THE CustomerApp SHALL تعرض رسالة توضح تنسيق الرقم المطلوب
3. WHEN العميل غير مخول للوصول للشحنة، THE CustomerApp SHALL تعرض رسالة تفيد بعدم وجود صلاحية
4. WHEN API غير متاح، THE CustomerApp SHALL تعرض رسالة صيانة مع معلومات الاتصال
5. WHILE يتم تحميل البيانات، THE CustomerApp SHALL تعرض loading indicators مناسبة

### المتطلب 6: التخزين المحلي والأداء

**قصة المستخدم:** كعميل، أريد أن يحفظ التطبيق عمليات البحث السابقة، حتى أتمكن من الوصول السريع للشحنات التي بحثت عنها مسبقاً.

#### معايير القبول

1. WHEN العميل يبحث عن رقم تتبع بنجاح، THE CustomerApp SHALL تحفظ الرقم في التاريخ المحلي
2. WHEN العميل يفتح واجهة البحث، THE CustomerApp SHALL تعرض آخر عمليات البحث الناجحة
3. WHEN العميل يختار من التاريخ، THE CustomerApp SHALL تملأ حقل البحث وتبدأ البحث تلقائياً
4. WHERE البيانات محفوظة محلياً، THE CustomerApp SHALL تعرض البيانات المحفوظة أثناء تحديث البيانات من API
5. WHEN العميل يمسح التاريخ، THE CustomerApp SHALL تحذف جميع عمليات البحث المحفوظة محلياً
