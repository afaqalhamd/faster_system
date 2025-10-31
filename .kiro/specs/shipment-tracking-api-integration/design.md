# تصميم ربط API البحث عن تتبع الشحنة

## نظرة عامة

يهدف هذا التصميم إلى ربط واجهة البحث الموجودة في تطبيق Flutter مع API الخادم لتتبع الشحنات. سنتبع Clean Architecture pattern المستخدم في التطبيق مع BLoC لإدارة الحالة.

## البنية المعمارية

### طبقات النظام

```
Presentation Layer (UI)
├── TrackingSearchDelegate (موجود)
├── TrackingDetailsPage (موجود)
├── TrackingBloc (جديد)
├── TrackingEvent (جديد)
└── TrackingState (جديد)

Domain Layer (Business Logic)
├── Entities
│   ├── ShipmentTracking (جديد)
│   ├── TrackingEvent (جديد)
│   └── TrackingDocument (جديد)
├── Repositories
│   └── TrackingRepository (جديد)
└── Use Cases
    ├── SearchTrackingUseCase (جديد)
    ├── ValidateTrackingNumberUseCase (جديد)
    └── GetTrackingDetailsUseCase (جديد)

Data Layer (External Interface)
├── Models
│   ├── ShipmentTrackingModel (جديد)
│   ├── TrackingEventModel (جديد)
│   └── TrackingDocumentModel (جديد)
├── Data Sources
│   ├── TrackingRemoteDataSource (جديد)
│   └── TrackingLocalDataSource (جديد)
└── Repositories
    └── TrackingRepositoryImpl (جديد)
```

## مكونات التصميم

### 1. Domain Entities

#### ShipmentTracking Entity
```dart
class ShipmentTracking {
  final int id;
  final String trackingNumber;
  final String? waybillNumber;
  final String status;
  final DateTime? estimatedDeliveryDate;
  final DateTime? actualDeliveryDate;
  final String? trackingUrl;
  final String? notes;
  final OrderInfo? orderInfo;
  final CustomerInfo? customerInfo;
  final CarrierInfo? carrierInfo;
  final List<TrackingEvent> events;
  final List<TrackingDocument> documents;
  final TrackingStatistics statistics;
}
```

#### TrackingEvent Entity
```dart
class TrackingEvent {
  final int id;
  final DateTime eventDate;
  final String? location;
  final String status;
  final String description;
  final String? signature;
  final String? proofImageUrl;
  final double? latitude;
  final double? longitude;
}
```

### 2. Repository Interface

```dart
abstract class TrackingRepository {
  Future<Either<Failure, ShipmentTracking>> searchByTrackingNumber(String trackingNumber);
  Future<Either<Failure, bool>> validateTrackingNumber(String trackingNumber);
  Future<Either<Failure, ShipmentTracking>> getTrackingDetails(int trackingId);
  Future<Either<Failure, List<String>>> getRecentSearches();
  Future<Either<Failure, void>> saveRecentSearch(String trackingNumber);
  Future<Either<Failure, void>> clearRecentSearches();
}
```

### 3. Use Cases

#### SearchTrackingUseCase
```dart
class SearchTrackingUseCase {
  final TrackingRepository repository;
  
  Future<Either<Failure, ShipmentTracking>> call(String trackingNumber) async {
    // 1. Validate tracking number format
    final validationResult = await repository.validateTrackingNumber(trackingNumber);
    
    return validationResult.fold(
      (failure) => Left(failure),
      (isValid) async {
        if (!isValid) {
          return Left(ValidationFailure('Invalid tracking number format'));
        }
        
        // 2. Search for shipment
        final searchResult = await repository.searchByTrackingNumber(trackingNumber);
        
        return searchResult.fold(
          (failure) => Left(failure),
          (tracking) async {
            // 3. Save to recent searches if successful
            await repository.saveRecentSearch(trackingNumber);
            return Right(tracking);
          },
        );
      },
    );
  }
}
```

### 4. BLoC State Management (توسيع الموجود)

#### إضافة Events جديدة للعملاء
```dart
// إضافة هذه Events إلى tracking_event.dart الموجود

class SearchTrackingByNumber extends TrackingEvent {
  final String trackingNumber;

  const SearchTrackingByNumber(this.trackingNumber);

  @override
  List<Object?> get props => [trackingNumber];
}

class ValidateTrackingNumber extends TrackingEvent {
  final String trackingNumber;

  const ValidateTrackingNumber(this.trackingNumber);

  @override
  List<Object?> get props => [trackingNumber];
}

class LoadRecentSearches extends TrackingEvent {
  const LoadRecentSearches();
}

class ClearRecentSearches extends TrackingEvent {
  const ClearRecentSearches();
}

class SaveRecentSearch extends TrackingEvent {
  final String trackingNumber;

  const SaveRecentSearch(this.trackingNumber);

  @override
  List<Object?> get props => [trackingNumber];
}
```

