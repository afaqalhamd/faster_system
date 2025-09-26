# Shipment Tracking API Usage Examples

This document provides practical code examples for using the shipment tracking API endpoints.

## Authentication

All API endpoints require authentication using Laravel Sanctum. You can authenticate in two ways:

### 1. Session-based Authentication (Web Interface)
When making requests from the web interface, authentication is handled automatically through sessions.

### 2. Token-based Authentication (External Applications)
For external applications, you need to obtain an API token:

```bash
# Login to get token
curl -X POST "http://your-domain.com/api/login" \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "email=admin@example.com&password=your_password"
```

Response:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      // User data
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

Then use the token in subsequent requests:
```bash
curl -X GET "http://your-domain.com/api/shipment-tracking/1" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

## API Endpoint Examples

### 1. Create a New Tracking Record

```javascript
// JavaScript example
const createTracking = async (saleOrderId, trackingData) => {
  try {
    const response = await fetch(`/api/sale-orders/${saleOrderId}/tracking`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(trackingData)
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Tracking created successfully:', result.data);
      return result.data;
    } else {
      console.error('Failed to create tracking:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error creating tracking:', error);
    return null;
  }
};

// Usage
const trackingData = {
  carrier_id: 1,
  tracking_number: 'TRK123456789',
  tracking_url: 'https://carrier.com/track/TRK123456789',
  status: 'In Transit',
  estimated_delivery_date: '2025-10-15',
  notes: 'Fragile items'
};

createTracking(1, trackingData);
```

### 2. Get a Specific Tracking Record

```javascript
// JavaScript example
const getTracking = async (trackingId) => {
  try {
    const response = await fetch(`/api/shipment-tracking/${trackingId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Tracking data:', result.data);
      return result.data;
    } else {
      console.error('Failed to get tracking:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error getting tracking:', error);
    return null;
  }
};

// Usage
getTracking(1);
```

### 3. Update an Existing Tracking Record

```javascript
// JavaScript example
const updateTracking = async (trackingId, trackingData) => {
  try {
    const response = await fetch(`/api/shipment-tracking/${trackingId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(trackingData)
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Tracking updated successfully:', result.data);
      return result.data;
    } else {
      console.error('Failed to update tracking:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error updating tracking:', error);
    return null;
  }
};

// Usage
const updatedData = {
  status: 'Delivered',
  actual_delivery_date: '2025-10-14T14:30:00.000000Z',
  notes: 'Delivered to front desk'
};

updateTracking(1, updatedData);
```

### 4. Delete a Tracking Record

```javascript
// JavaScript example
const deleteTracking = async (trackingId) => {
  try {
    const response = await fetch(`/api/shipment-tracking/${trackingId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Tracking deleted successfully');
      return true;
    } else {
      console.error('Failed to delete tracking:', result.message);
      return false;
    }
  } catch (error) {
    console.error('Error deleting tracking:', error);
    return false;
  }
};

// Usage
deleteTracking(1);
```

### 5. Add an Event to a Tracking Record

```javascript
// JavaScript example
const addTrackingEvent = async (trackingId, eventData) => {
  try {
    const response = await fetch(`/api/shipment-tracking/${trackingId}/events`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(eventData)
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Event added successfully:', result.data);
      return result.data;
    } else {
      console.error('Failed to add event:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error adding event:', error);
    return null;
  }
};

// Usage
const eventData = {
  event_date: '2025-09-26T14:30:00.000000Z',
  location: 'Distribution Center',
  status: 'In Transit',
  description: 'Package sorted for delivery',
  latitude: 40.7128,
  longitude: -74.0060
};

addTrackingEvent(1, eventData);
```

### 6. Get Tracking History for a Sale Order

```javascript
// JavaScript example
const getTrackingHistory = async (saleOrderId) => {
  try {
    const response = await fetch(`/api/sale-orders/${saleOrderId}/tracking-history`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    const result = await response.json();
    if (result.status) {
      console.log('Tracking history:', result.data);
      return result.data;
    } else {
      console.error('Failed to get tracking history:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error getting tracking history:', error);
    return null;
  }
};

// Usage
getTrackingHistory(1);
```

## PHP Examples

### 1. Creating Tracking Records via Service

```php
<?php

use App\Services\ShipmentTrackingService;
use App\Models\Sale\SaleOrder;

// Get the service instance
$trackingService = new ShipmentTrackingService();

// Prepare tracking data
$trackingData = [
    'sale_order_id' => 1,
    'carrier_id' => 1,
    'tracking_number' => 'TRK123456789',
    'tracking_url' => 'https://carrier.com/track/TRK123456789',
    'status' => 'In Transit',
    'estimated_delivery_date' => '2025-10-15',
    'notes' => 'Fragile items'
];

try {
    // Create the tracking record
    $tracking = $trackingService->createTracking($trackingData);
    echo "Tracking created successfully with ID: " . $tracking->id;
} catch (Exception $e) {
    echo "Failed to create tracking: " . $e->getMessage();
}
```

### 2. Adding Events via Service

```php
<?php

use App\Services\ShipmentTrackingService;
use App\Models\Sale\ShipmentTracking;

// Get the service instance
$trackingService = new ShipmentTrackingService();

// Find the tracking record
$tracking = ShipmentTracking::find(1);

if ($tracking) {
    // Prepare event data
    $eventData = [
        'event_date' => now(),
        'location' => 'Distribution Center',
        'status' => 'In Transit',
        'description' => 'Package sorted for delivery',
        'latitude' => 40.7128,
        'longitude' => -74.0060
    ];

    try {
        // Add the event
        $event = $trackingService->addTrackingEvent($tracking, $eventData);
        echo "Event added successfully with ID: " . $event->id;
    } catch (Exception $e) {
        echo "Failed to add event: " . $e->getMessage();
    }
} else {
    echo "Tracking record not found";
}
```

## Mobile App Integration Example

### Flutter/Dart Example

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ShipmentTrackingService {
  final String baseUrl = 'http://your-domain.com/api';
  final String token; // Obtain from login
  
  ShipmentTrackingService(this.token);
  
  Future<Map<String, dynamic>?> createTracking(int saleOrderId, Map<String, dynamic> data) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/sale-orders/$saleOrderId/tracking'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(data),
      );
      
      if (response.statusCode == 201) {
        final result = jsonDecode(response.body);
        if (result['status']) {
          return result['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error creating tracking: $e');
      return null;
    }
  }
  
  Future<Map<String, dynamic>?> getTracking(int trackingId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/shipment-tracking/$trackingId'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      
      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        if (result['status']) {
          return result['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error getting tracking: $e');
      return null;
    }
  }
}

// Usage
final trackingService = ShipmentTrackingService('your-api-token');

// Create tracking
final trackingData = {
  'carrier_id': 1,
  'tracking_number': 'TRK123456789',
  'status': 'In Transit',
};

final tracking = await trackingService.createTracking(1, trackingData);
if (tracking != null) {
  print('Tracking created: ${tracking['id']}');
}
```

## Error Handling

### Common Error Responses

1. **Validation Errors (422)**:
```json
{
  "status": false,
  "message": "Validation error",
  "errors": {
    "tracking_number": ["The tracking number field is required."],
    "status": ["The selected status is invalid."]
  }
}
```

2. **Not Found (404)**:
```json
{
  "status": false,
  "message": "Shipment tracking not found"
}
```

3. **Unauthorized (401)**:
```json
{
  "message": "Unauthenticated."
}
```

### Handling Errors in JavaScript

```javascript
const handleApiResponse = async (response) => {
  const data = await response.json();
  
  if (!response.ok) {
    switch (response.status) {
      case 401:
        console.error('Unauthorized - Please log in');
        // Redirect to login page
        break;
      case 404:
        console.error('Resource not found');
        break;
      case 422:
        console.error('Validation errors:', data.errors);
        // Display validation errors to user
        break;
      case 500:
        console.error('Server error:', data.message);
        break;
      default:
        console.error('Unexpected error');
    }
    return null;
  }
  
  if (!data.status) {
    console.error('API Error:', data.message);
    return null;
  }
  
  return data.data;
};
```

## Testing with Postman

### 1. Create Tracking Request
- **Method**: POST
- **URL**: `http://your-domain.com/api/sale-orders/1/tracking`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Bearer YOUR_TOKEN`
- **Body** (JSON):
```json
{
  "carrier_id": 1,
  "tracking_number": "TRK123456789",
  "tracking_url": "https://carrier.com/track/TRK123456789",
  "status": "In Transit",
  "estimated_delivery_date": "2025-10-15",
  "notes": "Fragile items"
}
```

### 2. Get Tracking Request
- **Method**: GET
- **URL**: `http://your-domain.com/api/shipment-tracking/1`
- **Headers**:
  - `Accept: application/json`
  - `Authorization: Bearer YOUR_TOKEN`

## Conclusion

This document provides comprehensive examples for using the shipment tracking API. The API supports full CRUD operations for tracking records, event management, and data retrieval. All endpoints are secured with Laravel Sanctum authentication and provide consistent JSON responses with proper error handling.
