# Shipment Tracking Feature Documentation

## Overview
The Shipment Tracking feature provides multi-tracking capability for sale orders, allowing users to create and manage multiple shipment tracking records for each sale order. This feature enhances the existing delivery system by providing detailed tracking information, events, and document management.

## Key Features
1. **Multi-Tracking Support**: Create multiple tracking records per sale order
2. **Tracking Events**: Record detailed events for each tracking record
3. **Document Management**: Upload and manage documents related to shipments
4. **Carrier Integration**: Associate trackings with carriers
5. **Status Management**: Track shipment status through its lifecycle
6. **Geolocation Support**: Record GPS coordinates for tracking events

## Database Schema

### 1. shipment_trackings Table
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

### 2. shipment_tracking_events Table
Stores detailed events for each tracking record.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Unique identifier |
| shipment_tracking_id | bigint (FK) | Reference to shipment_trackings table |
| event_date | datetime | Date and time of the event |
| location | string | Location where event occurred |
| status | string | Status at time of event |
| description | text | Description of the event |
| proof_image | string | Path to proof image |
| signature | text | Signature data |
| latitude | decimal | GPS latitude coordinate |
| longitude | decimal | GPS longitude coordinate |
| created_by | bigint (FK) | User who created the event |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### 3. shipment_documents Table
Stores documents related to shipment trackings.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Unique identifier |
| shipment_tracking_id | bigint (FK) | Reference to shipment_trackings table |
| document_type | string | Type of document |
| file_path | string | Path to uploaded file |
| file_name | string | Original file name |
| uploaded_by | bigint (FK) | User who uploaded the document |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

## API Endpoints

### 1. Create Tracking
**Endpoint**: `POST /api/sale-orders/{saleOrderId}/tracking`  
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

### 2. Update Tracking
**Endpoint**: `PUT /api/shipment-tracking/{id}`  
**Description**: Update an existing shipment tracking  
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

### 3. Delete Tracking
**Endpoint**: `DELETE /api/shipment-tracking/{id}`  
**Description**: Delete a shipment tracking and all related events and documents

### 4. Add Tracking Event
**Endpoint**: `POST /api/shipment-tracking/{trackingId}/events`  
**Description**: Add a new event to a shipment tracking  
**Request Body**:
```json
{
  "event_date": "2025-10-14T14:30:00.000000Z",
  "location": "Main Warehouse",
  "status": "Delivered",
  "description": "Package delivered to customer",
  "latitude": 40.7128,
  "longitude": -74.0060
}
```

### 5. Upload Document
**Endpoint**: `POST /api/shipment-tracking/{trackingId}/documents`  
**Description**: Upload a document for a shipment tracking  
**Request Body** (multipart/form-data):
```
document_type: "Delivery Receipt"
file: [file data]
notes: "Customer signature included"
```

### 6. Get Tracking History
**Endpoint**: `GET /api/sale-orders/{saleOrderId}/tracking-history`  
**Description**: Get all tracking records for a sale order

### 7. Get Statuses
**Endpoint**: `GET /api/tracking-statuses`  
**Description**: Get available tracking statuses

### 8. Get Document Types
**Endpoint**: `GET /api/tracking-document-types`  
**Description**: Get available document types

## Models

### ShipmentTracking
**Namespace**: `App\Models\Sale\ShipmentTracking`  
**Relationships**:
- `saleOrder()` - BelongsTo SaleOrder
- `carrier()` - BelongsTo Carrier
- `createdBy()` - BelongsTo User
- `updatedBy()` - BelongsTo User
- `trackingEvents()` - HasMany ShipmentTrackingEvent
- `documents()` - HasMany ShipmentDocument

### ShipmentTrackingEvent
**Namespace**: `App\Models\Sale\ShipmentTrackingEvent`  
**Relationships**:
- `shipmentTracking()` - BelongsTo ShipmentTracking
- `createdBy()` - BelongsTo User

### ShipmentDocument
**Namespace**: `App\Models\Sale\ShipmentDocument`  
**Relationships**:
- `shipmentTracking()` - BelongsTo ShipmentTracking
- `uploadedBy()` - BelongsTo User