#### إضافة States جديدة للعملاء
```dart
// إضافة هذه States إلى tracking_state.dart الموجود

class TrackingValidationResult extends TrackingState {
  final bool isValid;
  final String? message;
  final String? cleanedNumber;

  const TrackingValidationResult({
    required this.isValid,
    this.message,
    this.cleanedNumber,
  });

  @override
  List<Object?> get props => [isValid, message, cleanedNumber];
}

class TrackingSearchSuccess extends TrackingState {
  final DeliveryShipmentTracking tracking;

  const TrackingSearchSuccess(this.tracking);

  @override
  List<Object?> get props => [tracking];
}

class RecentSearchesLoaded extends TrackingState {
  final List<String> recentSearches;

  const RecentSearchesLoaded(this.recentSearches);

  @override
  List<Object?> get props => [recentSearches];
}

class RecentSearchesCleared extends TrackingState {
  const RecentSearchesCleared();
}
```

#### توسيع TrackingBloc الموجود
```dart
// تحديث TrackingBloc الموجود لإضافة الوظائف الجديدة

class TrackingBloc extends Bloc<TrackingEvent, TrackingState> {
  final ShipmentTrackingRepository _repository;
  final DeliveryRepository _deliveryRepository;
  final TrackingLocalDataSource _localDataSource; // جديد

  TrackingBloc(
    this._repository, 
    this._deliveryRepository,
    this._localDataSource, // جديد
  ) : super(const TrackingInitial()) {
    // Events الموجودة
    on<LoadOrderTracking>(_onLoadOrderTracking);
    on<LoadTrackingDetails>(_onLoadTrackingDetails);
    on<RefreshOrderTracking>(_onRefreshOrderTracking);
    on<LoadTrackingStatuses>(_onLoadTrackingStatuses);
    on<LoadDocumentTypes>(_onLoadDocumentTypes);
    
    // Events جديدة للعملاء
    on<SearchTrackingByNumber>(_onSearchTrackingByNumber);
    on<ValidateTrackingNumber>(_onValidateTrackingNumber);
    on<LoadRecentSearches>(_onLoadRecentSearches);
    on<ClearRecentSearches>(_onClearRecentSearches);
    on<SaveRecentSearch>(_onSaveRecentSearch);
  }

  // Methods جديدة للعملاء
  Future<void> _onSearchTrackingByNumber(
    SearchTrackingByNumber event,
    Emitter<TrackingState> emit,
  ) async {
    emit(const TrackingLoading());

    try {
      // 1. Validate tracking number first
      final isValid = await _repository.validateTrackingNumber(event.trackingNumber);
      
      if (!isValid) {
        emit(const TrackingValidationResult(
          isValid: false,
          message: 'تنسيق رقم التتبع غير صحيح',
        ));
        return;
      }

      // 2. Search for tracking
      final tracking = await _repository.searchByTrackingNumber(event.trackingNumber);
      
      // 3. Save to recent searches
      await _localDataSource.saveRecentSearch(event.trackingNumber);
      
      emit(TrackingSearchSuccess(tracking));
    } catch (e) {
      emit(TrackingError(_getErrorMessage(e)));
    }
  }

  Future<void> _onValidateTrackingNumber(
    ValidateTrackingNumber event,
    Emitter<TrackingState> emit,
  ) async {
    try {
      final result = await _repository.validateTrackingNumber(event.trackingNumber);
      emit(TrackingValidationResult(
        isValid: result,
        message: result ? 'رقم التتبع صحيح' : 'تنسيق رقم التتبع غير صحيح',
      ));
    } catch (e) {
      emit(TrackingError(_getErrorMessage(e)));
    }
  }

  Future<void> _onLoadRecentSearches(
    LoadRecentSearches event,
    Emitter<TrackingState> emit,
  ) async {
    try {
      final recentSearches = await _localDataSource.getRecentSearches();
      emit(RecentSearchesLoaded(recentSearches));
    } catch (e) {
      emit(TrackingError(_getErrorMessage(e)));
    }
  }

  Future<void> _onClearRecentSearches(
    ClearRecentSearches event,
    Emitter<TrackingState> emit,
  ) async {
    try {
      await _localDataSource.clearRecentSearches();
      emit(const RecentSearchesCleared());
    } catch (e) {
      emit(TrackingError(_getErrorMessage(e)));
    }
  }

  Future<void> _onSaveRecentSearch(
    SaveRecentSearch event,
    Emitter<TrackingState> emit,
  ) async {
    try {
      await _localDataSource.saveRecentSearch(event.trackingNumber);
    } catch (e) {
      // Silent fail for saving recent searches
    }
  }

  String _getErrorMessage(dynamic error) {
    if (error.toString().contains('TRACKING_NOT_FOUND')) {
      return 'لم يتم العثور على شحنة بهذا الرقم';
    } else if (error.toString().contains('ACCESS_DENIED')) {
      return 'ليس لديك صلاحية للوصول لهذه الشحنة';
    } else if (error.toString().contains('INVALID_TRACKING_NUMBER')) {
      return 'رقم التتبع غير صحيح';
    } else if (error.toString().contains('NetworkException')) {
      return 'لا يوجد اتصال بالإنترنت';
    }
    return 'حدث خطأ غير متوقع';
  }
}
```

