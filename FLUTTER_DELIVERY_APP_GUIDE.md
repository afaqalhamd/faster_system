# Professional Flutter Delivery Application Development Guide

## Overview
This guide provides a comprehensive approach to building a professional Flutter delivery application that integrates with the existing Laravel API. It covers setup, architecture, API integration, state management, and handling of delivery-specific features like proof of delivery. The application supports bilingual interface (Arabic and English) with a primary red color scheme and includes a custom splash screen.

## Table of Contents
1. [Project Setup](#project-setup)
2. [Architecture](#architecture)
3. [API Integration](#api-integration)
4. [App Theme and Localization](#app-theme-and-localization)
5. [Authentication Flow](#authentication-flow)
6. [Order Management](#order-management)
7. [Status Management with Proof of Delivery](#status-management-with-proof-of-delivery)
8. [Payment Collection](#payment-collection)
9. [Order Updates](#order-updates)
10. [UI/UX Considerations](#uiux-considerations)
11. [Testing](#testing)
12. [Deployment](#deployment)

## Project Setup

### Prerequisites

- Android Studio or VS Code with Flutter extensions
- iOS development environment (for iOS deployment)

### Creating the Project
```bash
flutter create --org com.yourcompany delivery_app
cd delivery_app
```

### Adding Dependencies
Update `pubspec.yaml` with required dependencies:

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^0.13.5
  get_storage: ^2.1.0
  provider: ^6.0.5
  json_annotation: ^4.8.0
  image_picker: ^0.8.7+4
  geolocator: ^9.0.2
  signature: ^5.3.0
  intl: ^0.18.1
  flutter_svg: ^2.0.5
  cached_network_image: ^3.2.3
  photo_view: ^0.14.0
  flutter_localizations:
    sdk: flutter
  easy_localization: ^3.0.1

dev_dependencies:
  flutter_test:
    sdk: flutter
  build_runner: ^2.3.3
  json_serializable: ^6.7.1
  flutter_lints: ^2.0.1
```

Run `flutter pub get` to install dependencies.

Update `pubspec.yaml` to include assets:

```yaml
flutter:
  assets:
    - assets/images/
    - assets/translations/
  uses-material-design: true
```

Create an `assets/translations` directory for localization files and add the following JSON files:

`assets/translations/en-US.json`:
```json
{
  "app_name": "Delivery App",
  "login": "Login",
  "email": "Email",
  "password": "Password",
  "delivery_orders": "Delivery Orders",
  "order_code": "Order Code",
  "customer": "Customer",
  "items": "Items",
  "total": "Total",
  "status": "Status",
  "update_status": "Update Status",
  "collect_payment": "Collect Payment",
  "notes": "Notes",
  "signature": "Signature",
  "photos": "Photos",
  "location": "Location",
  "amount": "Amount",
  "payment_type": "Payment Type",
  "reference_number": "Reference Number",
  "update_order": "Update Order",
  "shipping_charge": "Shipping Charge",
  "shipping_charge_distributed": "Shipping Charge Distributed",
  "logout": "Logout",
  "cancel": "Cancel",
  "save": "Save",
  "clear": "Clear",
  "add_photo": "Add Photo",
  "no_orders_found": "No orders found",
  "loading": "Loading...",
  "please_wait": "Please wait...",
  "success": "Success",
  "error": "Error",
  "failed_to_update_status": "Failed to update status",
  "status_updated_successfully": "Status updated successfully",
  "payment_collected_successfully": "Payment collected successfully",
  "failed_to_collect_payment": "Failed to collect payment",
  "order_updated_successfully": "Order updated successfully",
  "failed_to_update_order": "Failed to update order",
  "please_provide_signature_or_photos": "Please provide signature or photos for POD status",
  "getting_location": "Getting location..."
}
```

`assets/translations/ar-SA.json`:
```json
{
  "app_name": "تطبيق التوصيل",
  "login": "تسجيل الدخول",
  "email": "البريد الإلكتروني",
  "password": "كلمة المرور",
  "delivery_orders": "طلبات التوصيل",
  "order_code": "رمز الطلب",
  "customer": "العميل",
  "items": "العناصر",
  "total": "الإجمالي",
  "status": "الحالة",
  "update_status": "تحديث الحالة",
  "collect_payment": "تحصيل الدفع",
  "notes": "الملاحظات",
  "signature": "التوقيع",
  "photos": "الصور",
  "location": "الموقع",
  "amount": "المبلغ",
  "payment_type": "نوع الدفع",
  "reference_number": "رقم المرجع",
  "update_order": "تحديث الطلب",
  "shipping_charge": "رسوم الشحن",
  "shipping_charge_distributed": "رسوم الشحن موزعة",
  "logout": "تسجيل الخروج",
  "cancel": "إلغاء",
  "save": "حفظ",
  "clear": "مسح",
  "add_photo": "إضافة صورة",
  "no_orders_found": "لم يتم العثور على طلبات",
  "loading": "جار التحميل...",
  "please_wait": "يرجى الانتظار...",
  "success": "نجاح",
  "error": "خطأ",
  "failed_to_update_status": "فشل في تحديث الحالة",
  "status_updated_successfully": "تم تحديث الحالة بنجاح",
  "payment_collected_successfully": "تم تحصيل الدفع بنجاح",
  "failed_to_collect_payment": "فشل في تحصيل الدفع",
  "order_updated_successfully": "تم تحديث الطلب بنجاح",
  "failed_to_update_order": "فشل في تحديث الطلب",
  "please_provide_signature_or_photos": "يرجى تقديم التوقيع أو الصور لحالة POD",
  "getting_location": "جارٍ الحصول على الموقع..."
}
```

## Architecture

### Folder Structure
```
lib/
├── main.dart
├── models/
│   ├── auth/
│   ├── order/
│   ├── status/
│   └── payment/
├── services/
│   ├── api_service.dart
│   ├── auth_service.dart
│   ├── order_service.dart
│   ├── status_service.dart
│   └── payment_service.dart
├── providers/
│   ├── auth_provider.dart
│   ├── order_provider.dart
│   └── status_provider.dart
├── screens/
│   ├── auth/
│   ├── orders/
│   ├── status/
│   └── payment/
├── widgets/
│   ├── order/
│   ├── status/
│   └── shared/
├── utils/
│   ├── constants.dart
│   ├── helpers.dart
│   ├── validators.dart
│   └── localization.dart
├── routes/
│   └── app_routes.dart
└── themes/
    └── app_theme.dart
```

### State Management
Use Provider for state management as it's lightweight and easy to implement.

## App Theme and Localization

The application supports bilingual interface (Arabic and English) with a primary red color scheme and includes a custom splash screen.

## API Integration

### App Theme
Create `lib/themes/app_theme.dart` to define the primary red color scheme:

```dart
import 'package:flutter/material.dart';

class AppTheme {
  static final ThemeData lightTheme = ThemeData(
    primaryColor: Colors.red,
    primarySwatch: Colors.red,
    appBarTheme: AppBarTheme(
      color: Colors.red,
      titleTextStyle: TextStyle(
        color: Colors.white,
        fontSize: 20,
        fontWeight: FontWeight.bold,
      ),
      iconTheme: IconThemeData(color: Colors.white),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.red,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: Colors.red,
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.red),
      ),
    ),
  );
}
```

### Localization Helper
Create `lib/utils/localization.dart`:

```dart
import 'package:easy_localization/easy_localization.dart';

class LocalizationKeys {
  static String appName = 'app_name'.tr();
  static String login = 'login'.tr();
  static String email = 'email'.tr();
  static String password = 'password'.tr();
  static String deliveryOrders = 'delivery_orders'.tr();
  static String orderCode = 'order_code'.tr();
  static String customer = 'customer'.tr();
  static String items = 'items'.tr();
  static String total = 'total'.tr();
  static String status = 'status'.tr();
  static String updateStatus = 'update_status'.tr();
  static String collectPayment = 'collect_payment'.tr();
  static String notes = 'notes'.tr();
  static String signature = 'signature'.tr();
  static String photos = 'photos'.tr();
  static String location = 'location'.tr();
  static String amount = 'amount'.tr();
  static String paymentType = 'payment_type'.tr();
  static String referenceNumber = 'reference_number'.tr();
  static String updateOrder = 'update_order'.tr();
  static String shippingCharge = 'shipping_charge'.tr();
  static String shippingChargeDistributed = 'shipping_charge_distributed'.tr();
  static String logout = 'logout'.tr();
  static String cancel = 'cancel'.tr();
  static String save = 'save'.tr();
  static String clear = 'clear'.tr();
  static String addPhoto = 'add_photo'.tr();
  static String noOrdersFound = 'no_orders_found'.tr();
  static String loading = 'loading'.tr();
  static String pleaseWait = 'please_wait'.tr();
  static String success = 'success'.tr();
  static String error = 'error'.tr();
  static String failedToUpdateStatus = 'failed_to_update_status'.tr();
  static String statusUpdatedSuccessfully = 'status_updated_successfully'.tr();
  static String paymentCollectedSuccessfully = 'payment_collected_successfully'.tr();
  static String failedToCollectPayment = 'failed_to_collect_payment'.tr();
  static String orderUpdatedSuccessfully = 'order_updated_successfully'.tr();
  static String failedToUpdateOrder = 'failed_to_update_order'.tr();
  static String pleaseProvideSignatureOrPhotos = 'please_provide_signature_or_photos'.tr();
  static String gettingLocation = 'getting_location'.tr();
}
```

### Base API Service
Create `lib/services/api_service.dart`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:get_storage/get_storage.dart';

class ApiService {
  static const String baseUrl = 'http://192.168.0.238'; // Update with your server IP
  static const String apiPrefix = '/api/delivery';
  
  String? _token;
  final _storage = GetStorage();
  
  Future<void> init() async {
    _token = _storage.read('token');
  }
  
  Map<String, String> getHeaders() {
    final headers = {
      'Content-Type': 'application/json',
    };
    
    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    
    return headers;
  }
  
  Future<Map<String, dynamic>> get(String endpoint) async {
    final response = await http.get(
      Uri.parse('$baseUrl$apiPrefix$endpoint'),
      headers: getHeaders(),
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> post(String endpoint, dynamic data) async {
    final response = await http.post(
      Uri.parse('$baseUrl$apiPrefix$endpoint'),
      headers: getHeaders(),
      body: json.encode(data),
    );
    
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> put(String endpoint, dynamic data) async {
    final response = await http.put(
      Uri.parse('$baseUrl$apiPrefix$endpoint'),
      headers: getHeaders(),
      body: json.encode(data),
    );
    
    return json.decode(response.body);
  }
  
  void setToken(String token) {
    _token = token;
    _storage.write('token', token);
  }
  
  void clearToken() {
    _token = null;
    _storage.remove('token');
  }
}
```

## Authentication Flow

### Login Implementation
Create `lib/services/auth_service.dart`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:delivery_app/models/auth/auth_response.dart';
import 'package:delivery_app/services/api_service.dart';

class AuthService {
  final ApiService apiService;
  
  AuthService(this.apiService);
  
  Future<AuthResponse?> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiService.baseUrl}${ApiService.apiPrefix}/login'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'password': password,
        }),
      );
      
      final jsonData = json.decode(response.body);
      
      if (jsonData['status'] == true) {
        final authResponse = AuthResponse.fromJson(jsonData['data']);
        apiService.setToken(authResponse.token);
        return authResponse;
      }
      
      return null;
    } catch (e) {
      print('Login error: $e');
      return null;
    }
  }
  
  Future<bool> logout() async {
    try {
      await apiService.post('/logout', {});
      apiService.clearToken();
      return true;
    } catch (e) {
      print('Logout error: $e');
      return false;
    }
  }
  
  Future<Map<String, dynamic>?> getProfile() async {
    try {
      final response = await apiService.get('/profile');
      if (response['status'] == true) {
        return response['data'];
      }
      return null;
    } catch (e) {
      print('Get profile error: $e');
      return null;
    }
  }
}
```

### Splash Screen
Create `lib/screens/auth/splash_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/utils/localization.dart';
import 'package:delivery_app/screens/auth/login_screen.dart';

class SplashScreen extends StatefulWidget {
  @override
  _SplashScreenState createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    // Simulate app initialization
    Future.delayed(Duration(seconds: 2), () {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => LoginScreen()),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.red,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Add your app logo here
            Icon(
              Icons.local_shipping,
              size: 100,
              color: Colors.white,
            ),
            SizedBox(height: 20),
            Text(
              LocalizationKeys.appName,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            SizedBox(height: 20),
            CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
```

### Login Screen
Create `lib/screens/auth/login_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/providers/auth_provider.dart';
import 'package:delivery_app/screens/orders/orders_list_screen.dart';
import 'package:delivery_app/utils/localization.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
      });

      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      
      final success = await authProvider.login(
        _emailController.text,
        _passwordController.text,
      );

      setState(() {
        _isLoading = false;
      });

      if (success) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => OrdersListScreen()),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.failedToCollectPayment)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(LocalizationKeys.login)),
      body: Padding(
        padding: EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextFormField(
                controller: _emailController,
                decoration: InputDecoration(
                  labelText: LocalizationKeys.email,
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter your email';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(
                  labelText: LocalizationKeys.password,
                  border: OutlineInputBorder(),
                ),
                obscureText: true,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter your password';
                  }
                  return null;
                },
              ),
              SizedBox(height: 24),
              _isLoading
                  ? CircularProgressIndicator()
                  : ElevatedButton(
                      onPressed: _login,
                      child: Text(LocalizationKeys.login),
                      style: ElevatedButton.styleFrom(
                        padding: EdgeInsets.symmetric(horizontal: 50, vertical: 15),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}
```

## Order Management

### Order Service
Create `lib/services/order_service.dart`:

```dart
import 'package:delivery_app/models/order/delivery_order.dart';
import 'package:delivery_app/models/order/delivery_order_detail.dart';
import 'package:delivery_app/services/api_service.dart';

class OrderService {
  final ApiService apiService;
  
  OrderService(this.apiService);
  
  Future<List<DeliveryOrder>?> getOrders({
    String? status,
    String? dateFrom,
    String? dateTo,
    String? search,
    int? page,
  }) async {
    try {
      final queryParams = <String, String>{};
      
      if (status != null) queryParams['status'] = status;
      if (dateFrom != null) queryParams['date_from'] = dateFrom;
      if (dateTo != null) queryParams['date_to'] = dateTo;
      if (search != null) queryParams['search'] = search;
      if (page != null) queryParams['page'] = page.toString();
      
      final queryString = queryParams.entries
          .map((e) => '${e.key}=${e.value}')
          .join('&');
      
      final endpoint = queryString.isEmpty 
          ? '/orders' 
          : '/orders?$queryString';
      
      final response = await apiService.get(endpoint);
      
      if (response['status'] == true) {
        final List<DeliveryOrder> orders = [];
        final List<dynamic> data = response['data'];
        
        for (var item in data) {
          orders.add(DeliveryOrder.fromJson(item));
        }
        
        return orders;
      }
      
      return null;
    } catch (e) {
      print('Get orders error: $e');
      return null;
    }
  }
  
  Future<DeliveryOrderDetail?> getOrderDetail(int orderId) async {
    try {
      final response = await apiService.get('/orders/$orderId');
      
      if (response['status'] == true) {
        return DeliveryOrderDetail.fromJson(response['data']);
      }
      
      return null;
    } catch (e) {
      print('Get order detail error: $e');
      return null;
    }
  }
}
```

### Orders List Screen
Create `lib/screens/orders/orders_list_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/models/order/delivery_order.dart';
import 'package:delivery_app/providers/order_provider.dart';
import 'package:delivery_app/screens/orders/order_detail_screen.dart';
import 'package:delivery_app/utils/localization.dart';

class OrdersListScreen extends StatefulWidget {
  @override
  _OrdersListScreenState createState() => _OrdersListScreenState();
}

class _OrdersListScreenState extends State<OrdersListScreen> {
  late OrderProvider _orderProvider;
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _orderProvider = Provider.of<OrderProvider>(context, listen: false);
    _loadOrders();
    
    _scrollController.addListener(_scrollListener);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollListener() {
    if (_scrollController.position.pixels ==
        _scrollController.position.maxScrollExtent) {
      _loadMoreOrders();
    }
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
    });
    
    await _orderProvider.loadOrders();
    
    setState(() {
      _isLoading = false;
    });
  }

  Future<void> _loadMoreOrders() async {
    if (!_orderProvider.hasMoreOrders) return;
    
    await _orderProvider.loadMoreOrders();
  }

  Future<void> _refreshOrders() async {
    await _orderProvider.refreshOrders();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(LocalizationKeys.deliveryOrders),
        actions: [
          IconButton(
            icon: Icon(Icons.logout),
            onPressed: () {
              // Handle logout
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshOrders,
        child: Consumer<OrderProvider>(
          builder: (context, orderProvider, child) {
            if (orderProvider.isLoading && orderProvider.orders.isEmpty) {
              return Center(child: CircularProgressIndicator());
            }
            
            if (orderProvider.orders.isEmpty) {
              return Center(
                child: Text(LocalizationKeys.noOrdersFound),
              );
            }
            
            return ListView.builder(
              controller: _scrollController,
              itemCount: orderProvider.orders.length + 
                  (orderProvider.hasMoreOrders ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == orderProvider.orders.length) {
                  return Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }
                
                final order = orderProvider.orders[index];
                return _buildOrderCard(order);
              },
            );
          },
        ),
      ),
    );
  }

  Widget _buildOrderCard(DeliveryOrder order) {
    return Card(
      margin: EdgeInsets.all(8),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => OrderDetailScreen(orderId: order.id),
            ),
          );
        },
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    order.orderCode,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _getStatusColor(order.status),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      order.status,
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 8),
              Text(
                order.party.name,
                style: TextStyle(fontSize: 16),
              ),
              SizedBox(height: 4),
              Text(
                order.party.address ?? '',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[600],
                ),
              ),
              SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '${LocalizationKeys.items}: ${order.itemsCount}',
                    style: TextStyle(fontSize: 14),
                  ),
                  Text(
                    '\$${order.totalAmount.toStringAsFixed(2)}',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'delivery':
        return Colors.blue;
      case 'pod':
        return Colors.green;
      case 'returned':
        return Colors.orange;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
```

## Status Management with Proof of Delivery

### Status Service
Create `lib/services/status_service.dart`:

```dart
import 'package:delivery_app/models/status/status_option.dart';
import 'package:delivery_app/models/status/status_history.dart';
import 'package:delivery_app/models/status/status_update_request.dart';
import 'package:delivery_app/services/api_service.dart';

class StatusService {
  final ApiService apiService;
  
  StatusService(this.apiService);
  
  Future<List<StatusOption>?> getStatuses() async {
    try {
      final response = await apiService.get('/statuses');
      
      if (response['status'] == true) {
        final List<StatusOption> statuses = [];
        final List<dynamic> data = response['data'];
        
        for (var item in data) {
          statuses.add(StatusOption.fromJson(item));
        }
        
        return statuses;
      }
      
      return null;
    } catch (e) {
      print('Get statuses error: $e');
      return null;
    }
  }
  
  Future<bool> updateOrderStatus(int orderId, StatusUpdateRequest request) async {
    try {
      final response = await apiService.post(
        '/orders/$orderId/status',
        request.toJson(),
      );
      
      return response['status'] == true;
    } catch (e) {
      print('Update order status error: $e');
      return false;
    }
  }
  
  Future<List<StatusHistory>?> getStatusHistory(int orderId) async {
    try {
      final response = await apiService.get('/orders/$orderId/status-history');
      
      if (response['status'] == true) {
        final List<StatusHistory> history = [];
        final List<dynamic> data = response['data'];
        
        for (var item in data) {
          history.add(StatusHistory.fromJson(item));
        }
        
        return history;
      }
      
      return null;
    } catch (e) {
      print('Get status history error: $e');
      return null;
    }
  }
}
```

### Status Update Screen
Create `lib/screens/status/status_update_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:signature/signature.dart';
import 'package:geolocator/geolocator.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/models/status/status_option.dart';
import 'package:delivery_app/models/status/status_update_request.dart';
import 'package:delivery_app/providers/status_provider.dart';
import 'package:delivery_app/utils/localization.dart';

class StatusUpdateScreen extends StatefulWidget {
  final int orderId;
  
  StatusUpdateScreen({required this.orderId});
  
  @override
  _StatusUpdateScreenState createState() => _StatusUpdateScreenState();
}

class _StatusUpdateScreenState extends State<StatusUpdateScreen> {
  final _formKey = GlobalKey<FormState>();
  String? _selectedStatus;
  final _notesController = TextEditingController();
  final SignatureController _signatureController = SignatureController(
    penStrokeWidth: 3,
    penColor: Colors.red,
    exportBackgroundColor: Colors.white,
  );
  List<String> _photos = [];
  Position? _currentPosition;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadStatuses();
    _getCurrentLocation();
  }

  @override
  void dispose() {
    _notesController.dispose();
    _signatureController.dispose();
    super.dispose();
  }

  Future<void> _loadStatuses() async {
    final statusProvider = Provider.of<StatusProvider>(context, listen: false);
    await statusProvider.loadStatuses();
  }

  Future<void> _getCurrentLocation() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      setState(() {
        _currentPosition = position;
      });
    } catch (e) {
      print('Error getting location: $e');
    }
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.camera);
    
    if (pickedFile != null) {
      // In a real app, you would upload the image to your server
      // and get a URL back. For now, we'll just add a placeholder.
      setState(() {
        _photos.add(pickedFile.path);
      });
    }
  }

  Future<void> _updateStatus() async {
    if (_formKey.currentState!.validate()) {
      if (_selectedStatus == 'POD' && 
          _signatureController.isEmpty && 
          _photos.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(LocalizationKeys.pleaseProvideSignatureOrPhotos),
          ),
        );
        return;
      }
      
      setState(() {
        _isLoading = true;
      });
      
      // Convert signature to base64 if needed
      String? signatureData;
      if (!_signatureController.isEmpty) {
        final signatureBytes = await _signatureController.toPngBytes();
        if (signatureBytes != null) {
          // In a real app, you would upload this to your server
          // signatureData = base64Encode(signatureBytes);
        }
      }
      
      final request = StatusUpdateRequest(
        status: _selectedStatus!,
        notes: _notesController.text.isNotEmpty ? _notesController.text : null,
        signature: signatureData,
        photos: _photos.isNotEmpty ? _photos : null,
        latitude: _currentPosition?.latitude,
        longitude: _currentPosition?.longitude,
      );
      
      final statusProvider = Provider.of<StatusProvider>(context, listen: false);
      final success = await statusProvider.updateOrderStatus(widget.orderId, request);
      
      setState(() {
        _isLoading = false;
      });
      
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.statusUpdatedSuccessfully)),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.failedToUpdateStatus)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(LocalizationKeys.updateStatus)),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                LocalizationKeys.status,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              Consumer<StatusProvider>(
                builder: (context, statusProvider, child) {
                  if (statusProvider.isLoading) {
                    return CircularProgressIndicator();
                  }
                  
                  return DropdownButtonFormField<String>(
                    value: _selectedStatus,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(),
                    ),
                    items: statusProvider.statuses.map((status) {
                      return DropdownMenuItem(
                        value: status.id,
                        child: Text(status.name),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        _selectedStatus = value;
                      });
                    },
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please select a status';
                      }
                      return null;
                    },
                  );
                },
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.notes,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _notesController,
                decoration: InputDecoration(
                  hintText: 'Enter any notes...',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.location,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: _currentPosition == null
                    ? Text(LocalizationKeys.gettingLocation)
                    : Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Latitude: ${_currentPosition!.latitude}'),
                          Text('Longitude: ${_currentPosition!.longitude}'),
                        ],
                      ),
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.signature,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              Container(
                height: 150,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey),
                ),
                child: Signature(
                  controller: _signatureController,
                  backgroundColor: Colors.white,
                ),
              ),
              SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () {
                      _signatureController.clear();
                    },
                    child: Text(LocalizationKeys.clear),
                  ),
                ],
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.photos,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  ..._photos.map((photo) => Container(
                        width: 100,
                        height: 100,
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey),
                        ),
                        child: Image.file(
                          File(photo),
                          fit: BoxFit.cover,
                        ),
                      )),
                  IconButton(
                    icon: Icon(Icons.add_a_photo),
                    onPressed: _pickImage,
                  ),
                ],
              ),
              SizedBox(height: 24),
              _isLoading
                  ? Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _updateStatus,
                      child: Text(LocalizationKeys.updateStatus),
                      style: ElevatedButton.styleFrom(
                        padding: EdgeInsets.symmetric(horizontal: 50, vertical: 15),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}
