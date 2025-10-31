# خطة تنفيذ ربط API البحث عن تتبع الشحنة

- [ ] 1. إعداد البنية الأساسية للتخزين المحلي
  - إنشاء TrackingLocalDataSource للتعامل مع البحثات السابقة والتخزين المؤقت
  - إضافة methods للحفظ والاسترجاع من GetStorage
  - _المتطلبات: 6.1, 6.2, 6.3_

- [ ] 2. توسيع ShipmentTrackingRepository الموجود
  - إضافة method searchByTrackingNumber للبحث برقم التتبع
  - إضافة method validateTrackingNumber للتحقق من صحة الرقم
  - إضافة method fromCustomerSearchJson في DeliveryShipmentTrackingModel
  - _المتطلبات: 1.2, 1.3, 2.1_

- [ ] 3. توسيع TrackingBloc الموجود للعملاء
  - إضافة Events جديدة في tracking_event.dart (SearchTrackingByNumber, ValidateTrackingNumber, LoadRecentSearches)
  - إضافة States جديدة في tracking_state.dart (TrackingValidationResult, TrackingSearchSuccess, RecentSearchesLoaded)
  - إضافة event handlers في TrackingBloc للوظائف الجديدة
  - _المتطلبات: 2.2, 2.3, 2.4, 2.5_

- [ ] 4. تحديث TrackingSearchDelegate لاستخدام API
  - ربط TrackingSearchDelegate مع TrackingBloc
  - تحديث buildResults لعرض نتائج البحث من API
  - تحديث buildSuggestions لعرض البحثات السابقة والتحقق من الرقم
  - إضافة معالجة الأخطاء وعرض الرسائل المناسبة
  - _المتطلبات: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 5. تحديث TrackingDetailsPage لعرض البيانات من API
  - تحديث TrackingDetailsPage لاستقبال بيانات من TrackingBloc
  - إضافة عرض معلومات الطلب والعميل وشركة الشحن
  - إضافة عرض timeline للأحداث مع التواريخ
  - إضافة عرض المستندات المرفقة إن وجدت
  - _المتطلبات: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 6. تحديث Dependency Injection
  - إضافة TrackingLocalDataSource إلى injection_container.dart
  - تحديث تسجيل TrackingBloc لتمرير TrackingLocalDataSource
  - التأكد من تسجيل جميع التبعيات المطلوبة
  - _المتطلبات: 1.1, 2.1_

- [ ] 7. إضافة معالجة شاملة للأخطاء
  - إنشاء ErrorHandler class لتوحيد رسائل الأخطاء
  - إضافة معالجة أخطاء الشبكة والخادم والمصادقة
  - إضافة رسائل خطأ واضحة باللغة العربية
  - إضافة خيارات إعادة المحاولة للأخطاء المؤقتة
  - _المتطلبات: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 8. تحسين تجربة المستخدم والأداء
  - إضافة loading indicators مناسبة أثناء البحث
  - إضافة تخزين مؤقت للنتائج لمدة 15 دقيقة
  - إضافة validation محلي كـ fallback عند فشل API
  - إضافة حد أقصى 10 بحثات سابقة مع إمكانية المسح
  - _المتطلبات: 6.4, 6.5, 5.5_

- [ ] 9. تحديث CustomerProfileHeaderWidget
  - التأكد من أن زر البحث يستخدم TrackingBloc المحدث
  - إضافة BlocProvider عند فتح TrackingSearchDelegate
  - اختبار التنقل بين الصفحات والعودة للصفحة الرئيسية
  - _المتطلبات: 3.5_

- [ ]* 10. إضافة اختبارات الوحدة
  - كتابة unit tests لـ TrackingLocalDataSource
  - كتابة unit tests للـ methods الجديدة في ShipmentTrackingRepository
  - كتابة unit tests للـ event handlers الجديدة في TrackingBloc
  - كتابة widget tests لـ TrackingSearchDelegate المحدث
  - _المتطلبات: جميع المتطلبات_

- [ ]* 11. إضافة اختبارات التكامل
  - اختبار التدفق الكامل من البحث إلى عرض التفاصيل
  - اختبار معالجة الأخطاء المختلفة
  - اختبار التخزين المحلي والبحثات السابقة
  - اختبار الأداء مع بيانات كبيرة
  - _المتطلبات: جميع المتطلبات_