### 5. توسيع ShipmentTrackingRepository الموجود

#### إضافة Methods جديدة للعملاء
```dart
// توسيع ShipmentTrackingRepository الموجود

class ShipmentTrackingRepository {
  final ApiClient _apiClient;

  ShipmentTrackingRepository(this._apiClient);

  // Methods الموجودة (للـ delivery)
  Future<List<DeliveryShipmentTracking>> getOrderTrackingHistory(int orderId) async { ... }
  Future<DeliveryShipmentTracking> getTrackingDetails(int trackingId) async { ... }
  Future<List<Map<String, dynamic>>> getTrackingStatuses() async { ... }
  Future<List<Map<String, dynamic>>> getDocumentTypes() async { ... }

  // Methods جديدة للعملاء
  
  /// البحث عن الشحنة برقم التتبع (للعملاء)
  Future<DeliveryShipmentTracking> searchByTrackingNumber(String trackingNumber) async {
    try {
      final response = await _apiClient.post(
        '/api/shipment-tracking/search',
        data: {'tracking_number': trackingNumber},
      );

      if (response.body['status'] == true) {
        final trackingData = response.body['data']['tracking_info'];
        
        // تحويل البيانات إلى DeliveryShipmentTracking
        final tracking = DeliveryShipmentTrackingModel.fromCustomerSearchJson(trackingData);
        return tracking;
      } else {
        final errorCode = response.body['error_code'] ?? 'UNKNOWN_ERROR';
        throw Exception('$errorCode: ${response.body['message']}');
      }
    } catch (e) {
      throw Exception('Failed to search tracking: $e');
    }
  }

  /// التحقق من صحة رقم التتبع
  Future<bool> validateTrackingNumber(String trackingNumber) async {
    try {
      final response = await _apiClient.post(
        '/api/shipment-tracking/validate',
        data: {'tracking_number': trackingNumber},
      );

      if (response.body['status'] == true) {
        return response.body['valid'] ?? false;
      } else {
        return false;
      }
    } catch (e) {
      // في حالة فشل التحقق من الخادم، نتحقق محلياً
      return _validateTrackingNumberLocally(trackingNumber);
    }
  }

  /// التحقق المحلي من رقم التتبع (fallback)
  bool _validateTrackingNumberLocally(String trackingNumber) {
    final cleanNumber = trackingNumber.replaceAll(RegExp(r'[\s\-_]'), '').toUpperCase();
    
    if (cleanNumber.length < 8 || cleanNumber.length > 50) {
      return false;
    }
    
    final patterns = [
      RegExp(r'^[A-Z]{2}\d{9}[A-Z]{2}$'),           // International format
      RegExp(r'^\d{12,22}$'),                        // Numeric only
      RegExp(r'^[A-Z0-9]{10,30}$'),                  // Alphanumeric
      RegExp(r'^1Z[A-Z0-9]{16}$'),                   // UPS format
      RegExp(r'^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$'),   // 16 digits with spaces
      RegExp(r'^[A-Z]{3}\d{8,12}$'),                // Carrier prefix + numbers
      RegExp(r'^TN\d{8,15}$'),                       // Tracking Number format
    ];
    
    return patterns.any((pattern) => pattern.hasMatch(cleanNumber));
  }
}
```

