# Flutter Models for Delivery Application

## Overview
This document provides the complete Flutter model classes for the Delivery application based on the API responses. These models are designed to work with the `json_serializable` package for easy serialization and deserialization.

## Dependencies
Add these dependencies to your `pubspec.yaml`:
```yaml
dependencies:
  json_annotation: ^4.8.0

dev_dependencies:
  build_runner: ^2.3.3
  json_serializable: ^6.7.1
```

## Models

### 1. Base Response Models

#### ApiResponse
```dart
import 'package:json_annotation/json_annotation.dart';

part 'api_response.g.dart';

@JsonSerializable(genericArgumentFactories: true)
class ApiResponse<T> {
  final bool status;
  final String? message;
  final T? data;

  ApiResponse({required this.status, this.message, this.data});

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Object? json) fromJsonT,
  ) => _$ApiResponseFromJson(json, fromJsonT);

  Map<String, dynamic> toJson(
    Object? Function(T value) toJsonT,
  ) => _$ApiResponseToJson(this, toJsonT);
}
```

#### PaginatedResponse
```dart
import 'package:json_annotation/json_annotation.dart';

part 'paginated_response.g.dart';

@JsonSerializable(genericArgumentFactories: true)
class PaginatedResponse<T> {
  final bool status;
  final List<T> data;
  final Pagination pagination;

  PaginatedResponse({required this.status, required this.data, required this.pagination});

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Object? json) fromJsonT,
  ) => _$PaginatedResponseFromJson(json, fromJsonT);

  Map<String, dynamic> toJson(
    Object? Function(T value) toJsonT,
  ) => _$PaginatedResponseToJson(this, toJsonT);
}

@JsonSerializable()
class Pagination {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  Pagination({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory Pagination.fromJson(Map<String, dynamic> json) => _$PaginationFromJson(json);
  Map<String, dynamic> toJson() => _$PaginationToJson(this);
}
```

### 2. Authentication Models

#### LoginRequest
```dart
import 'package:json_annotation/json_annotation.dart';

part 'login_request.g.dart';

@JsonSerializable()
class LoginRequest {
  final String email;
  final String password;

  LoginRequest({required this.email, required this.password});

  factory LoginRequest.fromJson(Map<String, dynamic> json) => _$LoginRequestFromJson(json);
  Map<String, dynamic> toJson() => _$LoginRequestToJson(this);
}
```

#### AuthResponse
```dart
import 'package:json_annotation/json_annotation.dart';

part 'auth_response.g.dart';

@JsonSerializable()
class AuthResponse {
  final AuthUser user;
  final String token;

  AuthResponse({required this.user, required this.token});

  factory AuthResponse.fromJson(Map<String, dynamic> json) => _$AuthResponseFromJson(json);
  Map<String, dynamic> toJson() => _$AuthResponseToJson(this);
}

@JsonSerializable()
class AuthUser {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? avatar;
  final int carrierId;
  final String carrierName;

  AuthUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatar,
    required this.carrierId,
    required this.carrierName,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) => _$AuthUserFromJson(json);
  Map<String, dynamic> toJson() => _$AuthUserToJson(this);
}
```

### 3. Order Models

