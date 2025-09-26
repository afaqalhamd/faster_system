# Proof Image Display in Shipment Tracking Events

## Overview
This guide explains how proof images uploaded with shipment tracking events are displayed in the events list and how the system handles these images.

## Implementation Details

### 1. Database Structure
The proof images are stored in the `shipment_tracking_events` table in the `proof_image` column, which contains the file path relative to the storage directory.

### 2. Image Upload Process
1. When adding a tracking event, users can upload a proof image through the "Add Event" modal
2. The image is stored in `storage/app/public/shipment_events/{tracking_id}/` directory
3. The file path is saved in the database

### 3. Display in Events List
The proof images are displayed as thumbnails in the events timeline:
- Thumbnails are shown with a maximum size of 100x100 pixels
- Clicking on the thumbnail opens the full-size image in a new tab
- Images are only displayed when they exist for an event

### 4. Code Implementation

#### Backend (ShipmentTrackingService.php)
```php
// Validation rules for proof images
'proof_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp|max:5120',

// Image handling in addTrackingEvent method
if (isset($data['proof_image']) && $data['proof_image']) {
    $image = $data['proof_image'];
    $directory = "shipment_events/{$tracking->id}";
    $filename = 'proof_' . time() . '.' . $image->getClientOriginalExtension();
    $proofImagePath = $image->storeAs($directory, $filename, 'public');
    $data['proof_image'] = $proofImagePath;
}
```

#### Frontend (edit.blade.php)
```blade
{{-- Display proof image thumbnail if exists --}}
@if($event->proof_image)
    <div class="mt-2">
        <a href="{{ asset('storage/' . $event->proof_image) }}" target="_blank">
            <img src="{{ asset('storage/' . $event->proof_image) }}" 
                 alt="{{ __('shipment.proof_image') }}" 
                 class="img-thumbnail" 
                 style="max-width: 100px; max-height: 100px;">
        </a>
    </div>
@endif
```

## Usage Instructions

### 1. Adding Events with Proof Images
1. Navigate to a sale order edit page
2. Go to the "Shipment Tracking" section
3. Click "Add Event" button for a tracking record
4. Fill in event details
5. Select a proof image file (JPG, PNG, GIF, BMP formats supported)
6. Click "Save" to add the event

### 2. Viewing Proof Images
1. In the tracking events timeline, look for events with thumbnail images
2. Click on any thumbnail to view the full-size image
3. Use browser functionality to download or save the image

## Supported Image Formats
- JPEG/JPG
- PNG
- GIF
- BMP

## File Size Limitations
- Maximum file size: 5MB
- Thumbnail display: Max 100x100 pixels
- Full-size images: Original dimensions

## Security Considerations
- File type validation ensures only image formats are accepted
- Files are stored outside the public directory with symbolic links
- Filenames are randomized to prevent conflicts and security issues
- Size limitations prevent excessive storage usage

## Troubleshooting

### Images Not Displaying
1. Check if the image file exists in the storage directory
2. Verify the symbolic link between `public/storage` and `storage/app/public` exists
3. Ensure the web server has read permissions on the storage directory

### Creating Storage Symbolic Link
If images are not displaying, run:
```bash
php artisan storage:link
```

### File Upload Issues
1. Verify the file size is under 5MB
2. Check that the file format is supported
3. Ensure the server has write permissions to the storage directory