#### TrackingLocalDataSource
```dart
abstract class TrackingLocalDataSource {
  Future<List<String>> getRecentSearches();
  Future<void> saveRecentSearch(String trackingNumber);
  Future<void> clearRecentSearches();
  Future<ShipmentTrackingModel?> getCachedTracking(String trackingNumber);
  Future<void> cacheTracking(String trackingNumber, ShipmentTrackingModel tracking);
}

class TrackingLocalDataSourceImpl implements TrackingLocalDataSource {
  final GetStorage storage;
  static const String _recentSearchesKey = 'recent_tracking_searches';
  static const String _cachedTrackingPrefix = 'cached_tracking_';
  
  @override
  Future<List<String>> getRecentSearches() async {
    final searches = storage.read<List>(_recentSearchesKey) ?? [];
    return searches.cast<String>();
  }
  
  @override
  Future<void> saveRecentSearch(String trackingNumber) async {
    final searches = await getRecentSearches();
    
    // Remove if already exists
    searches.remove(trackingNumber);
    
    // Add to beginning
    searches.insert(0, trackingNumber);
    
    // Keep only last 10 searches
    if (searches.length > 10) {
      searches.removeRange(10, searches.length);
    }
    
    await storage.write(_recentSearchesKey, searches);
  }
}
```

## واجهات المستخدم

### 1. تحديث TrackingSearchDelegate الموجود

```dart
// تحديث TrackingSearchDelegate الموجود لاستخدام TrackingBloc

class TrackingSearchDelegate extends SearchDelegate<String> {
  final AppLocalizations localizations;
  late final TrackingBloc _trackingBloc;

  TrackingSearchDelegate({required this.localizations}) {
    _trackingBloc = sl<TrackingBloc>();
    // تحميل البحثات السابقة عند بدء التشغيل
    _trackingBloc.add(const LoadRecentSearches());
  }

  @override
  Widget buildResults(BuildContext context) {
    if (query.isEmpty) {
      return _buildEmptyState(context);
    }

    // بدء البحث عند عرض النتائج
    _trackingBloc.add(SearchTrackingByNumber(query));

    return BlocConsumer<TrackingBloc, TrackingState>(
      bloc: _trackingBloc,
      listener: (context, state) {
        if (state is TrackingSearchSuccess) {
          // الانتقال إلى صفحة تفاصيل التتبع
          close(context, query);
          _navigateToTrackingDetails(context, state.tracking);
        } else if (state is TrackingError) {
          // عرض رسالة الخطأ
          _showErrorSnackbar(context, state.message);
        }
      },
      builder: (context, state) {
        if (state is TrackingLoading) {
          return _buildLoadingState(context);
        } else if (state is TrackingValidationResult && !state.isValid) {
          return _buildInvalidFormatState(context, state.message);
        } else if (state is TrackingSearchSuccess) {
          return _buildSuccessState(context, state.tracking);
        } else if (state is TrackingError) {
          return _buildErrorState(context, state.message);
        }
        
        return _buildInitialState(context);
      },
    );
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    if (query.isNotEmpty) {
      // التحقق من صحة الرقم أثناء الكتابة
      _trackingBloc.add(ValidateTrackingNumber(query));
    }

    return BlocBuilder<TrackingBloc, TrackingState>(
      bloc: _trackingBloc,
      builder: (context, state) {
        if (state is RecentSearchesLoaded) {
          return _buildSuggestionsWithRecent(context, state.recentSearches);
        } else if (state is TrackingValidationResult) {
          return _buildSuggestionsWithValidation(context, state);
        }
        
        return _buildInitialState(context);
      },
    );
  }

  Widget _buildSuggestionsWithValidation(BuildContext context, TrackingValidationResult validation) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // عرض حالة التحقق
          if (query.isNotEmpty) ...[
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: validation.isValid 
                    ? Colors.green.withValues(alpha: 0.1)
                    : Colors.orange.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: validation.isValid 
                      ? Colors.green.withValues(alpha: 0.3)
                      : Colors.orange.withValues(alpha: 0.3),
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    validation.isValid 
                        ? Icons.check_circle_outline
                        : Icons.warning_outlined,
                    color: validation.isValid ? Colors.green : Colors.orange,
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      validation.message ?? '',
                      style: TextStyle(
                        color: validation.isValid ? Colors.green : Colors.orange,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // زر البحث إذا كان الرقم صحيح
            if (validation.isValid)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => showResults(context),
                  icon: const Icon(Icons.search),
                  label: Text(localizations.translate('search_tracking')),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
          ],

          const Divider(),

          // البحثات السابقة
          BlocBuilder<TrackingBloc, TrackingState>(
            bloc: _trackingBloc,
            buildWhen: (previous, current) => current is RecentSearchesLoaded,
            builder: (context, state) {
              if (state is RecentSearchesLoaded && state.recentSearches.isNotEmpty) {
                return _buildRecentSearchesList(context, state.recentSearches);
              }
              return const SizedBox.shrink();
            },
          ),
        ],
      ),
    );
  }

  void _navigateToTrackingDetails(BuildContext context, DeliveryShipmentTracking tracking) {
    Get.toNamed(
      '/tracking-details',
      arguments: {'tracking': tracking},
    );
  }

  void _showErrorSnackbar(BuildContext context, String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  void dispose() {
    _trackingBloc.close();
    super.dispose();
  }
}
```