#### DeliveryOrder (List Item)
```dart
import 'package:json_annotation/json_annotation.dart';

part 'delivery_order.g.dart';

@JsonSerializable()
class DeliveryOrder {
  final int id;
  final String orderCode;
  final String prefixCode;
  final int countId;
  final DateTime orderDate;
  final DateTime? dueDate;
  final PartySummary party;
  final Carrier carrier;
  final double totalAmount;
  final double paidAmount;
  final double dueAmount;
  final String status;
  final String deliveryStatus;
  final String paymentStatus;
  final int itemsCount;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;

  DeliveryOrder({
    required this.id,
    required this.orderCode,
    required this.prefixCode,
    required this.countId,
    required this.orderDate,
    this.dueDate,
    required this.party,
    required this.carrier,
    required this.totalAmount,
    required this.paidAmount,
    required this.dueAmount,
    required this.status,
    required this.deliveryStatus,
    required this.paymentStatus,
    required this.itemsCount,
    this.notes,
    required this.createdAt,
    required this.updatedAt,
  });

  factory DeliveryOrder.fromJson(Map<String, dynamic> json) => _$DeliveryOrderFromJson(json);
  Map<String, dynamic> toJson() => _$DeliveryOrderToJson(this);
}

@JsonSerializable()
class PartySummary {
  final int id;
  final String name;
  final String? phone;
  final String? address;
  final double? latitude;
  final double? longitude;

  PartySummary({
    required this.id,
    required this.name,
    this.phone,
    this.address,
    this.latitude,
    this.longitude,
  });

  factory PartySummary.fromJson(Map<String, dynamic> json) => _$PartySummaryFromJson(json);
  Map<String, dynamic> toJson() => _$PartySummaryToJson(this);
}

@JsonSerializable()
class Carrier {
  final int id;
  final String name;

  Carrier({required this.id, required this.name});

  factory Carrier.fromJson(Map<String, dynamic> json) => _$CarrierFromJson(json);
  Map<String, dynamic> toJson() => _$CarrierToJson(this);
}
```

#### DeliveryOrderDetail (Detailed View)
```dart
import 'package:json_annotation/json_annotation.dart';

part 'delivery_order_detail.g.dart';

@JsonSerializable()
class DeliveryOrderDetail {
  final int id;
  final String orderCode;
  final String prefixCode;
  final int countId;
  final DateTime orderDate;
  final DateTime? dueDate;
  final PartyDetail party;
  final Carrier carrier;
  final List<OrderItem> items;
  final OrderTotals totals;
  final List<Payment> payments;
  final double totalAmount;
  final double paidAmount;
  final double dueAmount;
  final String status;
  final String deliveryStatus;
  final String paymentStatus;
  final String? inventoryStatus;
  final String? notes;
  final String? signature;
  final DateTime createdAt;
  final DateTime updatedAt;

  DeliveryOrderDetail({
    required this.id,
    required this.orderCode,
    required this.prefixCode,
    required this.countId,
    required this.orderDate,
    this.dueDate,
    required this.party,
    required this.carrier,
    required this.items,
    required this.totals,
    required this.payments,
    required this.totalAmount,
    required this.paidAmount,
    required this.dueAmount,
    required this.status,
    required this.deliveryStatus,
    required this.paymentStatus,
    this.inventoryStatus,
    this.notes,
    this.signature,
    required this.createdAt,
    required this.updatedAt,
  });

  factory DeliveryOrderDetail.fromJson(Map<String, dynamic> json) => _$DeliveryOrderDetailFromJson(json);
  Map<String, dynamic> toJson() => _$DeliveryOrderDetailToJson(this);
}

@JsonSerializable()
class PartyDetail {
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final String? address;
  final double? latitude;
  final double? longitude;

  PartyDetail({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    this.address,
    this.latitude,
    this.longitude,
  });

  factory PartyDetail.fromJson(Map<String, dynamic> json) => _$PartyDetailFromJson(json);
  Map<String, dynamic> toJson() => _$PartyDetailToJson(this);
}

@JsonSerializable()
class OrderItem {
  final int id;
  final int itemId;
  final String name;
  final String sku;
  final double quantity;
  final String unit;
  final double price;
  final double discount;
  final double tax;
  final double total;
  final String? batchNumber;
  final List<String> serialNumbers;

  OrderItem({
    required this.id,
    required this.itemId,
    required this.name,
    required this.sku,
    required this.quantity,
    required this.unit,
    required this.price,
    required this.discount,
    required this.tax,
    required this.total,
    this.batchNumber,
    required this.serialNumbers,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) => _$OrderItemFromJson(json);
  Map<String, dynamic> toJson() => _$OrderItemToJson(this);
}

@JsonSerializable()
class OrderTotals {
  final double subtotal;
  final double discount;
  final double tax;
  final double shipping;
  final double total;

  OrderTotals({
    required this.subtotal,
    required this.discount,
    required this.tax,
    required this.shipping,
    required this.total,
  });

  factory OrderTotals.fromJson(Map<String, dynamic> json) => _$OrderTotalsFromJson(json);
  Map<String, dynamic> toJson() => _$OrderTotalsToJson(this);
}

@JsonSerializable()
class Payment {
  final int id;
  final double amount;
  final String paymentType;
  final DateTime paymentDate;
  final String? referenceNumber;
  final String? notes;

  Payment({
    required this.id,
    required this.amount,
    required this.paymentType,
    required this.paymentDate,
    this.referenceNumber,
    this.notes,
  });

  factory Payment.fromJson(Map<String, dynamic> json) => _$PaymentFromJson(json);
  Map<String, dynamic> toJson() => _$PaymentToJson(this);
}
```

