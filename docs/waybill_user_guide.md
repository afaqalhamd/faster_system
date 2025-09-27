# Waybill Integration User Guide

## Introduction

The waybill integration feature allows you to track shipments using carrier-specific waybill numbers. This guide will help you understand how to use the waybill functionality in the Faster System.

## What is a Waybill?

A waybill is a document issued by a carrier that provides details and instructions relating to the shipment of goods. It typically includes:
- A unique waybill number for tracking
- Carrier information
- Origin and destination details
- Package contents and weight
- Special handling instructions

## Supported Carriers

The system currently supports waybill validation for the following carriers:

1. **DHL** - Format: GM + 10 digits (e.g., GM1234567890)
2. **FedEx** - Format: 12 or 15 digits (e.g., 123456789012 or 123456789012345)
3. **UPS** - Format: 1Z + 18 alphanumeric characters (e.g., 1Z123456789012345678)
4. **USPS** - Format: 20 digits (e.g., 12345678901234567890)

## Adding Waybill Information to Shipments

### Creating a New Shipment Tracking

1. Navigate to the Sales Orders section
2. Select the order you want to track
3. Click on "Add Tracking" or "Manage Tracking"
4. In the tracking form, you'll see a "Waybill Number" field
5. Enter the waybill number provided by the carrier
6. Select the appropriate "Waybill Type" from the dropdown:
   - Airway Bill
   - Bill of Lading
   - Courier Waybill
   - Other
7. The system will automatically validate the waybill format
8. If valid, you'll see a green checkmark; if invalid, you'll see a red X
9. Complete the rest of the tracking information
10. Click "Save" to create the tracking record

### Updating Existing Shipment Tracking

1. Navigate to the Sales Orders section
2. Select the order with existing tracking
3. Click on "Edit Tracking" for the shipment you want to update
4. Enter or modify the waybill information
5. The system will validate the waybill format in real-time
6. Save your changes

## Waybill Validation

The system provides two levels of waybill validation:

### 1. Format Validation
- Checks if the waybill number follows the expected format for the selected carrier
- Provides immediate feedback as you type
- Displays visual indicators (green checkmark for valid, red X for invalid)

### 2. Barcode Validation
- Ensures the waybill number can be processed as a barcode
- Validates against common barcode patterns
- Confirms the waybill number is suitable for scanning

## Viewing Waybill Information

### In Shipment Tracking List
- Waybill numbers are displayed in the tracking list
- Validated waybills are marked with a checkmark icon
- You can sort and filter by waybill numbers

### In Shipment Details
- Detailed waybill information is shown in the shipment details view
- Includes waybill type and validation status
- Shows any additional waybill data if available

## Searching by Waybill Number

You can search for shipments using waybill numbers:

1. Use the global search function and enter the waybill number
2. Use the advanced search in the Sales Orders section
3. Filter shipments by waybill number in the tracking list

## Barcode Scanning

The system supports barcode scanning for waybill numbers:

1. Click the barcode scanner icon next to the waybill input field
2. Use your device's camera or barcode scanner to scan the waybill barcode
3. The system will automatically populate the waybill number field
4. The waybill will be validated automatically after scanning

## Troubleshooting

### Common Issues

**Invalid Waybill Format**
- Ensure you've entered the correct waybill number
- Check that you've selected the correct carrier
- Verify the waybill number with the carrier if problems persist

**Waybill Not Found in Search**
- Double-check the waybill number for typos
- Ensure the waybill has been added to a shipment tracking record
- Try searching with partial waybill numbers

**Barcode Scanning Issues**
- Ensure the barcode is clean and undamaged
- Check lighting conditions when scanning
- Make sure your device's camera is clean and functional

### Contact Support

If you continue to experience issues with waybill integration:
1. Note the exact error message or problem
2. Take a screenshot if possible
3. Contact your system administrator or support team
4. Provide the waybill number and carrier information

## Best Practices

1. **Always validate waybills** before saving tracking information
2. **Use barcode scanning** when possible to reduce data entry errors
3. **Keep waybill records** for your shipping documentation
4. **Update waybill information** promptly when it changes
5. **Verify waybill numbers** with carriers if you're unsure of the format

## FAQ

**Q: Can I use waybills with any carrier?**
A: The system supports validation for major carriers (DHL, FedEx, UPS, USPS) and generic formats for other carriers.

**Q: What happens if I enter an invalid waybill number?**
A: The system will show an error message and prevent saving until a valid format is entered.

**Q: Can I edit a waybill number after saving?**
A: Yes, you can edit waybill information at any time through the edit tracking function.

**Q: Is waybill information secure?**
A: Yes, waybill information is stored securely and follows the same security protocols as other shipment data.

**Q: Can I import waybill numbers in bulk?**
A: Bulk import functionality is available through the data import feature (contact your administrator for details).
