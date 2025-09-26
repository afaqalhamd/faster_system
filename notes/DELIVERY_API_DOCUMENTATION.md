# Delivery API Documentation

## Overview
This document provides detailed documentation for the Delivery API endpoints. The API allows delivery personnel to manage sale orders, update statuses, collect payments, and track delivery progress.

**Note: Device token functionality is temporarily disabled as per user request.**

## Authentication

### Login
**Endpoint:** `POST /api/delivery/login`

**Description:** Authenticate a delivery user and obtain an access token.

**Request Body:**
```json
{
  "email": "string",
  "password": "string"
  // device_token field is temporarily disabled
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
    "token": "string"
  }
}
```

### Get Profile
**Endpoint:** `GET /api/delivery/profile`

**Description:** Get the authenticated delivery user's profile information.

**Headers:**
```
Authorization: Bearer <token>
```

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

### Logout
**Endpoint:** `POST /api/delivery/logout`

**Description:** Logout the authenticated delivery user and invalidate the token.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": true,
  "message": "Logout successful"
}
```

## Orders

### List Orders
**Endpoint:** `GET /api/delivery/orders`

**Description:** Get a paginated list of orders assigned to the delivery user's carrier.

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `status`: Filter by order status (Delivery, POD, Returned, Cancelled)
- `date_from`: Filter by order date (YYYY-MM-DD)
- `date_to`: Filter by order date (YYYY-MM-DD)
- `search`: Search by order code or customer name
- `per_page`: Number of records per page (default: 15)
- `page`: Page number for pagination

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": "integer",
      "order_code": "string",
      "prefix_code": "string",
      "count_id": "integer",
      "order_date": "date",
      "due_date": "date",
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
      "notes": "string",
      "created_at": "datetime",
      "updated_at": "datetime"
    }
  ],
  "pagination": {
    "current_page": "integer",
    "last_page": "integer",
    "per_page": "integer",
    "total": "integer"
  }
}
```

### Get Order Details
**Endpoint:** `GET /api/delivery/orders/{id}`

**Description:** Get detailed information about a specific order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": true,
  "data": {
    "id": "integer",
    "order_code": "string",
    "prefix_code": "string",
    "count_id": "integer",
    "order_date": "date",
    "due_date": "date",
    "party": {
      "id": "integer",
      "name": "string",
      "phone": "string",
      "email": "string",
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
        "item_id": "integer",
        "name": "string",
        "sku": "string",
        "quantity": "float",
        "unit": "string",
        "price": "float",
        "discount": "float",
        "tax": "float",
        "total": "float",
        "batch_number": "string",
        "serial_numbers": ["string"]
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
    "inventory_status": "string",
    "notes": "string",
    "signature": "string",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

## Status Management

### Get Valid Statuses
**Endpoint:** `GET /api/delivery/statuses`

**Description:** Get a list of valid delivery statuses.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": "string",
      "name": "string",
      "description": "string"
    }
  ]
}
```

### Update Order Status
**Endpoint:** `POST /api/delivery/orders/{id}/status`

**Description:** Update the status of an order with proof of delivery.

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "status": "string (required, one of: Delivery, POD, Returned, Cancelled)",
  "notes": "string (optional, max 500 characters)",
  "signature": "string (optional)",
  "photos": ["string"] (optional),
  "latitude": "float (optional, between -90 and 90)",
  "longitude": "float (optional, between -180 and 180)"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Status updated successfully",
  "data": {
    "order_id": "integer",
    "order_code": "string",
    "status": "string"
  }
}
```

### Get Order Status History
**Endpoint:** `GET /api/delivery/orders/{id}/status-history`

**Description:** Get the status history for an order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": "integer",
      "sale_order_id": "integer",
      "status": "string",
      "notes": "string",
      "signature": "string",
      "proof_image": "string",
      "latitude": "float",
      "longitude": "float",
      "changed_by": "integer",
      "created_at": "datetime",
      "updated_at": "datetime"
    }
  ]
}
```

## Payment Collection

### Collect Payment
**Endpoint:** `POST /api/delivery/orders/{id}/payment`

**Description:** Collect payment for an order.

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "amount": "float (required, minimum 0)",
  "payment_type_id": "integer (required, exists in payment_types table)",
  "reference_number": "string (optional, max 100 characters)",
  "notes": "string (optional, max 500 characters)",
  "signature": "string (optional)",
  "photos": ["string"] (optional),
  "latitude": "float (optional, between -90 and 90)",
  "longitude": "float (optional, between -180 and 180)"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Payment collected successfully",
  "data": {
    "payment_id": "integer",
    "amount": "float",
    "order_id": "integer",
    "order_code": "string",
    "balance": "float"
  }
}
```

### Get Order Payment History
**Endpoint:** `GET /api/delivery/orders/{id}/payment-history`

**Description:** Get the payment history for an order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": "integer",
      "amount": "float",
      "payment_type": "string",
      "payment_date": "date",
      "reference_number": "string",
      "notes": "string"
    }
  ]
}
```

## Error Responses

All endpoints may return the following error responses:

**401 Unauthorized:**
```json
{
  "status": false,
  "message": "Unauthenticated"
}
```

**403 Forbidden:**
```json
{
  "status": false,
  "message": "Access denied. User is not a delivery personnel."
}
```

**404 Not Found:**
```json
{
  "status": false,
  "message": "Sale order not found or not assigned to your carrier"
}
```

**422 Validation Error:**
```json
{
  "status": false,
  "message": "Validation error",
  "errors": {
    "field_name": ["error message"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "status": false,
  "message": "Failed to retrieve orders: Error message"
}
```

## Authentication Headers

All endpoints except login require the following header:
```
Authorization: Bearer <access_token>
```

## Rate Limiting

The API implements rate limiting to prevent abuse. Excessive requests may result in temporary blocking.

## Data Types

- **Integer**: Whole numbers (e.g., 123)
- **Float**: Decimal numbers (e.g., 123.45)
- **String**: Text values (e.g., "Hello World")
- **Date**: Date in YYYY-MM-DD format (e.g., "2023-12-25")
- **DateTime**: Date and time in ISO 8601 format (e.g., "2023-12-25T10:30:00.000000Z")
- **Boolean**: true or false
- **Array**: Ordered list of values (e.g., [1, 2, 3])
- **Object**: Key-value pairs (e.g., {"key": "value"})