### 4. Status Models

#### StatusOption
```dart
import 'package:json_annotation/json_annotation.dart';

part 'status_option.g.dart';

@JsonSerializable()
class StatusOption {
  final String id;
  final String name;
  final String description;

  StatusOption({
    required this.id,
    required this.name,
    required this.description,
  });

  factory StatusOption.fromJson(Map<String, dynamic> json) => _$StatusOptionFromJson(json);
  Map<String, dynamic> toJson() => _$StatusOptionToJson(this);
}
```

#### StatusUpdateRequest
```dart
import 'package:json_annotation/json_annotation.dart';

part 'status_update_request.g.dart';

@JsonSerializable()
class StatusUpdateRequest {
  final String status;
  final String? notes;
  final String? signature;
  final List<String>? photos;
  final double? latitude;
  final double? longitude;

  StatusUpdateRequest({
    required this.status,
    this.notes,
    this.signature,
    this.photos,
    this.latitude,
    this.longitude,
  });

  factory StatusUpdateRequest.fromJson(Map<String, dynamic> json) => _$StatusUpdateRequestFromJson(json);
  Map<String, dynamic> toJson() => _$StatusUpdateRequestToJson(this);
}
```

#### StatusHistory
```dart
import 'package:json_annotation/json_annotation.dart';

part 'status_history.g.dart';

@JsonSerializable()
class StatusHistory {
  final int id;
  final int saleOrderId;
  final String status;
  final String? notes;
  final String? signature;
  final String? proofImage;
  final double? latitude;
  final double? longitude;
  final int changedBy;
  final DateTime createdAt;
  final DateTime updatedAt;

  StatusHistory({
    required this.id,
    required this.saleOrderId,
    required this.status,
    this.notes,
    this.signature,
    this.proofImage,
    this.latitude,
    this.longitude,
    required this.changedBy,
    required this.createdAt,
    required this.updatedAt,
  });

  factory StatusHistory.fromJson(Map<String, dynamic> json) => _$StatusHistoryFromJson(json);
  Map<String, dynamic> toJson() => _$StatusHistoryToJson(this);
}
```

### 5. Payment Models

#### PaymentRequest
```dart
import 'package:json_annotation/json_annotation.dart';

part 'payment_request.g.dart';

@JsonSerializable()
class PaymentRequest {
  final double amount;
  final int paymentTypeId;
  final String? referenceNumber;
  final String? notes;
  final String? signature;
  final List<String>? photos;
  final double? latitude;
  final double? longitude;

  PaymentRequest({
    required this.amount,
    required this.paymentTypeId,
    this.referenceNumber,
    this.notes,
    this.signature,
    this.photos,
    this.latitude,
    this.longitude,
  });

  factory PaymentRequest.fromJson(Map<String, dynamic> json) => _$PaymentRequestFromJson(json);
  Map<String, dynamic> toJson() => _$PaymentRequestToJson(this);
}
```

