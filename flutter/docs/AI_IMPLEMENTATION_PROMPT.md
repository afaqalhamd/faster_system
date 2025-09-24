# Professional AI Implementation Prompt for Flutter Delivery Application

## Context
You are a senior Flutter developer working on a delivery application that integrates with an existing Laravel backend. The application needs to implement three critical components for delivery personnel functionality.

## Objective
Implement three essential files for a professional Flutter delivery application with the following requirements:
1. Use get_storage for token management instead of shared_preferences
2. Implement bilingual support (Arabic/English) with proper localization
3. Apply a consistent red color theme throughout the application
4. Include a custom splash screen with 2-second delay
5. Follow carrier-based approach for delivery operations
6. Implement role-based field-level permissions for delivery users

## Files to Implement

### 1. ApiService Implementation
File: `lib/services/api_service.dart`

Requirements:
- Use get_storage for token management
- Implement proper HTTP methods (GET, POST, PUT) with JSON serialization
- Include proper error handling
- Follow REST API conventions
- Use Bearer token authentication

Key Features:
```dart
class ApiService {
  // Base configuration
  static const String baseUrl = 'http://192.168.0.238';
  static const String apiPrefix = '/api/delivery';
  
  // Token management with get_storage
  String? _token;
  final _storage = GetStorage();
  
  // Methods to implement:
  // - init(): Initialize token from storage
  // - getHeaders(): Return headers with auth token
  // - get(): Handle GET requests
  // - post(): Handle POST requests
  // - put(): Handle PUT requests
  // - setToken(): Save token to storage
  // - clearToken(): Remove token from storage
}
```

### 2. AuthProvider Implementation
File: `lib/providers/auth_provider.dart`

Requirements:
- Manage authentication state
- Handle login/logout functionality
- Integrate with ApiService
- Provide user profile information
- Handle token lifecycle

Key Features:
```dart
class AuthProvider with ChangeNotifier {
  // State management
  bool _isLoggedIn = false;
  User? _user;
  final ApiService _apiService;
  
  // Methods to implement:
  // - login(): Authenticate user and save token
  // - logout(): Clear auth state and token
  // - getProfile(): Fetch user profile
  // - init(): Initialize provider state
  // - Properties: isLoggedIn, user, etc.
}
```

### 3. Main Application File
File: `lib/main.dart`

Requirements:
- Proper initialization of all services
- Multi-provider setup
- Localization configuration
- Theme configuration with red primary color
- Splash screen routing
- GetStorage initialization

Key Features:
```dart
void main() async {
  // Proper initialization sequence
  WidgetsFlutterBinding.ensureInitialized();
  await EasyLocalization.ensureInitialized();
  await GetStorage.init(); // Initialize GetStorage
  
  // MultiProvider setup with all required providers
  // MaterialApp configuration with localization
  // Theme setup with red color scheme
  // Routing to splash screen
}

class MyApp extends StatelessWidget {
  // MultiProvider configuration
  // MaterialApp with proper theme
  // Localization setup
  // Splash screen as home
}
```

## Technical Requirements

### Dependencies
Use these exact dependencies in pubspec.yaml:
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
```

### Theme Requirements
Implement a consistent red color theme:
- Primary color: Colors.red
- AppBar color: Colors.red
- Button colors: Colors.red with white text
- Input field borders: Red when focused

### Localization
Support both Arabic and English languages:
- Create translation files in assets/translations/
- Use easy_localization for language switching
- Implement proper RTL support for Arabic

### Splash Screen
Create a custom splash screen:
- Red background
- App logo or icon
- Loading indicator
- 2-second delay before navigating to login

### Carrier-Based Approach
Implement carrier-based filtering:
- Delivery personnel can only see orders for their carrier
- Payment visibility restricted to carrier and user
- Status updates filtered by carrier

### Role-Based Permissions
Implement field-level permissions for delivery users:
- Only allow status modification and payment addition
- Hide all other fields and sections
- Show notification banner explaining limited permissions

## Implementation Guidelines

1. Follow clean architecture principles
2. Use proper error handling and state management
3. Implement comprehensive validation
4. Write clean, readable, and maintainable code
5. Follow Flutter best practices and style guide
6. Ensure proper null safety
7. Include appropriate comments and documentation
8. Handle edge cases and error scenarios
9. Optimize for performance
10. Ensure security best practices

## Deliverables

1. Complete implementation of `lib/services/api_service.dart`
2. Complete implementation of `lib/providers/auth_provider.dart`
3. Complete implementation of `lib/main.dart`
4. Proper integration with existing localization files
5. Correct theme implementation with red color scheme
6. Functional splash screen with proper navigation
7. Working authentication flow with token management
8. Carrier-based filtering implementation
9. Role-based field-level permissions

## Quality Standards

- Code must be production-ready
- Follow Flutter linting rules
- Include proper error handling
- Implement comprehensive testing considerations
- Ensure accessibility compliance
- Optimize for performance
- Maintain security best practices
- Provide clear documentation

## Priority Order

1. ApiService implementation (highest priority - enables all API communication)
2. AuthProvider implementation (medium priority - manages authentication state)
3. Main application file (lower priority - ties everything together)

## Additional Notes

- Ensure all implementations follow the carrier-based approach
- Maintain consistency with the existing Laravel backend API structure
- Implement proper error handling for network requests
- Use proper state management with Provider
- Ensure token is properly persisted and cleared
- Follow the exact folder structure specified
- Implement proper localization support
- Apply the red color theme consistently
- Create a professional splash screen experience
