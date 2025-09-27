# Technical Documentation: Shipping Waybill Integration

## System Architecture

The waybill integration follows the existing Laravel MVC pattern with the following components:

### Models
- `App\Models\Sale\ShipmentTracking` - Extended with waybill fields

### Services
- `App\Services\ShipmentTrackingService` - Extended with waybill validation
- `App\Services\WaybillValidationService` - New service for waybill validation

### Controllers
- `App\Http\Controllers\Api\ShipmentTrackingController` - Extended with waybill API endpoints

### Database
- Migration: `2025_09_26_000003_add_waybill_fields_to_shipment_trackings_table.php`

## Data Model Changes

### ShipmentTracking Model Extension

The `ShipmentTracking` model has been extended with the following fields:

```php
protected $fillable = [
    // ... existing fields ...
    'waybill_number',
    'waybill_type',
    'waybill_data',
    'waybill_validated',
];

protected $casts = [
    // ... existing casts ...
    'waybill_data' => 'array',
    'waybill_validated' => 'boolean',
];
```

## Waybill Validation Service

### Supported Carrier Formats

The `WaybillValidationService` supports validation for the following carrier formats:

1. **DHL**: `GM` + 10 digits (e.g., GM1234567890)
2. **FedEx**: 12 or 15 digits (e.g., 123456789012 or 123456789012345)
3. **UPS**: `1Z` + 18 alphanumeric characters (e.g., 1Z1234567890123456)
4. **USPS**: 20 digits (e.g., 12345678901234567890)
5. **Generic Formats**:
   - Alphanumeric: 10-20 characters
   - Numeric: 12-18 digits

### Service Methods

```php
// Validate waybill format based on carrier
public function validateWaybillFormat(string $waybillNumber, ?string $carrier = null): bool

// Get validation rules
public function getWaybillRules(): array

// Validate waybill data
public function validateWaybillData(array $data): void

// Validate barcode format
public function validateWaybillBarcode(string $waybillNumber): bool
```

## API Endpoints

### Validate Waybill Number
```
POST /api/waybill/validate
```
**Parameters:**
- `waybill_number` (required): The waybill number to validate
- `carrier` (optional): The carrier name for carrier-specific validation

**Response:**
```json
{
  "status": true,
  "valid": true,
  "message": "Waybill number is valid"
}
```

### Validate Waybill Barcode Format
```
POST /api/waybill/validate-barcode
```
**Parameters:**
- `waybill_number` (required): The waybill number to validate

**Response:**
```json
{
  "status": true,
  "valid": true,
  "message": "Waybill barcode format is valid"
}
```

### Get Waybill Validation Rules
```
GET /api/waybill/rules
```
**Response:**
```json
{
  "status": true,
  "data": {
    "waybill_number": "nullable|string|max:255|unique:shipment_trackings,waybill_number",
    "waybill_type": "nullable|string|in:AirwayBill,BillOfLading,CourierWaybill,Other"
  }
}
```

## Integration with Existing Workflows

### Creating Shipment Tracking with Waybill

When creating a shipment tracking record with a waybill number:

1. The system validates the waybill data using `WaybillValidationService`
2. The waybill barcode format is validated
3. If valid, the `waybill_validated` flag is set to `true`
4. The shipment tracking record is created with waybill information

### Updating Shipment Tracking with Waybill

When updating a shipment tracking record with a waybill number:

1. The system validates the waybill data using `WaybillValidationService`
2. The waybill barcode format is validated
3. If valid, the `waybill_validated` flag is set to `true`
4. The shipment tracking record is updated with waybill information

## Database Schema

### Added Columns to `shipment_trackings` Table

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| waybill_number | string | YES | NULL | The waybill number |
| waybill_type | string | YES | NULL | Type of waybill (AirwayBill, BillOfLading, etc.) |
| waybill_data | json | YES | NULL | Additional waybill data in JSON format |
| waybill_validated | boolean | NO | false | Whether the waybill has been validated |

### Indexes

A new index has been added on the `waybill_number` column for improved query performance.

## Error Handling

The waybill validation follows the existing error handling patterns in the system:

1. Validation errors return HTTP 422 with detailed error messages
2. System errors return HTTP 500 with error descriptions
3. All validation is performed before database operations

## Testing Considerations

### Unit Tests to Implement

1. Waybill format validation for each supported carrier
2. Barcode format validation
3. Integration tests for shipment tracking creation with waybills
4. Integration tests for shipment tracking updates with waybills
5. API endpoint tests for new waybill validation endpoints

### Test Data Examples

- DHL: GM1234567890
- FedEx (12 digits): 123456789012
- FedEx (15 digits): 123456789012345
- UPS: 1Z123456789012345678
- USPS: 12345678901234567890
- Generic alphanumeric: ABC123456789
- Generic numeric: 123456789012345

## Future Enhancements

1. **Carrier Detection**: Automatic detection of carrier based on waybill number format
2. **Advanced Validation**: Integration with carrier APIs for real-time waybill validation
3. **Waybill Tracking**: Direct integration with carrier tracking systems
4. **Document Generation**: Automatic generation of waybill documents
5. **Barcode Generation**: Generation of barcode images for waybill numbers
