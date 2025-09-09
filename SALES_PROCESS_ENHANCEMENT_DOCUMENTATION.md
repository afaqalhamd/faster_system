# Enhanced Sales Process with POD Status and Inventory Management

## Overview

This document outlines the enhanced sales process that implements the following requirements:

1. **Inventory Reservation**: When creating a new sale, inventory is reserved but not deducted (status: Pending)
2. **Removed Payment-Based Inventory Deduction**: Inventory is no longer automatically deducted when payment is completed
3. **Status-Based Inventory Deduction**: Inventory is only deducted when sales status is changed to POD
4. **Proof Requirements**: POD, Cancelled, and Returned statuses require proof images and notes
5. **Status History Tracking**: All status changes are recorded with timestamps, notes, and proof images

## Implementation Details

### 1. Database Schema Changes

#### New Table: `sales_status_histories`
```sql
CREATE TABLE sales_status_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sale_id BIGINT UNSIGNED NOT NULL,
    previous_status VARCHAR(255) NULL,
    new_status VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    proof_image VARCHAR(255) NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (sale_id, changed_at)
);
```

### 2. New Models

#### SalesStatusHistory Model
- Tracks all status changes for sales
- Stores proof images and notes
- Maintains audit trail with user information

### 3. Enhanced Services

#### SalesStatusService
- Handles all status transitions with validation
- Manages inventory deduction/restoration based on status
- Processes proof image uploads
- Records status history

### 4. Updated Controllers

#### SaleController Enhancements
- Removed automatic inventory deduction on payment completion
- Added new endpoints for status management:
  - `POST /sale/invoice/update-sales-status/{id}` - Update status with proof
  - `GET /sale/invoice/get-sales-status-history/{id}` - Get status history
  - `GET /sale/invoice/get-sales-status-options` - Get available statuses

### 5. Status Transition Rules

```
Pending → [Processing, Completed, Delivery, Cancelled]
Processing → [Completed, Delivery, Cancelled]
Completed → [Delivery, POD, Cancelled, Returned]
Delivery → [POD, Cancelled, Returned]
POD → [Completed, Delivery, Cancelled, Returned]
Cancelled → [] (Terminal state)
Returned → [] (Terminal state)
```

### 6. Inventory Management Logic

#### Status-Based Inventory Impact

| Status | Inventory Action | Requirements |
|--------|------------------|--------------|
| Pending | Reserve items (SALE_ORDER unique_code) | None |
| Processing | Keep reserved | None |
| Completed | Keep reserved | None |
| Delivery | Keep reserved | None |
| **POD** | **Deduct inventory (SALE unique_code)** | **Notes + Proof Image** |
| Cancelled | Restore if deducted | Notes + Optional Proof Image |
| Returned | Restore if deducted | Notes + Optional Proof Image |

#### Inventory Status Flow
```
pending → deducted (when moving to POD)
deducted → restored (when moving to Cancelled/Returned)
deducted → deducted (when moving from POD to other status except Cancelled/Returned)
```

## Usage Instructions

### 1. Creating a Sale

```php
// Sales are created with inventory_status = 'pending'
// Items are reserved with unique_code = 'SALE_ORDER'
$sale = Sale::create([
    'party_id' => $partyId,
    'sales_status' => 'Pending',
    'inventory_status' => 'pending',
    // ... other fields
]);
```

### 2. Updating Sales Status

#### Via Service (Programmatic)
```php
$salesStatusService = app(SalesStatusService::class);

$result = $salesStatusService->updateSalesStatus($sale, 'POD', [
    'notes' => 'Delivery completed and confirmed by customer',
    'proof_image' => $uploadedFile, // UploadedFile instance
]);

if ($result['success']) {
    echo \"Status updated! Inventory updated: \" . ($result['inventory_updated'] ? 'Yes' : 'No');
}
```

#### Via HTTP API
```javascript
// Update to POD status with proof
const formData = new FormData();
formData.append('sales_status', 'POD');
formData.append('notes', 'Delivery completed and confirmed by customer');
formData.append('proof_image', imageFile);
formData.append('_token', csrfToken);

fetch(`/sale/invoice/update-sales-status/${saleId}`, {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.status) {
        console.log('Status updated successfully');
        if (data.inventory_updated) {
            console.log('Inventory was updated');
        }
    }
});
```

### 3. Viewing Status History

