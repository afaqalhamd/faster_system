# Step-by-Step Guide: Creating Shipment Tracking

This guide provides practical, step-by-step instructions for creating shipment tracking records in the system.

## Prerequisites

1. Ensure you have a working installation of the system
2. Make sure you're logged in with appropriate permissions
3. Verify that the database migrations have been run

## Step 1: Access the Sale Order Edit Page

1. Navigate to **Sales** → **Sale Orders**
2. Find an existing sale order or create a new one
3. Click the **Edit** button for the sale order

## Step 2: Locate the Shipment Tracking Section

1. Scroll down to the bottom of the edit page
2. You'll see a section titled **"Tracking"**
3. If there are existing tracking records, they will be displayed here
4. If no tracking records exist, you'll see a message indicating this

## Step 3: Create a New Tracking Record

1. Click the **"Add Tracking"** button (blue button on the right)
2. A modal dialog will appear with the following fields:
   - **Carrier**: Select a carrier from the dropdown (optional)
   - **Tracking Number**: Enter the carrier's tracking number (optional)
   - **Tracking URL**: Enter the URL to track the shipment online (optional)
   - **Status**: Select the current status (default: Pending)
   - **Estimated Delivery Date**: Enter the expected delivery date (optional)
   - **Note**: Add any additional notes (optional)

3. Fill in the required information:
   ```
   Carrier: DHL
   Tracking Number: DHL1234567890
   Tracking URL: https://www.dhl.com/en/express/tracking.html?AWB=1234567890
   Status: In Transit
   Estimated Delivery Date: 2025-10-15
   Note: Fragile items - handle with care
   ```

4. Click the **"Save"** button

## Step 4: Verify the Tracking Record

1. The modal will close and the page will reload
2. You should now see the new tracking record in the list
3. The tracking record will display:
   - Carrier name
   - Tracking number
   - Current status
   - Estimated delivery date

## Step 5: Add Events to the Tracking Record

1. Find the tracking record you just created
2. Click the **"Add Event"** button below the tracking details
3. A modal dialog will appear with the following fields:
   - **Event Date**: Auto-filled with current date/time (can be modified)
   - **Location**: Enter the location where the event occurred (optional)
   - **Status**: Select the status at the time of the event (optional)
   - **Description**: Describe what happened (optional)
   - **Latitude**: GPS coordinates (optional)
   - **Longitude**: GPS coordinates (optional)
   - **Proof Image**: Upload an image as proof (optional)

4. Fill in the event information:
   ```
   Event Date: 2025-09-26 14:30
   Location: New York Distribution Center
   Status: In Transit
   Description: Package sorted for delivery to destination
   Latitude: 40.7128
   Longitude: -74.0060
   ```

5. Click the **"Save"** button

## Step 6: Verify the Tracking Event

1. The modal will close and the page will reload
2. Expand the tracking record to see the timeline of events
3. You should see the new event in the timeline with:
   - Location
   - Date and time
   - Description
   - Status

## Step 7: Edit an Existing Tracking Record

1. Find the tracking record you want to edit
2. Click the **"Edit"** button (pencil icon)
3. The "Add Tracking" modal will appear with the existing data pre-filled
4. Make your changes:
   ```
   Status: Out for Delivery
   Note: Package loaded on delivery truck - ETA 4:00 PM
   ```

5. Click the **"Save"** button

## Step 8: View Tracking Data via API

To retrieve tracking data via API, you can use the following endpoints:

### Get a Specific Tracking Record
```bash
GET /api/shipment-tracking/{id}
```

Example using curl:
```bash
curl -X GET "http://your-domain.com/api/shipment-tracking/1" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Get All Tracking Records for a Sale Order
```bash
GET /api/sale-orders/{saleOrderId}/tracking-history
```

Example using curl:
```bash
curl -X GET "http://your-domain.com/api/sale-orders/1/tracking-history" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Step 9: Using the API Response Data

The API responses will include all relevant tracking information:

### Single Tracking Record Response
```json
{
  "status": true,
  "data": {
    "id": 1,
    "sale_order_id": 1,
    "carrier_id": 1,
    "tracking_number": "DHL1234567890",
    "tracking_url": "https://www.dhl.com/en/express/tracking.html?AWB=1234567890",
    "status": "Out for Delivery",
    "estimated_delivery_date": "2025-10-15",
    "actual_delivery_date": null,
    "notes": "Package loaded on delivery truck - ETA 4:00 PM",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2025-09-26T10:00:00.000000Z",
    "updated_at": "2025-09-26T15:00:00.000000Z",
    "carrier": {
      "id": 1,
      "name": "DHL",
      "email": "info@dhl.com",
      // ... other carrier fields
    },
    "tracking_events": [
      {
        "id": 1,
        "shipment_tracking_id": 1,
        "event_date": "2025-09-26T14:30:00.000000Z",
        "location": "New York Distribution Center",
        "status": "In Transit",
        "description": "Package sorted for delivery to destination",
        "latitude": "40.71280000",
        "longitude": "-74.00600000",
        // ... other event fields
      }
    ]
  }
}
```

## Step 10: Handling Multiple Trackings per Sale Order

The system supports multiple tracking records per sale order:

1. Click **"Add Tracking"** multiple times to create several tracking records
2. Each tracking can have different carriers, tracking numbers, and statuses
3. All trackings will be displayed in the tracking section
4. Each tracking can have its own events and documents

Example scenario:
- Tracking #1: DHL for main package
- Tracking #2: UPS for accessory items
- Tracking #3: Local courier for special delivery

## Common Issues and Troubleshooting

### Issue 1: "Failed to save tracking" Error
**Solution**: 
1. Check browser console for JavaScript errors
2. Verify you're logged in and have proper permissions
3. Ensure all required fields are filled correctly

### Issue 2: 401 Unauthorized Error When Using API
**Solution**:
1. Ensure you're including the Authorization header with a valid token
2. Check that Sanctum is properly configured
3. Verify the user has appropriate permissions

### Issue 3: Tracking Not Appearing After Creation
**Solution**:
1. Refresh the page
2. Check browser console for errors
3. Verify the tracking was actually saved in the database

## Best Practices

1. **Always include tracking numbers** when available from carriers
2. **Update status regularly** to keep customers informed
3. **Add events for major milestones** in the shipping process
4. **Use descriptive notes** to provide additional context
5. **Include GPS coordinates** when available for better tracking
6. **Upload proof images** for important events like delivery

## Conclusion

This step-by-step guide covers the complete process of creating and managing shipment tracking records in the system. By following these steps, you can effectively track shipments from creation through delivery, providing valuable information to both internal teams and customers.
