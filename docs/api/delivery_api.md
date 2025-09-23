# Delivery API Documentation

This documentation covers the API endpoints for the carrier-based delivery system that integrates with the Flutter delivery application.

## Authentication

All endpoints require a valid Sanctum token. Include the token in the Authorization header:

```
Authorization: Bearer <your_token>
```

To obtain a token, use the login endpoint:

### POST `/api/login`

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

## Delivery Endpoints

### GET `/api/delivery-orders`

Retrieve delivery orders assigned to the carrier user.

**Query Parameters:**
- `status` (optional): Filter by status (Delivery, POD, Cancelled, Returned)
- `date_from` (optional): Filter by order date range start (YYYY-MM-DD)
- `date_to` (optional): Filter by order date range end (YYYY-MM-DD)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "order_code": "SO001",
      "order_date": "2023-01-01",
      "due_date": "2023-01-10",
      "grand_total": 100.00,
      "paid_amount": 50.00,
      "order_status": "Delivery",
      "party_id": 1,
      "carrier_id": 1,
      "party": {
        "first_name": "Customer",
        "last_name": "One"
      },
      "carrier": {
        "name": "Carrier Company"
      }
    }
  ]
}
```

### GET `/api/delivery-orders/{id}`

Get detailed information for a specific delivery order.

**Response:**
```json
{
  "status": "success",
  "data": {
    "order": {
      "id": 1,
      "order_code": "SO001",
      "order_date": "2023-01-01",
      "due_date": "2023-01-10",
      "grand_total": 100.00,
      "paid_amount": 50.00,
      "order_status": "Delivery",
      "party_id": 1,
      "carrier_id": 1,
      "itemTransaction": [
        {
          "id": 1,
          "item_id": 1,
          "quantity": 2,
          "unit_price": 50.00,
          "total": 100.00,
          "item": {
            "name": "Product One"
          }
        }
      ],
      "party": {
        "first_name": "Customer",
        "last_name": "One",
        "address": "123 Main St"
      },
      "carrier": {
        "name": "Carrier Company"
      }
    },
    "payment_records": []
  }
}
```

### POST `/api/delivery-orders/{id}/update-status`

Update order status (Delivery → POD, Cancelled, Returned).

**Request Body:**
```json
{
  "status": "POD",
  "notes": "Delivered successfully",
  "proof_image": "image_data" // Required for POD status
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Sale order status updated successfully"
}
```

### POST `/api/delivery-orders/{id}/collect-payment`

Collect payment at delivery time.

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

### GET `/api/delivery-orders/{id}/status-history`

Retrieve status history for an order.

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

### GET `/api/delivery-profile`

Get delivery user profile with carrier information.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "delivery@example.com",
    "carrier_id": 1,
    "carrier": {
      "name": "Carrier Company"
    }
  }
}
```

### GET `/api/delivery-statuses`

Get list of valid delivery statuses.

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

## Error Responses

All error responses follow this format:

```json
{
  "status": "error",
  "message": "Error description"
}
```

Common HTTP status codes:
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Internal Server Error

## Valid Status Transitions

- Pending → Processing, Completed, Delivery, POD, Cancelled
- Processing → Completed, Delivery, POD, Cancelled
- Completed → Delivery, POD, Cancelled, Returned
- Delivery → POD, Cancelled, Returned
- POD → Completed, Delivery, Cancelled, Returned
- Cancelled → (No transitions allowed)
- Returned → (No transitions allowed)