```javascript
fetch(`/sale/invoice/get-sales-status-history/${saleId}`)
.then(response => response.json())
.then(data => {
    data.data.forEach(history => {
        console.log(`${history.previous_status} → ${history.new_status}`);
        console.log(`Notes: ${history.notes}`);
        console.log(`Changed by: ${history.changed_by.name}`);
        console.log(`Date: ${history.changed_at}`);
        if (history.proof_image) {
            console.log(`Proof: /storage/${history.proof_image}`);
        }
    });
});
```

## Frontend Integration

### 1. Status Update Modal

The system includes a JavaScript module (`sales-status-manager.js`) that provides:
- Automatic modal display for statuses requiring proof
- Image upload with preview
- Form validation
- Success/error handling
- Real-time UI updates

### 2. Status History Timeline

- Visual timeline showing all status changes
- Proof image display with click-to-enlarge
- User information and timestamps
- Notes display

### 3. Integration Example

```html
<!-- Status update select -->
<select class=\"sales-status-select\" data-sale-id=\"{{ $sale->id }}\">
    <option value=\"\">Select Status</option>
    @foreach($statusOptions as $status)
        <option value=\"{{ $status['id'] }}\">{{ $status['name'] }}</option>
    @endforeach
</select>

<!-- Status history button -->
<button class=\"btn btn-info view-status-history\" data-sale-id=\"{{ $sale->id }}\">
    View Status History
</button>

<!-- Include the JavaScript module -->
<script src=\"{{ asset('js/sales-status-manager.js') }}\"></script>
```

## Testing

### Running Tests

```bash
# Run the sales status management tests
php artisan test --filter=SalesStatusManagementTest

# Run all tests
php artisan test
```

### Test Coverage

The test suite covers:
- Status transitions with inventory impact
- Proof requirement validation
- Status history tracking
- Invalid transition prevention
- API endpoint functionality
- Image upload handling

## Security Considerations

### 1. Image Upload Security
- File type validation (images only)
- File size limits (2MB max)
- Secure storage in `storage/app/public/sales/status_proofs/`
- Proper file naming with timestamps

### 2. Access Control
- Status updates require `sale.invoice.edit` permission
- Status history viewing requires `sale.invoice.view` permission
- Manual inventory deduction requires `sale.invoice.manual.inventory.deduction` permission

### 3. Data Validation
- Status transition validation
- Required fields for proof statuses
- File upload validation
- CSRF protection

## Troubleshooting

### Common Issues

1. **\"Invalid status transition\" Error**
   - Check the status transition rules
   - Ensure the sale is not in a terminal state (Cancelled/Returned)

2. **\"Validation failed\" for POD Status**
   - Ensure both notes and proof image are provided
   - Check image file size (max 2MB)
   - Verify image file type (JPG, PNG, GIF)

3. **Inventory Not Updating**
   - Verify the sale has item transactions
   - Check ItemTransactionService methods are available
   - Ensure proper tracking type handling

4. **Status History Not Showing**
   - Check database permissions
   - Verify SalesStatusHistory model relationships
   - Ensure proper route permissions

### Debug Commands

```bash
# Check route registration
php artisan route:list --name=\"sale.invoice\"

# Check database tables
php artisan migrate:status

# Clear caches if needed
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Migration Guide

### From Old System to New System

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Update Existing Sales**
   ```php
   // Update existing sales to have proper inventory status
   Sale::whereNull('inventory_status')->update(['inventory_status' => 'pending']);
   ```

3. **Remove Old Payment-Based Logic**
   - The old automatic inventory deduction on payment completion has been removed
   - Manual inventory deduction is still available for admin users

4. **Update Frontend Code**
   - Replace old status update forms with new status management system
   - Include the new JavaScript module
   - Update permissions as needed

## Future Enhancements

### Potential Improvements

1. **Bulk Status Updates**
   - Allow updating multiple sales at once
   - Batch processing for large operations

2. **Status Notifications**
   - Email/SMS notifications on status changes
   - Customer portal integration

3. **Advanced Reporting**
   - Status change analytics
   - Inventory impact reports
   - Performance metrics

4. **Mobile App Support**
   - API endpoints for mobile applications
   - Offline status updates with sync

## Conclusion

This enhanced sales process provides:
- ✅ Proper inventory reservation and deduction flow
- ✅ Status-based inventory management
- ✅ Comprehensive audit trail
- ✅ Proof requirement handling
- ✅ Secure image storage
- ✅ Robust validation and error handling
- ✅ Complete test coverage
- ✅ Easy frontend integration

The system is designed to be maintainable, secure, and user-friendly while providing complete traceability of all sales process changes.
