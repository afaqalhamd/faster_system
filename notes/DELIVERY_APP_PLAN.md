# Flutter Delivery Application Plan

## Overview
This document outlines the requirements and implementation plan for a Flutter delivery application that integrates with the existing system to handle sale orders, delivery-specific statuses, and payments.

## 1. Authentication & User Management

### 1.1 Delivery Login API
**Endpoint:** `POST /api/delivery/login`
**Description:** Authenticate delivery personnel using credentials
**Request:**
```json
{
  "email": "string",
  "password": "string",
  "device_token": "string" // Optional for push notifications
}
```

**Response:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": "integer",
      "name": "string",
      "email": "string",
      "phone": "string",
      "avatar": "string",
      "carrier_id": "integer",
      "carrier_name": "string"
    },
    "token": "string", // JWT token for API authentication
    "permissions": ["array_of_permissions"]
  }
}
```

### 1.2 Delivery Profile API
**Endpoint:** `GET /api/delivery/profile`
**Description:** Get delivery personnel profile information
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "user": {
      "id": "integer",
      "name": "string",
      "email": "string",
      "phone": "string",
      "avatar": "string",
      "carrier_id": "integer",
      "carrier_name": "string"
    }
  }
}
```

## 2. Sale Orders Management

### 2.1 Get Delivery Orders API
**Endpoint:** `GET /api/delivery/orders`
**Description:** Retrieve sale orders assigned to the delivery person's carrier
**Headers:** `Authorization: Bearer {token}`
**Query Parameters:**
- `status`: Filter by order status (pending, in_transit, delivered, returned)
- `date_from`: Filter by date range start
- `date_to`: Filter by date range end
- `page`: Pagination page number
- `per_page`: Items per page

**Response:**
```json
{
  "status": true,
  "data": {
    "orders": [
      {
        "id": "integer",
        "order_code": "string",
        "prefix_code": "string",
        "count_id": "integer",
        "order_date": "date",
        "delivery_date": "date",
        "party": {
          "id": "integer",
          "name": "string",
          "phone": "string",
          "address": "string",
          "latitude": "float",
          "longitude": "float"
        },
        "carrier": {
          "id": "integer",
          "name": "string"
        },
        "total_amount": "float",
        "paid_amount": "float",
        "due_amount": "float",
        "status": "string",
        "delivery_status": "string",
        "payment_status": "string",
        "items_count": "integer",
        "notes": "string"
      }
    ],
    "pagination": {
      "current_page": "integer",
      "last_page": "integer",
      "per_page": "integer",
      "total": "integer"
    }
  }
}
```

### 2.2 Get Order Details API
**Endpoint:** `GET /api/delivery/orders/{id}`
**Description:** Retrieve detailed information for a specific sale order
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "order": {
      "id": "integer",
      "order_code": "string",
      "prefix_code": "string",
      "count_id": "integer",
      "order_date": "date",
      "delivery_date": "date",
      "party": {
        "id": "integer",
        "name": "string",
        "phone": "string",
        "address": "string",
        "latitude": "float",
        "longitude": "float"
      },
      "carrier": {
        "id": "integer",
        "name": "string"
      },
      "items": [
        {
          "id": "integer",
          "name": "string",
          "sku": "string",
          "quantity": "float",
          "unit": "string",
          "price": "float",
          "total": "float",
          "batch_number": "string", // if applicable
          "serial_numbers": ["array"] // if applicable
        }
      ],
      "totals": {
        "subtotal": "float",
        "discount": "float",
        "tax": "float",
        "shipping": "float",
        "total": "float"
      },
      "payments": [
        {
          "id": "integer",
          "amount": "float",
          "payment_type": "string",
          "payment_date": "date",
          "reference_number": "string",
          "notes": "string"
        }
      ],
      "total_amount": "float",
      "paid_amount": "float",
      "due_amount": "float",
      "status": "string",
      "delivery_status": "string",
      "payment_status": "string",
      "notes": "string",
      "signature": "string", // base64 encoded
      "documents": [
        {
          "id": "integer",
          "name": "string",
          "url": "string"
        }
      ]
    }
  }
}
```

## 3. Delivery Status Management

### 3.1 Get Available Delivery Statuses API
**Endpoint:** `GET /api/delivery/statuses`
**Description:** Retrieve available delivery statuses for the system
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "statuses": [
      {
        "id": "integer",
        "name": "string",
        "code": "string",
        "color": "string",
        "requires_proof": "boolean",
        "requires_signature": "boolean",
        "requires_photo": "boolean"
      }
    ]
  }
}
```

### 3.2 Update Order Delivery Status API
**Endpoint:** `POST /api/delivery/orders/{id}/status`
**Description:** Update the delivery status of an order
**Headers:** `Authorization: Bearer {token}`
**Request:**
```json
{
  "status_id": "integer",
  "notes": "string",
  "signature": "base64_encoded_image", // Required if status requires signature
  "photos": ["array_of_base64_images"], // Required if status requires photos
  "location": {
    "latitude": "float",
    "longitude": "float"
  }
}
```

**Response:**
```json
{
  "status": true,
  "message": "Status updated successfully",
  "data": {
    "order": {
      "id": "integer",
      "order_code": "string",
      "delivery_status": "string",
      "status_updated_at": "datetime"
    }
  }
}
```

