# Implementation Prompt for Three Critical Flutter Files

## Context
Implement three essential files for a professional Flutter delivery application that integrates with a Laravel backend. The application uses a carrier-based approach for delivery operations with role-based permissions.

## Priority Order
1. ApiService (lib/services/api_service.dart) - Highest priority
2. AuthProvider (lib/providers/auth_provider.dart) - Medium priority
3. Main Application (lib/main.dart) - Lower priority

## File 1: ApiService Implementation
File: `lib/services/api_service.dart`

### Requirements:
- Use get_storage for token management (NOT shared_preferences)
- Implement REST API communication with JSON serialization
- Handle authentication with Bearer tokens
- Include proper error handling

### Key Implementation:
```dart
import 'package:get_storage/get_storage.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'http://192.168.0.238';
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
  
  // Implement GET, POST, PUT methods
  // Implement setToken() and clearToken() using _storage.write() and _storage.remove()
}
```

## File 2: AuthProvider Implementation
File: `lib/providers/auth_provider.dart`

### Requirements:
- Manage authentication state with ChangeNotifier
- Integrate with ApiService
- Handle login/logout flows
- Provide user profile data

### Key Implementation:
```dart
import 'package:flutter/foundation.dart';
import 'package:delivery_app/services/api_service.dart';

class AuthProvider with ChangeNotifier {
  bool _isLoggedIn = false;
  User? _user;
  final ApiService _apiService;
  
  // Constructor, login, logout, getProfile methods
  // Properties: isLoggedIn, user
  // Notify listeners on state changes
}
```

## File 3: Main Application File
File: `lib/main.dart`

### Requirements:
- Proper initialization of all services
- Multi-provider setup
- GetStorage initialization
- Localization configuration
- Theme with red primary color
- Splash screen routing

### Key Implementation:
```dart
import 'package:flutter/material.dart';
import 'package:get_storage/get_storage.dart';
import 'package:provider/provider.dart';
import 'package:easy_localization/easy_localization.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await EasyLocalization.ensureInitialized();
  await GetStorage.init(); // Critical for get_storage
  
  // runApp with MultiProvider setup
}

class MyApp extends StatelessWidget {
  // MaterialApp with proper theme (red primary color)
  // Localization configuration
  // Routing to splash screen
}
```

## Technical Specifications

### Dependencies to Use:
```yaml
dependencies:
  http: ^0.13.5
  get_storage: ^2.1.0
  provider: ^6.0.5
  easy_localization: ^3.0.1
```

### Theme Requirements:
- Primary color: Colors.red
- Consistent red theme across all UI elements

### Localization:
- Support Arabic and English
- Use easy_localization package

### Carrier-Based Approach:
- Delivery personnel only see orders for their carrier
- Payments visible only for their carrier and created by themselves

### Role-Based Permissions:
- Delivery users can only modify status and add payments
- All other fields hidden from delivery users

## Quality Standards:
- Production-ready code
- Proper error handling
- Clean, maintainable implementation
- Follow Flutter best practices
- Null safety compliance
