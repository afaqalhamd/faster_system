# Flutter Delivery App API Documentation

This documentation provides comprehensive information for AI systems to develop a Flutter delivery application that integrates with the carrier-based delivery system. The API follows RESTful principles and uses JSON for data exchange.

## Base URL

All endpoints are prefixed with: `/api`

Example: `/api/delivery-orders`

## Authentication

All endpoints require a valid Sanctum token for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your_token>
```

### POST `/api/login`

**Purpose**: Authenticate a delivery user and obtain an access token.

**Request Body:**
```json
{
  "email": "delivery@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "delivery@example.com",
      "carrier_id": 1,
      "role": {
        "name": "Delivery"
      }
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  }
}
```

## Delivery User Profile

### GET `/api/delivery-profile`

**Purpose**: Retrieve the authenticated delivery user's profile information including carrier details.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "email": "delivery@example.com",
    "role_id": 4,
    "carrier_id": 1,
    "status": 1,
    "avatar": null,
    "mobile": "+1234567890",
    "is_allowed_all_warehouses": 0,
    "fc_token": null,
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z",
    "carrier": {
      "id": 1,
      "name": "Carrier Company",
      "email": "carrier@example.com",
      "phone": "+1234567890",
      "address": "123 Carrier St",
      "status": 1,
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

## Delivery Orders

### GET `/api/delivery-orders`

**Purpose**: Retrieve a paginated list of delivery orders assigned to the carrier user.

**Query Parameters:**
- `status` (optional): Filter by status (Delivery, POD, Cancelled, Returned)
- `date_from` (optional): Filter by order date range start (YYYY-MM-DD)
- `date_to` (optional): Filter by order date range end (YYYY-MM-DD)
- `per_page` (optional): Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12,
        "order_date": "2025-09-20",
        "due_date": null,
        "prefix_code": "SO/",
        "count_id": "1",
        "order_code": "SO/1",
        "order_status": "POD",
        "inventory_status": "deducted",
        "inventory_deducted_at": "2025-09-20T07:50:31.000000Z",
        "post_delivery_action": null,
        "post_delivery_action_at": null,
        "party_id": 1,
        "state_id": null,
        "carrier_id": 1,
        "note": null,
        "shipping_charge": "0.00",
        "is_shipping_charge_distributed": 0,
        "round_off": "0.00",
        "grand_total": "750.00",
        "paid_amount": "750.00",
        "currency_id": 1,
        "exchange_rate": "1.00",
        "created_by": 1,
        "updated_by": 2,
        "created_at": "2025-09-20T07:45:52.000000Z",
        "updated_at": "2025-09-20T07:50:31.000000Z",
        "party": {
          "id": 1,
          "prefix_code": null,
          "count_id": null,
          "party_code": null,
          "party_type": "customer",
          "is_wholesale_customer": 0,
          "default_party": 1,
          "first_name": "Renand",
          "last_name": "Qahtan",
          "email": "renand@gmail.com",
          "mobile": "055313258",
          "phone": "05563483",
          "whatsapp": "9665459524",
          "billing_address": "Saudia Arbia -Street-tno 6785",
          "shipping_address": "Saudia Arbia -Street-tno 6785",
          "currency_id": 1,
          "exchange_rate": "0.00",
          "tax_number": null,
          "tax_type": null,
          "state_id": null,
          "to_pay": "0.00",
          "to_receive": "0.00",
          "is_set_credit_limit": 0,
          "credit_limit": "0.00",
          "created_by": 1,
          "updated_by": 1,
          "status": 1,
          "created_at": "2025-09-14T21:31:33.000000Z",
          "updated_at": "2025-09-14T21:31:34.000000Z"
        },
        "carrier": {
          "id": 1,
          "name": "DHL",
          "email": "dhl@gmail.com",
          "mobile": "03456847",
          "phone": "05563483",
          "whatsapp": "9665459524",
          "address": null,
          "note": null,
          "created_by": 1,
          "updated_by": 1,
          "status": 1,
          "created_at": "2025-09-14T21:28:29.000000Z",
          "updated_at": "2025-09-14T21:28:29.000000Z"
        }
      }
    ],
    "first_page_url": "http://localhost/api/delivery-orders?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/api/delivery-orders?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost/api/delivery-orders?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "http://localhost/api/delivery-orders",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

### GET `/api/delivery-orders/{id}`

**Purpose**: Get detailed information for a specific delivery order.

**Response:**
```json
{
  "status": "success",
  "data": {
    "order": {
      "id": 12,
      "order_date": "2025-09-20",
      "due_date": null,
      "prefix_code": "SO/",
      "count_id": "1",
      "order_code": "SO/1",
      "order_status": "POD",
      "inventory_status": "deducted",
      "inventory_deducted_at": "2025-09-20T07:50:31.000000Z",
      "post_delivery_action": null,
      "post_delivery_action_at": null,
      "party_id": 1,
      "state_id": null,
      "carrier_id": 1,
      "note": null,
      "shipping_charge": "0.00",
      "is_shipping_charge_distributed": 0,
      "round_off": "0.00",
      "grand_total": "750.00",
      "paid_amount": "750.00",
      "currency_id": 1,
      "exchange_rate": "1.00",
      "created_by": 1,
      "updated_by": 2,
      "created_at": "2025-09-20T07:45:52.000000Z",
      "updated_at": "2025-09-20T07:50:31.000000Z",
      "party": {
        "id": 1,
        "prefix_code": null,
        "count_id": null,
        "party_code": null,
        "party_type": "customer",
        "is_wholesale_customer": 0,
        "default_party": 1,
        "first_name": "Renand",
        "last_name": "Qahtan",
        "email": "renand@gmail.com",
        "mobile": "055313258",
        "phone": "05563483",
        "whatsapp": "9665459524",
        "billing_address": "Saudia Arbia -Street-tno 6785",
        "shipping_address": "Saudia Arbia -Street-tno 6785",
        "currency_id": 1,
        "exchange_rate": "0.00",
        "tax_number": null,
        "tax_type": null,
        "state_id": null,
        "to_pay": "0.00",
        "to_receive": "0.00",
        "is_set_credit_limit": 0,
        "credit_limit": "0.00",
        "created_by": 1,
        "updated_by": 1,
        "status": 1,
        "created_at": "2025-09-14T21:31:33.000000Z",
        "updated_at": "2025-09-14T21:31:34.000000Z"
      },
      "itemTransaction": [
        {
          "id": 1,
          "transaction_id": 12,
          "transaction_type": "SaleOrder",
          "item_id": 1,
          "quantity": 1,
          "unit_price": "750.00",
          "total": "750.00",
          "item": {
            "id": 1,
            "name": "Samsung Galaxy S21",
            "sku": "SM-G991U"
          }
        }
      ],
      "carrier": {
        "id": 1,
        "name": "DHL",
        "email": "dhl@gmail.com",
        "mobile": "03456847",
        "phone": "05563483",
        "whatsapp": "9665459524",
        "address": null,
        "note": null,
        "created_by": 1,
        "updated_by": 1,
        "status": 1,
        "created_at": "2025-09-14T21:28:29.000000Z",
        "updated_at": "2025-09-14T21:28:29.000000Z"
      }
    },
    "payment_records": []
  }
}
```

## Delivery Status Management

### GET `/api/delivery-statuses`

**Purpose**: Get a list of valid delivery statuses that can be applied to orders.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "Delivery",
      "name": "Delivery",
      "description": "Order is out for delivery"
    },
    {
      "id": "POD",
      "name": "POD",
      "description": "Proof of delivery collected"
    },
    {
      "id": "Cancelled",
      "name": "Cancelled",
      "description": "Order cancelled"
    },
    {
      "id": "Returned",
      "name": "Returned",
      "description": "Order returned"
    }
  ]
}
```