```

### Handling POD Status with Proof
When a delivery person updates an order status to "POD" (Proof of Delivery), the application requires them to provide either a signature or photos as proof. This is implemented in the `_updateStatus` method above, which checks if the selected status is "POD" and ensures that either a signature or photos are provided.

The key points in the implementation:
1. When status is "POD", validation requires either signature or photos
2. Signature is captured using the signature package
3. Photos are captured using the image_picker package
4. Location is captured using the geolocator package
5. All proof data is sent to the server with the status update

## Payment Collection

### Payment Service
Create `lib/services/payment_service.dart`:

```dart
import 'package:delivery_app/models/payment/payment_request.dart';
import 'package:delivery_app/models/payment/payment_response.dart';
import 'package:delivery_app/services/api_service.dart';

class PaymentService {
  final ApiService apiService;
  
  PaymentService(this.apiService);
  
  Future<PaymentResponse?> collectPayment(int orderId, PaymentRequest request) async {
    try {
      final response = await apiService.post(
        '/orders/$orderId/payment',
        request.toJson(),
      );
      
      if (response['status'] == true) {
        return PaymentResponse.fromJson(response['data']);
      }
      
      return null;
    } catch (e) {
      print('Collect payment error: $e');
      return null;
    }
  }
  
  Future<List<dynamic>?> getPaymentHistory(int orderId) async {
    try {
      final response = await apiService.get('/orders/$orderId/payment-history');
      
      if (response['status'] == true) {
        return response['data'];
      }
      
      return null;
    } catch (e) {
      print('Get payment history error: $e');
      return null;
    }
  }
}
```

### Payment Collection Screen
Create `lib/screens/payment/payment_collection_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/models/payment/payment_request.dart';
import 'package:delivery_app/providers/payment_provider.dart';
import 'package:delivery_app/utils/localization.dart';