### 3.3 Get Order Status History API
**Endpoint:** `GET /api/delivery/orders/{id}/status-history`
**Description:** Retrieve the status history for a specific order
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "history": [
      {
        "id": "integer",
        "status": {
          "id": "integer",
          "name": "string",
          "code": "string"
        },
        "notes": "string",
        "created_by": {
          "id": "integer",
          "name": "string"
        },
        "created_at": "datetime",
        "signature": "string", // URL or base64
        "photos": ["array_of_urls_or_base64"],
        "location": {
          "latitude": "float",
          "longitude": "float"
        }
      }
    ]
  }
}
```

## 4. Payment Collection

### 4.1 Get Order Payment Details API
**Endpoint:** `GET /api/delivery/orders/{id}/payment`
**Description:** Retrieve payment details for a specific order
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "order": {
      "id": "integer",
      "order_code": "string",
      "total_amount": "float",
      "paid_amount": "float",
      "due_amount": "float",
      "payment_status": "string"
    },
    "payment_methods": [
      {
        "id": "integer",
        "name": "string",
        "code": "string",
        "is_cash": "boolean"
      }
    ]
  }
}
```

### 4.2 Collect Payment API
**Endpoint:** `POST /api/delivery/orders/{id}/payment`
**Description:** Collect payment for an order
**Headers:** `Authorization: Bearer {token}`
**Request:**
```json
{
  "amount": "float",
  "payment_method_id": "integer",
  "reference_number": "string", // For bank transfers, cheques, etc.
  "notes": "string",
  "signature": "base64_encoded_image", // Customer signature
  "photos": ["array_of_base64_images"], // Payment proof photos
  "location": {
    "latitude": "float",
    "longitude": "float"
  }
}
```

**Response:**
```json
{
  "status": true,
  "message": "Payment collected successfully",
  "data": {
    "payment": {
      "id": "integer",
      "amount": "float",
      "payment_method": {
        "id": "integer",
        "name": "string"
      },
      "reference_number": "string",
      "payment_date": "datetime"
    },
    "order": {
      "id": "integer",
      "order_code": "string",
      "paid_amount": "float",
      "due_amount": "float",
      "payment_status": "string"
    }
  }
}
```

### 4.3 Get Payment History API
**Endpoint:** `GET /api/delivery/orders/{id}/payment-history`
**Description:** Retrieve payment history for a specific order
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
  "status": true,
  "data": {
    "payments": [
      {
        "id": "integer",
        "amount": "float",
        "payment_method": {
          "id": "integer",
          "name": "string"
        },
        "reference_number": "string",
        "notes": "string",
        "payment_date": "datetime",
        "collected_by": {
          "id": "integer",
          "name": "string"
        },
        "signature": "string", // URL or base64
        "photos": ["array_of_urls_or_base64"]
      }
    ]
  }
}
```

## 5. Flutter Application Structure

### 5.1 Core Modules
1. **Authentication Module**
   - Login screen
   - Profile management
   - Token management

2. **Orders Module**
   - Orders list view (with filters)
   - Order details view
   - Map integration for delivery locations
   - Order search functionality

3. **Status Management Module**
   - Status update forms
   - Proof collection (signature, photos)
   - Status history view

4. **Payment Module**
   - Payment collection forms
   - Payment method selection
   - Payment history view
   - Receipt generation

5. **Offline Support Module**
   - Local data caching
   - Offline status updates
   - Sync when online

### 5.2 Technical Requirements
- **State Management:** Provider or Riverpod
- **Networking:** Dio or http package
- **Local Storage:** Shared Preferences, SQLite (floor or sqflite)
- **Location Services:** geolocator package
- **Image Handling:** image_picker, photo_view
- **Map Integration:** google_maps_flutter or mapbox
- **Push Notifications:** firebase_messaging
- **Security:** flutter_secure_storage for token storage

## 6. Backend Implementation Requirements

### 6.1 Database Modifications
- Ensure `sale_orders` table has proper relationships with `carriers` table
- Add `delivery_status_id` field to `sale_orders` table if not exists
- Ensure proper indexing for performance

### 6.2 API Controllers
- Create `Delivery\AuthController` for authentication
- Create `Delivery\OrderController` for order management
- Create `Delivery\StatusController` for status management
- Create `Delivery\PaymentController` for payment handling

### 6.3 Middleware
- Create `DeliveryAuth` middleware to validate delivery personnel tokens
- Implement carrier-based order filtering
- Add rate limiting for API endpoints

### 6.4 Security Considerations
- JWT token authentication
- Role-based access control
- Input validation and sanitization
- SQL injection prevention
- Rate limiting to prevent abuse

## 7. Testing Requirements

### 7.1 API Testing
- Unit tests for all controller methods
- Integration tests for authentication flow
- Performance tests for high-load scenarios

### 7.2 Flutter App Testing
- Widget tests for UI components
- Integration tests for API interactions
- End-to-end tests for critical user flows
- Offline functionality testing

## 8. Deployment Considerations

### 8.1 Backend
- API versioning
- Monitoring and logging
- Error handling and reporting
- Documentation (Swagger/OpenAPI)

### 8.2 Flutter App
- App store compliance
- Performance optimization
- Crash reporting
- Analytics integration

## 9. Future Enhancements
- Real-time order notifications
- Delivery route optimization
- Customer signature capture
- Delivery performance analytics
- Multi-language support
