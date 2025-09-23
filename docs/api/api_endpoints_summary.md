# API Endpoints Summary for Flutter Delivery App

This document provides a quick reference to all the API endpoints that need to be implemented in the Flutter delivery application.

## Authentication
- **POST** `/api/login` - User login and token generation

## Delivery User Profile
- **GET** `/api/delivery-profile` - Get authenticated user's profile with carrier info

## Delivery Orders
- **GET** `/api/delivery-orders` - List delivery orders (paginated, filterable)
- **GET** `/api/delivery-orders/{id}` - Get detailed information for a specific order

## Status Management
- **GET** `/api/delivery-statuses` - Get valid delivery statuses
- **POST** `/api/delivery-orders/{id}/update-status` - Update order status

## Payment Collection
- **POST** `/api/delivery-orders/{id}/collect-payment` - Collect payment at delivery

## Order History
- **GET** `/api/delivery-orders/{id}/status-history` - Get order status history

## Implementation Priority

1. **Authentication & Profile** - Essential for app access
2. **Order Listing** - Core functionality for delivery personnel
3. **Order Details** - Required for delivery execution
4. **Status Updates** - Critical for order progression
5. **Payment Collection** - Important for cash-on-delivery scenarios
6. **Status History** - Useful for tracking and transparency

## Key Data Points for UI

### Order List Screen
- Order code (order_code)
- Customer name (party.first_name + party.last_name)
- Order date (order_date)
- Due date (due_date)
- Grand total (grand_total) - Format: 750.00
- Paid amount (paid_amount) - Format: 750.00
- Current status (order_status)
- Inventory status (inventory_status)

### Order Details Screen
- All order list data plus:
- Customer contact info (party.mobile, party.phone, party.email)
- Customer addresses (party.billing_address, party.shipping_address)
- Item details (itemTransaction.item.name, itemTransaction.item.sku, itemTransaction.quantity, itemTransaction.unit_price) - Format: 750.00
- Payment records
- Carrier information (carrier.name)
- Notes (note)
- Shipping charges (shipping_charge) - Format: 0.00

### Status Update Screen
- Current status (order_status)
- Available status options
- Notes field
- Image upload for POD status

### Payment Collection Screen
- Remaining balance (grand_total - paid_amount) - Format: 750.00
- Payment amount input - Format: 750.00
- Payment type selection
- Payment notes

This summary provides a clear overview of all endpoints and their purposes for Flutter app development.
