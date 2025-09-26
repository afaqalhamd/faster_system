# Shipment Tracking Complete Process Documentation

This document explains the complete process of creating and managing shipment tracking in the system, from creation through the web interface to display via API.

## Table of Contents
1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Creating Tracking Records](#creating-tracking-records)
4. [Web Interface Implementation](#web-interface-implementation)
5. [API Endpoints](#api-endpoints)
6. [JavaScript Integration](#javascript-integration)
7. [Testing the Process](#testing-the-process)

## Overview

The shipment tracking system allows users to:
- Create multiple tracking records for each sale order
- Add events to each tracking record
- Upload documents related to shipments
- View tracking history through both web interface and API

## Database Structure

The system uses three main tables:

### 1. shipment_trackings
Stores main tracking information for sale orders.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Unique identifier |
| sale_order_id | bigint (FK) | Reference to sale_orders table |
| carrier_id | bigint (FK) | Reference to carriers table |
| tracking_number | string | Carrier tracking number |
| tracking_url | string | URL to carrier tracking page |
| status | string | Current tracking status |
| estimated_delivery_date | date | Estimated delivery date |
| actual_delivery_date | datetime | Actual delivery date |
| notes | text | Additional notes |
| created_by | bigint (FK) | User who created the tracking |
| updated_by | bigint (FK) | User who last updated the tracking |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### 2. shipment_tracking_events
Stores events for each tracking record.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Unique identifier |
| shipment_tracking_id | bigint (FK) | Reference to shipment_trackings table |
| event_date | timestamp | Date and time of event |
| location | string | Location of event |
| status | string | Status at time of event |
| description | text | Event description |
| proof_image | string | Path to proof image |
| signature | text | Digital signature |
| latitude | decimal | GPS latitude |
| longitude | decimal | GPS longitude |
| created_by | bigint (FK) | User who created the event |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### 3. shipment_documents
Stores documents related to shipments.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Unique identifier |
| shipment_tracking_id | bigint (FK) | Reference to shipment_trackings table |
| document_type | string | Type of document |
| file_path | string | Path to file |
| file_name | string | Original file name |
| uploaded_by | bigint (FK) | User who uploaded the document |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

## Creating Tracking Records

### 1. From Web Interface (System Creation)

1. **Navigate to Sale Order Edit Page**
   - Go to Sales → Sale Orders → Edit any sale order
   - The shipment tracking section is visible at the bottom of the page

2. **Add New Tracking**
   - Click the "Add Tracking" button
   - Fill in the tracking details:
     - Carrier (optional)
     - Tracking Number (optional)
     - Tracking URL (optional)
     - Status (default: Pending)
     - Estimated Delivery Date (optional)
     - Notes (optional)
   - Click "Save" to create the tracking record

3. **Edit Existing Tracking**
   - Click the "Edit" button next to any tracking record
   - Modify the tracking details
   - Click "Save" to update the tracking record

4. **Delete Tracking**
   - Click the "Delete" button next to any tracking record
   - Confirm deletion when prompted

### 2. Adding Tracking Events

1. **Add Event to Tracking**
   - Click the "Add Event" button for any tracking record
   - Fill in the event details:
     - Event Date (auto-filled with current date/time)
     - Location (optional)
     - Status (optional)
     - Description (optional)
     - Latitude/Longitude (optional)
     - Proof Image (optional)
   - Click "Save" to add the event

### 3. Uploading Documents

1. **Upload Document**
   - Currently, document upload is handled through the API
   - Future enhancement: Add UI for document upload in the web interface

## Web Interface Implementation

### 1. Blade Template (resources/views/sale/order/edit.blade.php)

The shipment tracking section is included in the sale order edit page:

```blade
{{-- Shipment Tracking Section --}}
<div class="card-header px-4 py-3">
    <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ __('shipment.tracking') }}</h5>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-primary" id="addTrackingBtn">
                <i class="bx bx-plus me-1"></i>{{ __('shipment.add_tracking') }}
            </button>
        </div>
    </div>
</div>
<div class="card-body p-4 row g-3">
    <div class="col-md-12" id="trackingContent">
        @if($order->shipmentTrackings->count() > 0)
            @foreach($order->shipmentTrackings as $tracking)
                <div class="tracking-item mb-4 p-3 border rounded" data-tracking-id="{{ $tracking->id }}">
                    <!-- Tracking display content -->
                </div>
            @endforeach
        @else
            <div class="text-center py-4" id="noTrackingMessage">
                <!-- No tracking message -->
            </div>
        @endif
    </div>
</div>
```

### 2. Controller Modification (app/Http/Controllers/Sale/SaleOrderController.php)

The edit method was modified to include shipment tracking data:

```php
public function edit($id): View
{
    $order = SaleOrder::with(['party',
        'itemTransaction' => [
            'item',
            'tax',
            'batch.itemBatchMaster',
            'itemSerialTransaction.itemSerialMaster'
        ],
        'saleOrderStatusHistories' => [
            'changedBy'
        ],
        'shipmentTrackings' => [
            'carrier',
            'trackingEvents'
        ]])->findOrFail($id);
    // ... rest of the method
}
```

## API Endpoints

### 1. Create Tracking
**Endpoint**: `POST /api/sale-orders/{saleOrderId}/tracking`  
**Authentication**: Required (auth:sanctum)  
**Description**: Create a new shipment tracking for a sale order

**Request Body**:
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

**Response**:
```json
{
  "status": true,
  "message": "Shipment tracking created successfully",
  "data": {
    "id": 1,
    "sale_order_id": 1,
    "carrier_id": 1,
    "tracking_number": "TRK123456789",
    "tracking_url": "https://carrier.com/track/TRK123456789",
    "status": "In Transit",
    "estimated_delivery_date": "2025-10-15",
    "notes": "Fragile items",
    "created_at": "2025-09-26T10:00:00.000000Z",
    "updated_at": "2025-09-26T10:00:00.000000Z"
  }
}
```

### 2. Get Tracking by ID
**Endpoint**: `GET /api/shipment-tracking/{id}`  
**Authentication**: Required (auth:sanctum)  
**Description**: Retrieve a specific shipment tracking record

**Response**:
```json
{
  "status": true,
  "data": {
    "id": 1,
    "sale_order_id": 1,
    "carrier_id": 1,
    "tracking_number": "TRK123456789",
    "tracking_url": "https://carrier.com/track/TRK123456789",
    "status": "In Transit",
    "estimated_delivery_date": "2025-10-15",
    "actual_delivery_date": null,
    "notes": "Fragile items",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2025-09-26T10:00:00.000000Z",
    "updated_at": "2025-09-26T10:00:00.000000Z",
    "carrier": {
      "id": 1,
      "name": "DHL",
      // ... other carrier fields
    },
    "tracking_events": [
      {
        "id": 1,
        "shipment_tracking_id": 1,
        "event_date": "2025-09-26T10:30:00.000000Z",
        "location": "Warehouse",
        "status": "In Transit",
        "description": "Package picked up",
        // ... other event fields
      }
    ]
  }
}
```

### 3. Update Tracking
**Endpoint**: `PUT /api/shipment-tracking/{id}`  
**Authentication**: Required (auth:sanctum)  
**Description**: Update an existing shipment tracking record

**Request Body**:
```json
{
  "carrier_id": 1,
  "tracking_number": "TRK123456789",
  "tracking_url": "https://carrier.com/track/TRK123456789",
  "status": "Delivered",
  "estimated_delivery_date": "2025-10-15",
  "actual_delivery_date": "2025-10-14T14:30:00.000000Z",
  "notes": "Delivered to front desk"
}
```

### 4. Delete Tracking
**Endpoint**: `DELETE /api/shipment-tracking/{id}`  
**Authentication**: Required (auth:sanctum)  
**Description**: Delete a shipment tracking record and all related events and documents

### 5. Add Tracking Event
**Endpoint**: `POST /api/shipment-tracking/{trackingId}/events`  
**Authentication**: Required (auth:sanctum)  
**Description**: Add an event to a shipment tracking record

**Request Body**:
```json
{
  "event_date": "2025-09-26T14:30:00.000000Z",
  "location": "Distribution Center",
  "status": "In Transit",
  "description": "Package sorted for delivery",
  "latitude": 40.7128,
  "longitude": -74.0060
}
```

### 6. Get Tracking History
**Endpoint**: `GET /api/sale-orders/{saleOrderId}/tracking-history`  
**Authentication**: Required (auth:sanctum)  
**Description**: Get all tracking records for a sale order with related events

### 7. Get Tracking Statuses
**Endpoint**: `GET /api/tracking-statuses`  
**Authentication**: Required (auth:sanctum)  
**Description**: Get available tracking statuses

### 8. Get Document Types
**Endpoint**: `GET /api/tracking-document-types`  
**Authentication**: Required (auth:sanctum)  
**Description**: Get available document types

## JavaScript Integration

### 1. Main JavaScript File (public/custom/js/sale/shipment-tracking.js)

The JavaScript handles all client-side interactions:

```javascript
$(document).ready(function() {
    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Add tracking button click handler
    $('#addTrackingBtn').on('click', function() {
        // Reset form and show modal
    });

    // Edit tracking button click handler
    $(document).on('click', '.edit-tracking', function() {
        var trackingId = $(this).data('tracking-id');
        
        // Fetch tracking data via API
        $.ajax({
            url: '/api/shipment-tracking/' + trackingId,
            method: 'GET',
            success: function(response) {
                if (response.status) {
                    // Populate form with tracking data
                }
            }
        });
    });

    // Save tracking button click handler
    $('#saveTrackingBtn').on('click', function() {
        var trackingId = $('#trackingId').val();
        var formData = $('#trackingForm').serialize();
        var url = '/api/sale-orders/' + window.saleOrderId + '/tracking';
        var method = 'POST';

        if (trackingId) {
            url = '/api/shipment-tracking/' + trackingId;
            method = 'PUT';
        }

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.status) {
                    // Handle success
                }
            }
        });
    });
});
```

### 2. Key Features

1. **CSRF Protection**: All AJAX requests include CSRF token
2. **Dynamic Forms**: Forms are populated dynamically when editing
3. **Real-time Updates**: Page reloads after successful operations
4. **Error Handling**: Proper error messages for validation and server errors

## Testing the Process

### 1. Manual Testing Steps

1. **Create a Sale Order**
   - Navigate to Sales → Sale Orders → Create New
   - Fill in the required details and save

2. **Add Tracking Record**
   - Edit the created sale order
   - Click "Add Tracking" button
   - Fill in tracking details and save
   - Verify the tracking appears in the list

3. **Edit Tracking Record**
   - Click "Edit" button for the tracking
   - Modify details and save
   - Verify the changes are reflected

4. **Add Tracking Event**
   - Click "Add Event" button for the tracking
   - Fill in event details and save
   - Verify the event appears in the timeline

5. **Delete Tracking Record**
   - Click "Delete" button for the tracking
   - Confirm deletion
   - Verify the tracking is removed

### 2. API Testing

1. **Get Tracking by ID**
   ```bash
   curl -X GET "http://127.0.0.1:8000/api/shipment-tracking/1" \
        -H "Accept: application/json" \
        -H "Authorization: Bearer YOUR_API_TOKEN"
   ```

2. **Get Tracking History**
   ```bash
   curl -X GET "http://127.0.0.1:8000/api/sale-orders/1/tracking-history" \
        -H "Accept: application/json" \
        -H "Authorization: Bearer YOUR_API_TOKEN"
   ```

### 3. Common Issues and Solutions

1. **401 Unauthorized Error**
   - Ensure you're logged into the system
   - Check that Sanctum middleware is properly configured
   - Verify the `EnsureFrontendRequestsAreStateful` middleware is included in the API group

2. **CSRF Token Mismatch**
   - Ensure the meta tag is included in the layout
   - Verify `$.ajaxSetup()` is properly configured in JavaScript

3. **Route Not Found**
   - Check that all required routes are registered in `routes/api.php`
   - Run `php artisan route:clear` and `php artisan route:cache`

## Conclusion

The shipment tracking system provides a complete solution for managing shipment information for sale orders. Users can create and manage tracking records through the web interface, while developers can access the same data via RESTful API endpoints. The system supports multi-tracking per sale order, event logging, and document management.