## Service Class

### ShipmentTrackingService
**Namespace**: `App\Services\ShipmentTrackingService`  
**Methods**:
- `createTracking(array $data)` - Create a new tracking record
- `updateTracking(ShipmentTracking $tracking, array $data)` - Update a tracking record
- `deleteTracking(ShipmentTracking $tracking)` - Delete a tracking record
- `addTrackingEvent(ShipmentTracking $tracking, array $data)` - Add an event to a tracking
- `uploadDocument(ShipmentTracking $tracking, array $data)` - Upload a document for a tracking
- `getTrackingHistory(int $saleOrderId)` - Get tracking history for a sale order
- `getTrackingStatuses()` - Get available tracking statuses
- `getDocumentTypes()` - Get available document types

## UI Components

### Sale Order Edit Page
The shipment tracking section is added to the sale order edit page with the following features:
1. **Tracking List**: Displays all tracking records for the sale order
2. **Add Tracking Button**: Opens modal to create new tracking
3. **Edit Tracking**: Opens modal to edit existing tracking
4. **Delete Tracking**: Removes tracking record and all related data
5. **Tracking Events**: Displays events for each tracking record
6. **Add Event Button**: Opens modal to add new event to tracking

### Modals
1. **Add/Edit Tracking Modal**: Form for creating/updating tracking records
2. **Add Event Modal**: Form for adding events to tracking records

## JavaScript Functionality

### shipment-tracking.js
Handles all client-side functionality for the tracking feature:
- AJAX calls to API endpoints
- Form validation and submission
- Modal management
- UI updates and refreshes
- Error handling and user notifications

## Language Support

### English (en/shipment.php)
Contains all English translations for the tracking feature.

### Arabic (ar/shipment.php)
Contains all Arabic translations for the tracking feature.

## Implementation Steps

1. **Database Migrations**: Run the three new migration files to create tables
2. **Models**: Implement the three new Eloquent models
3. **Service Class**: Implement the ShipmentTrackingService class
4. **Controller**: Implement the ShipmentTrackingController class
5. **API Routes**: Register the new API endpoints
6. **UI Updates**: Update the sale order edit page with tracking section
7. **JavaScript**: Implement client-side functionality
8. **Translations**: Add language files for English and Arabic
9. **Testing**: Execute the testing plan
10. **Documentation**: Review and update this documentation

## Usage Examples

### Creating a New Tracking Record
1. Navigate to a sale order edit page
2. Click "Add Tracking" button
3. Fill in tracking details in the modal
4. Click "Save" to create the tracking record
5. The new tracking will appear in the tracking list

### Adding a Tracking Event
1. In the tracking list, find the tracking record
2. Click "Add Event" button for that tracking
3. Fill in event details in the modal
4. Click "Save" to add the event
5. The new event will appear in the tracking events list

### Uploading a Document
1. Use the API endpoint to upload a document for a tracking record
2. The document will be stored and associated with the tracking

## Error Handling

The system implements comprehensive error handling:
- **Validation Errors**: All API endpoints validate input and return detailed error messages
- **Database Errors**: Database operations are wrapped in transactions and properly handled
- **File Upload Errors**: File uploads are validated for size and type
- **Authorization Errors**: All endpoints require authentication
- **NotFound Errors**: Proper 404 responses for missing records

## Security Considerations

- All API endpoints require authentication
- File uploads are sanitized and stored securely
- SQL injection protection through Eloquent ORM
- XSS protection through proper output escaping
- User permissions are checked where appropriate

## Performance Considerations

- Database indexes on frequently queried columns
- Eager loading of relationships to minimize queries
- Pagination for large datasets (where applicable)
- Efficient file storage and retrieval

## Future Enhancements

1. **Real-time Tracking Updates**: Implement WebSocket support for real-time tracking updates
2. **Notification System**: Send notifications for tracking events
3. **Integration with Carrier APIs**: Direct integration with carrier tracking APIs
4. **Advanced Reporting**: Detailed tracking reports and analytics
5. **Mobile App Support**: Extend tracking functionality to mobile applications
6. **Barcode Scanning**: Integration with barcode scanning for tracking numbers