#### PaymentResponse
```dart
import 'package:json_annotation/json_annotation.dart';

part 'payment_response.g.dart';

@JsonSerializable()
class PaymentResponse {
  final int paymentId;
  final double amount;
  final int orderId;
  final String orderCode;
  final double balance;

  PaymentResponse({
    required this.paymentId,
    required this.amount,
    required this.orderId,
    required this.orderCode,
    required this.balance,
  });

  factory PaymentResponse.fromJson(Map<String, dynamic> json) => _$PaymentResponseFromJson(json);
  Map<String, dynamic> toJson() => _$PaymentResponseToJson(this);
}
```

### 6. Order Update Models

#### OrderUpdateRequest
```dart
import 'package:json_annotation/json_annotation.dart';

part 'order_update_request.g.dart';

@JsonSerializable()
class OrderUpdateRequest {
  final String? note;
  final double? shippingCharge;
  final bool? isShippingChargeDistributed;

  OrderUpdateRequest({
    this.note,
    this.shippingCharge,
    this.isShippingChargeDistributed,
  });

  factory OrderUpdateRequest.fromJson(Map<String, dynamic> json) => _$OrderUpdateRequestFromJson(json);
  Map<String, dynamic> toJson() => _$OrderUpdateRequestToJson(this);
}
```

## Usage Examples

### 1. Parsing a List of Orders
```dart
// Assuming you have the JSON response from /api/delivery/orders
final response = await http.get(Uri.parse('http://your-api-url/api/delivery/orders'));
final json = jsonDecode(response.body);

if (json['status'] == true) {
  final paginatedResponse = PaginatedResponse<DeliveryOrder>.fromJson(
    json,
    (json) => DeliveryOrder.fromJson(json as Map<String, dynamic>),
  );
  
  // Access the orders
  final orders = paginatedResponse.data;
  final pagination = paginatedResponse.pagination;
}
```

### 2. Parsing a Detailed Order
```dart
// Assuming you have the JSON response from /api/delivery/orders/{id}
final response = await http.get(Uri.parse('http://your-api-url/api/delivery/orders/123'));
final json = jsonDecode(response.body);

if (json['status'] == true) {
  final orderDetail = DeliveryOrderDetail.fromJson(json['data'] as Map<String, dynamic>);
  // Use orderDetail as needed
}
```

### 3. Updating an Order
```dart
final updateRequest = OrderUpdateRequest(
  note: "Customer requested to leave package at the door",
  shippingCharge: 15.50,
  isShippingChargeDistributed: true,
);

final response = await http.put(
  Uri.parse('http://your-api-url/api/delivery/orders/123'),
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Content-Type': 'application/json',
  },
  body: jsonEncode(updateRequest.toJson()),
);
```

## Generating Code
To generate the serialization code, run:
```bash
flutter pub run build_runner build
```

Or for continuous watching:
```bash
flutter pub run build_runner watch
```

## Error Handling
All API responses should be wrapped in try-catch blocks to handle potential errors:

```dart
try {
  final response = await http.get(Uri.parse('http://your-api-url/api/delivery/orders'));
  final json = jsonDecode(response.body);
  
  if (json['status'] == true) {
    // Process successful response
  } else {
    // Handle API error
    print('API Error: ${json['message']}');
  }
} catch (e) {
  // Handle network or parsing errors
  print('Request failed: $e');
}
```

## Additional Notes

1. All DateTime fields are parsed from ISO 8601 format strings
2. Optional fields are marked with `?` in Dart
3. The models follow the exact structure of the API responses
4. Field names are converted from snake_case to camelCase using json_serializable
5. All models are designed to be immutable (final fields)
6. Lists are properly typed for better type safety
7. The models support both serialization and deserialization