### POST `/api/delivery-orders/{id}/update-status`

**Purpose**: Update the status of a delivery order (e.g., from Delivery to POD).

**Request Body:**
```json
{
  "status": "POD",
  "notes": "Delivered successfully",
  "proof_image": "base64_encoded_image_data" // Required for POD status
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Sale order status updated successfully"
}
```

## Payment Collection

### POST `/api/delivery-orders/{id}/collect-payment`

**Purpose**: Collect payment at the time of delivery.

**Request Body:**
```json
{
  "amount": 50.00,
  "payment_type_id": 1,
  "note": "Cash payment"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Payment collected successfully",
  "data": {
    "payment": {
      "id": 1,
      "amount": 50.00,
      "payment_type_id": 1
    },
    "updated_order": {
      "id": 1,
      "paid_amount": 100.00
    }
  }
}
```

## Order Status History

### GET `/api/delivery-orders/{id}/status-history`

**Purpose**: Retrieve the complete status history for a delivery order.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "sale_order_id": 1,
      "previous_status": "Processing",
      "new_status": "Delivery",
      "notes": null,
      "proof_image": null,
      "changed_by": 1,
      "changed_at": "2023-01-01 10:00:00",
      "changed_by_name": "Admin User"
    }
  ]
}
```

## Valid Status Transitions

Orders can only transition between specific statuses:

1. Pending → Processing, Completed, Delivery, POD, Cancelled
2. Processing → Completed, Delivery, POD, Cancelled
3. Completed → Delivery, POD, Cancelled, Returned
4. Delivery → POD, Cancelled, Returned
5. POD → Completed, Delivery, Cancelled, Returned
6. Cancelled → (No transitions allowed)
7. Returned → (No transitions allowed)

## Error Handling

All API responses follow a consistent format:

**Success Response:**
```json
{
  "status": "success",
  "message": "Description of the action",
  "data": { /* Data payload */ }
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Error description",
  "errors": { /* Optional validation errors */ }
}
```

### Common HTTP Status Codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Internal Server Error

## Implementation Guidelines for Flutter App

### 1. Authentication Flow
1. Use `/api/login` to authenticate users
2. Store the returned token securely
3. Include the token in the Authorization header for all subsequent requests

### 2. Order Listing Screen
1. Use `/api/delivery-orders` with pagination
2. Implement filters for status and date range
3. Display order information including customer name and order status

### 3. Order Details Screen
1. Use `/api/delivery-orders/{id}` to get detailed order information
2. Display customer details, item list, and payment information
3. Show current order status prominently

### 4. Status Update Feature
1. Use `/api/delivery-statuses` to populate status selection dropdown
2. Implement form for status update with notes field
3. For POD status, include image capture/upload functionality
4. Submit changes using `/api/delivery-orders/{id}/update-status`

### 5. Payment Collection Feature
1. Display remaining balance (grand_total - paid_amount)
2. Implement payment form with amount, payment type, and note fields
3. Submit payment using `/api/delivery-orders/{id}/collect-payment`

### 6. Status History Feature
1. Use `/api/delivery-orders/{id}/status-history` to retrieve history
2. Display chronological list of status changes with timestamps

### 7. Profile Management
1. Use `/api/delivery-profile` to retrieve user and carrier information
2. Display user details and assigned carrier information

## Data Models

### User Model
```json
{
  "id": 1,
  "first_name": "string",
  "last_name": "string",
  "email": "string",
  "carrier_id": 1,
  "role": {
    "name": "Delivery"
  }
}
```

### Sale Order Model
```json
{
  "id": 12,
  "order_date": "2025-09-20",
  "due_date": null,
  "prefix_code": "SO/",
  "count_id": "1",
  "order_code": "SO/1",
  "order_status": "POD",
  "inventory_status": "deducted",
  "inventory_deducted_at": "2025-09-20T07:50:31.000000Z",
  "post_delivery_action": null,
  "post_delivery_action_at": null,
  "party_id": 1,
  "state_id": null,
  "carrier_id": 1,
  "note": null,
  "shipping_charge": "0.00",
  "is_shipping_charge_distributed": 0,
  "round_off": "0.00",
  "grand_total": "750.00",
  "paid_amount": "750.00",
  "currency_id": 1,
  "exchange_rate": "1.00",
  "created_by": 1,
  "updated_by": 2,
  "created_at": "2025-09-20T07:45:52.000000Z",
  "updated_at": "2025-09-20T07:50:31.000000Z"
}
```

### Party (Customer) Model
```json
{
  "id": 1,
  "prefix_code": null,
  "count_id": null,
  "party_code": null,
  "party_type": "customer",
  "is_wholesale_customer": 0,
  "default_party": 1,
  "first_name": "Renand",
  "last_name": "Qahtan",
  "email": "renand@gmail.com",
  "mobile": "055313258",
  "phone": "05563483",
  "whatsapp": "9665459524",
  "billing_address": "Saudia Arbia -Street-tno 6785",
  "shipping_address": "Saudia Arbia -Street-tno 6785",
  "currency_id": 1,
  "exchange_rate": "0.00",
  "tax_number": null,
  "tax_type": null,
  "state_id": null,
  "to_pay": "0.00",
  "to_receive": "0.00",
  "is_set_credit_limit": 0,
  "credit_limit": "0.00",
  "created_by": 1,
  "updated_by": 1,
  "status": 1,
  "created_at": "2025-09-14T21:31:33.000000Z",
  "updated_at": "2025-09-14T21:31:34.000000Z"
}
```

### Item Transaction Model
```json
{
  "id": 1,
  "transaction_id": 12,
  "transaction_type": "SaleOrder",
  "item_id": 1,
  "quantity": 1,
  "unit_price": "750.00",
  "total": "750.00"
}
```

### Carrier Model
```json
{
  "id": 1,
  "name": "DHL",
  "email": "dhl@gmail.com",
  "mobile": "03456847",
  "phone": "05563483",
  "whatsapp": "9665459524",
  "address": null,
  "note": null,
  "created_by": 1,
  "updated_by": 1,
  "status": 1,
  "created_at": "2025-09-14T21:28:29.000000Z",
  "updated_at": "2025-09-14T21:28:29.000000Z"
}
```

This documentation provides all the necessary information for an AI system to generate a complete Flutter delivery application that integrates with the carrier-based delivery API.
