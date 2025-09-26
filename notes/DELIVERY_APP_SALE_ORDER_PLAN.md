# Delivery Application Plan Based on SaleOrderController

## Overview
This document outlines the complete plan for implementing a delivery application based on the existing SaleOrderController functionality. The delivery app will allow delivery personnel to manage sale orders assigned to their carrier, update statuses, collect payments, and track delivery progress.

## Data Models

### 1. User (Delivery Personnel)
The existing User model will be used with specific role requirements:
- Role: 'delivery'
- Carrier assignment: carrier_id (foreign key to Carrier model)
- Device token: fc_token for push notifications

### 2. SaleOrder
The existing SaleOrder model will be used with the following key fields:
- order_code: Unique identifier for the order
- order_date: Date when the order was placed
- due_date: Expected delivery date
- party_id: Customer information
- carrier_id: Assigned carrier for delivery
- grand_total: Total order amount
- paid_amount: Amount already paid
- order_status: Current status (Delivery, POD, Returned, Cancelled)
- note: Additional notes
- signature: Proof of delivery signature
- latitude/longitude: GPS coordinates for delivery location

### 3. Carrier
The existing Carrier model will be used to group delivery personnel:
- name: Carrier company name
- email, mobile, phone: Contact information
- address: Carrier address

### 4. Party (Customer)
The existing Party model contains customer information:
- first_name, last_name: Customer name
- email, mobile, phone: Contact information
- shipping_address: Delivery address
- latitude, longitude: GPS coordinates

### 5. ItemTransaction
Represents ordered items:
- item_id: Product information
- quantity: Number of items
- unit_price: Price per unit
- total: Total price for this line
- tax_amount: Applicable taxes

### 6. PaymentTransaction
Records payment collections:
- amount: Payment amount
- payment_type_id: Method of payment
- transaction_date: When payment was collected
- note: Additional payment notes

## API Endpoints

### Authentication
- `POST /api/delivery/login` - Delivery personnel login
- `GET /api/delivery/profile` - Get delivery user profile
- `POST /api/delivery/logout` - Logout delivery user

### Orders
- `GET /api/delivery/orders` - List delivery orders (with pagination and filters)
- `GET /api/delivery/orders/{id}` - Get order details
- `POST /api/delivery/orders/{id}/status` - Update order status
- `GET /api/delivery/orders/{id}/status-history` - Get order status history
- `POST /api/delivery/orders/{id}/payment` - Collect payment for order
- `GET /api/delivery/orders/{id}/payment-history` - Get payment history

### Status Management
- `GET /api/delivery/statuses` - Get valid delivery statuses

## Core Features

### 1. Order Management
Delivery personnel can:
- View assigned orders based on their carrier
- Filter orders by status, date range, and search terms
- View detailed order information including:
  - Customer details
  - Order items
  - Payment status
  - Delivery address with GPS coordinates

### 2. Status Updates
Delivery personnel can update order status with:
- Status selection (Delivery, POD, Returned, Cancelled)
- Notes for status changes
- Signature capture for proof of delivery
- Photo documentation
- GPS location tracking

### 3. Payment Collection
Delivery personnel can collect payments:
- Record payment amount
- Select payment method
- Add reference numbers
- Capture signature for payment proof
- Add notes for payment

### 4. Proof Collection
For status updates and payments:
- Signature capture
- Photo documentation
- GPS location tracking
- Timestamp recording

## Security & Access Control

### Role-Based Access
- Only users with 'delivery' role can access delivery endpoints
- Users must be assigned to a carrier
- Users can only access orders assigned to their carrier
- Orders must be in valid delivery statuses (Delivery, POD, Returned, Cancelled)

### Authentication
- Token-based authentication using Laravel Sanctum
- Device token registration for push notifications

## Implementation Tasks

### Phase 1: Authentication & User Management
1. Create delivery authentication controller
2. Implement login, profile, and logout endpoints
3. Register delivery.auth middleware
4. Validate delivery user role and carrier assignment

### Phase 2: Order Management
1. Create order controller with listing and detail endpoints
2. Implement carrier-based order filtering
3. Create API resources for order data transformation
4. Add search and filter functionality

### Phase 3: Status Management
1. Create status controller for valid statuses
2. Implement status update endpoint in order controller
3. Add validation for status values
4. Integrate with existing status history system

### Phase 4: Payment Collection
1. Create payment controller
2. Implement payment collection endpoint
3. Add validation for payment data
4. Integrate with existing payment transaction system

### Phase 5: Documentation & Testing
1. Document all API endpoints
2. Create API request validation classes
3. Write unit tests for all endpoints
4. Test security and access controls

## Technical Requirements

### Backend
- Laravel 8+ with Sanctum authentication
- MySQL database
- RESTful API design
- Proper validation and error handling
- Pagination for list endpoints
- Eager loading for performance optimization

### Mobile App Features
- Offline capability for order data
- GPS location tracking
- Signature capture
- Photo documentation
- Push notifications
- Barcode/QR code scanning for order identification

## Data Flow

1. Delivery personnel login with credentials
2. System validates role and carrier assignment
3. App fetches assigned orders for the carrier
4. Delivery personnel can view order details
5. Status updates are sent to the server with proof
6. Payments are collected and recorded
7. All actions are logged in the status history
8. Customer and admin users can track delivery progress

## Integration Points

### Existing Systems
- SaleOrder model and related data
- User authentication and role management
- Payment transaction system
- Status history tracking
- Carrier and party management

### Third-Party Services
- Firebase for push notifications
- GPS mapping services
- Payment gateway integrations (if needed)

## Future Enhancements

1. Real-time order tracking
2. Customer notification system
3. Performance analytics dashboard
4. Multi-language support
5. Integration with route optimization services
6. Barcode scanning for order verification
7. Voice notes for delivery instructions