### 2. تحديث TrackingDetailsPage

```dart
class TrackingDetailsPage extends StatefulWidget {
  final ShipmentTracking? tracking;
  final String? trackingNumber;
  
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => sl<TrackingBloc>(),
      child: BlocConsumer<TrackingBloc, TrackingState>(
        listener: (context, state) {
          if (state is TrackingSearchError) {
            _showErrorDialog(context, state.message);
          }
        },
        builder: (context, state) {
          if (state is TrackingLoading) {
            return _buildLoadingView();
          } else if (state is TrackingSearchSuccess) {
            return _buildTrackingDetailsView(state.tracking);
          }
          
          return _buildErrorView();
        },
      ),
    );
  }
}
```

## معالجة الأخطاء

### أنواع الأخطاء

```dart
class TrackingFailure extends Failure {
  final String errorCode;
  
  const TrackingFailure({
    required String message,
    required this.errorCode,
  }) : super(message: message);
}

// Specific error types
class TrackingNotFoundFailure extends TrackingFailure {
  const TrackingNotFoundFailure() : super(
    message: 'لم يتم العثور على شحنة بهذا الرقم',
    errorCode: 'TRACKING_NOT_FOUND',
  );
}

class InvalidTrackingNumberFailure extends TrackingFailure {
  const InvalidTrackingNumberFailure() : super(
    message: 'رقم التتبع غير صحيح',
    errorCode: 'INVALID_TRACKING_NUMBER',
  );
}

class AccessDeniedFailure extends TrackingFailure {
  const AccessDeniedFailure() : super(
    message: 'ليس لديك صلاحية للوصول لهذه الشحنة',
    errorCode: 'ACCESS_DENIED',
  );
}
```

### استراتيجية معالجة الأخطاء

```dart
class ErrorHandler {
  static String getErrorMessage(Failure failure) {
    if (failure is TrackingFailure) {
      switch (failure.errorCode) {
        case 'TRACKING_NOT_FOUND':
          return 'لم يتم العثور على شحنة بهذا الرقم. تأكد من صحة الرقم وحاول مرة أخرى.';
        case 'INVALID_TRACKING_NUMBER':
          return 'تنسيق رقم التتبع غير صحيح. يجب أن يكون الرقم من 8-50 حرف.';
        case 'ACCESS_DENIED':
          return 'ليس لديك صلاحية للوصول لهذه الشحنة.';
        default:
          return failure.message;
      }
    } else if (failure is NetworkFailure) {
      return 'لا يوجد اتصال بالإنترنت. تحقق من الاتصال وحاول مرة أخرى.';
    } else if (failure is ServerFailure) {
      return 'خطأ في الخادم. حاول مرة أخرى لاحقاً.';
    }
    
    return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
  }
}
```

## استراتيجية التخزين المؤقت

### Cache Strategy

```dart
class TrackingCacheStrategy {
  static const Duration cacheExpiry = Duration(minutes: 15);
  
  static bool shouldRefreshCache(DateTime? lastUpdated) {
    if (lastUpdated == null) return true;
    
    final now = DateTime.now();
    final difference = now.difference(lastUpdated);
    
    return difference > cacheExpiry;
  }
}

class CachedTrackingModel {
  final ShipmentTrackingModel tracking;
  final DateTime cachedAt;
  
  bool get isExpired => TrackingCacheStrategy.shouldRefreshCache(cachedAt);
}
```

## الأداء والتحسين

