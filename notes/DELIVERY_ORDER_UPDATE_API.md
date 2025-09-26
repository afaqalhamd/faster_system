# Delivery Order Update API

## Overview
This API endpoint allows delivery personnel to update specific fields of a sale order. The update functionality is limited to only those fields that delivery personnel should be able to modify, ensuring data integrity and security.

## Endpoint
```
PUT /api/delivery/orders/{id}
```

## Authentication
This endpoint requires authentication with a valid delivery user token.

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

## Path Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | The ID of the sale order to update |

## Request Body
The request body should be a JSON object containing any of the following optional fields:

| Field | Type | Description | Validation Rules |
|-------|------|-------------|------------------|
| note | string | Additional notes about the order | Optional, max 1000 characters |
| shipping_charge | numeric | Shipping charge amount | Optional, must be a number, minimum 0 |
| is_shipping_charge_distributed | boolean | Whether shipping charge is distributed | Optional, must be true or false |

### Example Request
```json
{
  "note": "Customer requested to leave package at the door",
  "shipping_charge": 15.50,
  "is_shipping_charge_distributed": true
}
```

## Response Format

### Success Response (200)
```json
{
  "status": true,
  "message": "Order updated successfully",
  "data": {
    "id": 123,
    "order_code": "SO-000123",
    "order_date": "2023-05-15",
    "expected_delivery_date": "2023-05-17",
    "order_status": "Delivery",
    "sub_total": 100.00,
    "discount": 0.00,
    "tax": 15.00,
    "grand_total": 115.00,
    "paid_amount": 0.00,
    "due_amount": 115.00,
    "note": "Customer requested to leave package at the door",
    "shipping_charge": 15.50,
    "is_shipping_charge_distributed": true,
    "party": {
      "id": 45,
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+1234567890",
      "email": "john.doe@example.com",
      "address": "123 Main St, City, Country"
    },
    "carrier": {
      "id": 5,
      "name": "DHL Express",
      "email": "dhl@example.com"
    },
    "items": [
      {
        "id": 789,
        "item_id": 12,
        "item_name": "Product Name",
        "quantity": 2,
        "rate": 50.00,
        "total": 100.00,
        "tax_rate": 15.00,
        "tax_amount": 15.00
      }
    ]
  }
}
```

### Error Responses

#### Unauthorized Access (403)
```json
{
  "status": false,
  "message": "Unauthorized access"
}
```

#### Order Not Found (404)
```json
{
  "status": false,
  "message": "Failed to update order: No query results for model [App\\Models\\Sale\\SaleOrder] {id}"
}
```

#### Validation Error (422)
```json
{
  "status": false,
  "message": "The given data was invalid.",
  "errors": {
    "note": [
      "Notes must not exceed 1000 characters"
    ],
    "shipping_charge": [
      "Shipping charge must be a number"
    ],
    "is_shipping_charge_distributed": [
      "Shipping charge distribution must be a boolean value"
    ]
  }
}
```

#### Server Error (500)
```json
{
  "status": false,
  "message": "Failed to update order: {error_message}"
}
```

## Authorization and Security

### Role Requirements
- User must have the "delivery" role
- User must be assigned to the same carrier as the order

### Field Restrictions
Delivery personnel can only update the following fields:
- `note`
- `shipping_charge`
- `is_shipping_charge_distributed`

All other fields are protected and cannot be modified through this endpoint.

## Implementation Details

### Controller Method
The endpoint is handled by the `update` method in `App\Http\Controllers\Api\Delivery\OrderController`.

### Validation
Request validation is performed by `App\Http\Requests\DeliveryOrderUpdateRequest`.

### Carrier Verification
The system ensures that delivery personnel can only update orders assigned to their carrier by checking:
```php
SaleOrder::where('carrier_id', $user->carrier_id)->findOrFail($id)
```

## Testing the Endpoint

### Using cURL
```bash
curl -X PUT \
  http://192.168.0.238/api/delivery/orders/123 \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -H 'Content-Type: application/json' \
  -d '{
    "note": "Customer requested to leave package at the door",
    "shipping_charge": 15.50,
    "is_shipping_charge_distributed": true
  }'
```

### Using Postman
1. Set method to PUT
2. Enter URL: `http://192.168.0.238/api/delivery/orders/123`
3. In Headers tab, add:
   - Key: `Authorization`, Value: `Bearer YOUR_TOKEN_HERE`
   - Key: `Content-Type`, Value: `application/json`
4. In Body tab, select "raw" and "JSON", then enter:
```json
{
  "note": "Customer requested to leave package at the door",
  "shipping_charge": 15.50,
  "is_shipping_charge_distributed": true
}
```

## Related Endpoints
- [Delivery Login](DELIVERY_LOGIN_INSTRUCTIONS.md)
- [Get Order Details](DELIVERY_API_DOCUMENTATION.md#get-order-details)
- [Update Order Status](DELIVERY_API_DOCUMENTATION.md#update-order-status)
- [Collect Payment](DELIVERY_API_DOCUMENTATION.md#collect-payment)