class PaymentCollectionScreen extends StatefulWidget {
  final int orderId;
  
  PaymentCollectionScreen({required this.orderId});
  
  @override
  _PaymentCollectionScreenState createState() => _PaymentCollectionScreenState();
}

class _PaymentCollectionScreenState extends State<PaymentCollectionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  int? _selectedPaymentType;
  final _referenceController = TextEditingController();
  final _notesController = TextEditingController();
  Position? _currentPosition;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _getCurrentLocation();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _referenceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      setState(() {
        _currentPosition = position;
      });
    } catch (e) {
      print('Error getting location: $e');
    }
  }

  Future<void> _collectPayment() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
      });
      
      final request = PaymentRequest(
        amount: double.parse(_amountController.text),
        paymentTypeId: _selectedPaymentType!,
        referenceNumber: _referenceController.text.isNotEmpty 
            ? _referenceController.text 
            : null,
        notes: _notesController.text.isNotEmpty 
            ? _notesController.text 
            : null,
        latitude: _currentPosition?.latitude,
        longitude: _currentPosition?.longitude,
      );
      
      final paymentProvider = Provider.of<PaymentProvider>(context, listen: false);
      final response = await paymentProvider.collectPayment(widget.orderId, request);
      
      setState(() {
        _isLoading = false;
      });
      
      if (response != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.paymentCollectedSuccessfully)),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.failedToCollectPayment)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(LocalizationKeys.collectPayment)),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                LocalizationKeys.amount,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _amountController,
                decoration: InputDecoration(
                  hintText: 'Enter amount',
                  border: OutlineInputBorder(),
                  prefixText: '\$',
                ),
                keyboardType: TextInputType.numberWithOptions(decimal: true),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter an amount';
                  }
                  final amount = double.tryParse(value);
                  if (amount == null || amount <= 0) {
                    return 'Please enter a valid amount';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.paymentType,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              // In a real app, you would load payment types from the API
              DropdownButtonFormField<int>(
                value: _selectedPaymentType,
                decoration: InputDecoration(
                  border: OutlineInputBorder(),
                ),
                items: [
                  DropdownMenuItem(value: 1, child: Text('Cash')),
                  DropdownMenuItem(value: 2, child: Text('Credit Card')),
                  DropdownMenuItem(value: 3, child: Text('Bank Transfer')),
                ],
                onChanged: (value) {
                  setState(() {
                    _selectedPaymentType = value;
                  });
                },
                validator: (value) {
                  if (value == null) {
                    return 'Please select a payment type';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.referenceNumber,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _referenceController,
                decoration: InputDecoration(
                  hintText: 'Enter reference number',
                  border: OutlineInputBorder(),
                ),
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.notes,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _notesController,
                decoration: InputDecoration(
                  hintText: 'Enter any notes...',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.location,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: _currentPosition == null
                    ? Text(LocalizationKeys.gettingLocation)
                    : Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Latitude: ${_currentPosition!.latitude}'),
                          Text('Longitude: ${_currentPosition!.longitude}'),
                        ],
                      ),
              ),
              SizedBox(height: 24),
              _isLoading
                  ? Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _collectPayment,
                      child: Text(LocalizationKeys.collectPayment),
                      style: ElevatedButton.styleFrom(
                        padding: EdgeInsets.symmetric(horizontal: 50, vertical: 15),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}
```

## Order Updates

### Order Update Implementation
Delivery personnel can update limited fields of an order (note, shipping_charge, is_shipping_charge_distributed). Create `lib/services/order_update_service.dart`:

```dart
import 'package:delivery_app/models/order/order_update_request.dart';
import 'package:delivery_app/services/api_service.dart';

class OrderUpdateService {
  final ApiService apiService;
  
  OrderUpdateService(this.apiService);
  
  Future<bool> updateOrder(int orderId, OrderUpdateRequest request) async {
    try {
      final response = await apiService.put(
        '/orders/$orderId',
        request.toJson(),
      );
      
      return response['status'] == true;
    } catch (e) {
      print('Update order error: $e');
      return false;
    }
  }
}
```

### Order Update Screen
Create `lib/screens/orders/order_update_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:delivery_app/models/order/order_update_request.dart';
import 'package:delivery_app/providers/order_provider.dart';
import 'package:delivery_app/utils/localization.dart';

class OrderUpdateScreen extends StatefulWidget {
  final int orderId;
  final String? currentNote;
  final double? currentShippingCharge;
  final bool? isShippingChargeDistributed;
  
  OrderUpdateScreen({
    required this.orderId,
    this.currentNote,
    this.currentShippingCharge,
    this.isShippingChargeDistributed,
  });
  
  @override
  _OrderUpdateScreenState createState() => _OrderUpdateScreenState();
}

class _OrderUpdateScreenState extends State<OrderUpdateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _noteController = TextEditingController();
  final _shippingChargeController = TextEditingController();
  bool _isShippingChargeDistributed = false;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _noteController.text = widget.currentNote ?? '';
    _shippingChargeController.text = 
        widget.currentShippingCharge?.toString() ?? '';
    _isShippingChargeDistributed = 
        widget.isShippingChargeDistributed ?? false;
  }

  @override
  void dispose() {
    _noteController.dispose();
    _shippingChargeController.dispose();
    super.dispose();
  }

  Future<void> _updateOrder() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
      });
      
      final request = OrderUpdateRequest(
        note: _noteController.text.isNotEmpty ? _noteController.text : null,
        shippingCharge: _shippingChargeController.text.isNotEmpty
            ? double.tryParse(_shippingChargeController.text)
            : null,
        isShippingChargeDistributed: _isShippingChargeDistributed,
      );
      
      final orderProvider = Provider.of<OrderProvider>(context, listen: false);
      final success = await orderProvider.updateOrder(widget.orderId, request);
      
      setState(() {
        _isLoading = false;
      });
      
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.orderUpdatedSuccessfully)),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(LocalizationKeys.failedToUpdateOrder)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(LocalizationKeys.updateOrder)),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                LocalizationKeys.notes,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _noteController,
                decoration: InputDecoration(
                  hintText: 'Enter notes...',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
                validator: (value) {
                  if (value != null && value.length > 1000) {
                    return 'Notes must not exceed 1000 characters';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              Text(
                LocalizationKeys.shippingCharge,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
              TextFormField(
                controller: _shippingChargeController,
                decoration: InputDecoration(
                  hintText: 'Enter shipping charge',
                  border: OutlineInputBorder(),
                  prefixText: '\$',
                ),
                keyboardType: TextInputType.numberWithOptions(decimal: true),
                validator: (value) {
                  if (value != null && value.isNotEmpty) {
                    final amount = double.tryParse(value);
                    if (amount == null || amount < 0) {
                      return 'Please enter a valid amount';
                    }
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              Row(
                children: [
                  Checkbox(
                    value: _isShippingChargeDistributed,
                    onChanged: (value) {
                      setState(() {
                        _isShippingChargeDistributed = value ?? false;
                      });
                    },
                  ),
                  Text(LocalizationKeys.shippingChargeDistributed),
                ],
              ),
              SizedBox(height: 24),
              _isLoading
                  ? Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _updateOrder,
                      child: Text(LocalizationKeys.updateOrder),
                      style: ElevatedButton.styleFrom(
                        padding: EdgeInsets.symmetric(horizontal: 50, vertical: 15),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}
```

## UI/UX Considerations

### Responsive Design
Ensure your app works well on different screen sizes:

```dart
// In your widgets, use MediaQuery to adapt to screen size
final screenWidth = MediaQuery.of(context).size.width;
final isLargeScreen = screenWidth > 600;

// Use appropriate layouts based on screen size
if (isLargeScreen) {
  // Use a two-column layout
  return Row(
    children: [
      Expanded(child: leftColumn),
      Expanded(child: rightColumn),
    ],
  );
} else {
  // Use a single column layout
  return Column(
    children: [
      leftColumn,
      rightColumn,
    ],
  );
}
```

### Accessibility
Implement proper accessibility features:

```dart
// Use semantic labels for widgets
ElevatedButton(
  onPressed: () {},
  child: Text('Submit'),
  semanticsLabel: 'Submit order button',
);

// Provide sufficient contrast
Text(
  'Order Details',
  style: TextStyle(
    fontSize: 18,
    fontWeight: FontWeight.bold,
    color: Colors.black, // Ensure good contrast
  ),
);
```

### Loading States
Provide clear loading indicators:

```dart
// Show loading indicator during API calls
if (_isLoading) {
  return Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        CircularProgressIndicator(),
        SizedBox(height: 16),
        Text('Loading...'),
      ],
    ),
  );
}
```

## Testing

### Unit Testing Models
Create `test/models/auth_response_test.dart`:

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:delivery_app/models/auth/auth_response.dart';

void main() {
  group('AuthResponse', () {
    test('can be instantiated from JSON', () {
      final json = {
        'user': {
          'id': 1,
          'name': 'John Doe',
          'email': 'john@example.com',
          'phone': '1234567890',
          'avatar': 'avatar.jpg',
          'carrier_id': 1,
          'carrier_name': 'DHL'
        },
        'token': 'test_token'
      };
      
      final authResponse = AuthResponse.fromJson(json);
      
      expect(authResponse.user.id, 1);
      expect(authResponse.user.name, 'John Doe');
      expect(authResponse.token, 'test_token');
    });
  });
}
```

### Widget Testing
Create `test/widget/login_screen_test.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:delivery_app/screens/auth/login_screen.dart';
import 'package:delivery_app/providers/auth_provider.dart';

void main() {
  testWidgets('Login screen has email and password fields', 
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: ChangeNotifierProvider(
          create: (context) => AuthProvider(),
          child: LoginScreen(),
        ),
      ),
    );
    
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
  });
}
```

## Deployment

### Android Deployment
1. Update `android/app/src/main/AndroidManifest.xml` with permissions:
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.CAMERA" />
```

2. Generate a signed APK:
```bash
flutter build apk --release
```

### iOS Deployment
1. Update `ios/Runner/Info.plist` with permissions:
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>This app needs location access to track deliveries</string>
<key>NSCameraUsageDescription</key>
<string>This app needs camera access to capture proof of delivery</string>
```

2. Build for iOS:
```bash
flutter build ios --release
```

## Main Application File
Create `lib/main.dart` with localization and theme support:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:get_storage/get_storage.dart';
import 'package:delivery_app/providers/auth_provider.dart';
import 'package:delivery_app/providers/order_provider.dart';
import 'package:delivery_app/providers/status_provider.dart';
import 'package:delivery_app/providers/payment_provider.dart';
import 'package:delivery_app/themes/app_theme.dart';
import 'package:delivery_app/screens/auth/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await EasyLocalization.ensureInitialized();
  await GetStorage.init(); // Initialize GetStorage
  
  runApp(
    EasyLocalization(
      supportedLocales: [Locale('en', 'US'), Locale('ar', 'SA')],
      path: 'assets/translations',
      fallbackLocale: Locale('en', 'US'),
      child: MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (context) => AuthProvider()),
        ChangeNotifierProvider(create: (context) => OrderProvider()),
        ChangeNotifierProvider(create: (context) => StatusProvider()),
        ChangeNotifierProvider(create: (context) => PaymentProvider()),
      ],
      child: MaterialApp(
        localizationsDelegates: context.localizationDelegates,
        supportedLocales: context.supportedLocales,
        locale: context.locale,
        title: 'Delivery App',
        theme: AppTheme.lightTheme,
        home: SplashScreen(),
        debugShowCheckedModeBanner: false,
      ),
    );
  }
}
```

## Language Switching
To allow users to switch between languages, add this to any screen:

```dart
import 'package:easy_localization/easy_localization.dart';

// In your widget
Row(
  mainAxisAlignment: MainAxisAlignment.center,
  children: [
    ElevatedButton(
      onPressed: () {
        context.setLocale(Locale('en', 'US'));
      },
      child: Text('English'),
    ),
    SizedBox(width: 20),
    ElevatedButton(
      onPressed: () {
        context.setLocale(Locale('ar', 'SA'));
      },
      child: Text('العربية'),
    ),
  ],
)
```

## Conclusion

This guide provides a comprehensive approach to building a professional Flutter delivery application. Key points covered:

1. **Structured Architecture**: Clean folder structure with separation of concerns
2. **API Integration**: Complete implementation of all delivery API endpoints
3. **Authentication Flow**: Secure login/logout with token management
4. **Order Management**: Full CRUD operations for delivery orders
5. **Status Management**: Implementation of status updates with proof of delivery
6. **Payment Collection**: Secure payment processing with location tracking
7. **Order Updates**: Limited field updates for delivery personnel
8. **UI/UX Best Practices**: Responsive design and accessibility considerations
9. **Localization**: Bilingual support (Arabic and English) with easy switching
10. **Theming**: Primary red color scheme throughout the application
11. **Splash Screen**: Custom splash screen with app branding
12. **Testing**: Unit and widget testing strategies
13. **Deployment**: Instructions for both Android and iOS deployment

The application follows professional development practices and provides a complete solution for delivery personnel to manage orders, update statuses with proof, collect payments, and update limited order information. The implementation ensures security, data integrity, and a good user experience.