### 1. Lazy Loading للأحداث
```dart
class TrackingEventsWidget extends StatefulWidget {
  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      itemCount: events.length + (hasMoreEvents ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == events.length) {
          // Load more indicator
          return _buildLoadMoreIndicator();
        }
        
        return TrackingEventTile(event: events[index]);
      },
    );
  }
}
```

### 2. Image Caching للمستندات
```dart
class TrackingDocumentTile extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      imageUrl: document.thumbnailUrl,
      placeholder: (context, url) => CircularProgressIndicator(),
      errorWidget: (context, url, error) => Icon(Icons.error),
      cacheManager: DefaultCacheManager(),
    );
  }
}
```

## الأمان

### 1. Token Management
```dart
class TrackingApiInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final token = CustomerAuthService.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    
    super.onRequest(options, handler);
  }
  
  @override
  void onError(DioError err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Handle token expiry
      CustomerAuthService.logout();
      NavigationService.navigateToLogin();
    }
    
    super.onError(err, handler);
  }
}
```

### 2. Data Validation
```dart
class TrackingNumberValidator {
  static bool isValid(String trackingNumber) {
    final cleanNumber = trackingNumber.replaceAll(RegExp(r'[\s\-_]'), '').toUpperCase();
    
    if (cleanNumber.length < 8 || cleanNumber.length > 50) {
      return false;
    }
    
    final patterns = [
      RegExp(r'^[A-Z]{2}\d{9}[A-Z]{2}$'),
      RegExp(r'^\d{12,22}$'),
      RegExp(r'^[A-Z0-9]{10,30}$'),
      RegExp(r'^1Z[A-Z0-9]{16}$'),
    ];
    
    return patterns.any((pattern) => pattern.hasMatch(cleanNumber));
  }
}
```

## اختبار الوحدة

### Repository Tests
```dart
class MockTrackingRemoteDataSource extends Mock implements TrackingRemoteDataSource {}
class MockTrackingLocalDataSource extends Mock implements TrackingLocalDataSource {}

void main() {
  group('TrackingRepositoryImpl', () {
    late TrackingRepositoryImpl repository;
    late MockTrackingRemoteDataSource mockRemoteDataSource;
    late MockTrackingLocalDataSource mockLocalDataSource;
    
    setUp(() {
      mockRemoteDataSource = MockTrackingRemoteDataSource();
      mockLocalDataSource = MockTrackingLocalDataSource();
      repository = TrackingRepositoryImpl(
        remoteDataSource: mockRemoteDataSource,
        localDataSource: mockLocalDataSource,
      );
    });
    
    test('should return tracking when search is successful', () async {
      // Arrange
      final trackingModel = ShipmentTrackingModel.fromJson(tTrackingJson);
      when(mockRemoteDataSource.searchByTrackingNumber(any))
          .thenAnswer((_) async => trackingModel);
      
      // Act
      final result = await repository.searchByTrackingNumber('TEST123');
      
      // Assert
      expect(result, equals(Right(trackingModel.toEntity())));
    });
  });
}
```

## التكامل مع النظام الحالي

### 1. تحديث Dependency Injection
```dart
// في injection_container.dart - تحديث التسجيل الموجود

// تحديث تسجيل TrackingBloc الموجود
sl.registerFactory<TrackingBloc>(() => TrackingBloc(
  sl(), // ShipmentTrackingRepository
  sl(), // DeliveryRepository  
  sl(), // TrackingLocalDataSource (جديد)
));

// إضافة TrackingLocalDataSource الجديد
sl.registerLazySingleton<TrackingLocalDataSource>(
  () => TrackingLocalDataSourceImpl(storage: sl()),
);

// ShipmentTrackingRepository موجود بالفعل، لا نحتاج تغيير
// sl.registerLazySingleton<ShipmentTrackingRepository>(() => ShipmentTrackingRepository(sl()));
```

### 2. Route Updates
```dart
// في main.dart
GetPage(
  name: '/tracking-search',
  page: () => BlocProvider(
    create: (context) => sl<TrackingBloc>(),
    child: TrackingSearchPage(),
  ),
),
GetPage(
  name: '/tracking-details/:trackingId',
  page: () {
    final trackingId = int.parse(Get.parameters['trackingId'] ?? '0');
    return BlocProvider(
      create: (context) => sl<TrackingBloc>(),
      child: TrackingDetailsPage(trackingId: trackingId),
    );
  },
),
```

هذا التصميم يوفر حلاً شاملاً ومرناً لربط API البحث عن تتبع الشحنة مع التطبيق، مع الحفاظ على البنية المعمارية الحالية وأفضل الممارسات في التطوير.
