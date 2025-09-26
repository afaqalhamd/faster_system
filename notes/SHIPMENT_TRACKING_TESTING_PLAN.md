# Shipment Tracking Feature - Testing Plan

## Overview
This document outlines the testing plan for the new multi-tracking capability for sale orders. The feature allows users to create and manage multiple shipment tracking records for each sale order, including tracking events and documents.

## Test Environment
- PHP 8.1+
- Laravel 9.x
- MySQL 5.7+
- Web browser (Chrome, Firefox, Safari, Edge)

## Test Cases

### 1. Database Migration Tests
- [ ] Verify that all three new tables are created correctly:
  - shipment_trackings
  - shipment_tracking_events
  - shipment_documents
- [ ] Verify foreign key constraints are properly set
- [ ] Verify indexes are created for performance optimization

### 2. Model Relationship Tests
- [ ] Verify SaleOrder model has correct relationship with ShipmentTracking
- [ ] Verify ShipmentTracking model has correct relationships with:
  - SaleOrder (belongsTo)
  - Carrier (belongsTo)
  - ShipmentTrackingEvent (hasMany)
  - ShipmentDocument (hasMany)
  - User (createdBy, updatedBy)
- [ ] Verify ShipmentTrackingEvent model has correct relationships
- [ ] Verify ShipmentDocument model has correct relationships

### 3. API Endpoint Tests

#### 3.1 Create Tracking (/api/sale-orders/{saleOrderId}/tracking)
- [ ] Test with valid data - should return 201 Created
- [ ] Test with invalid sale order ID - should return 404 Not Found
- [ ] Test with invalid carrier ID - should return 422 Validation Error
- [ ] Test with invalid status - should return 422 Validation Error
- [ ] Test with invalid dates - should return 422 Validation Error
- [ ] Test with missing required fields - should return 422 Validation Error

#### 3.2 Update Tracking (/api/shipment-tracking/{id})
- [ ] Test with valid data - should return 200 OK
- [ ] Test with invalid tracking ID - should return 404 Not Found
- [ ] Test with invalid carrier ID - should return 422 Validation Error
- [ ] Test with invalid status - should return 422 Validation Error
- [ ] Test with invalid dates - should return 422 Validation Error

#### 3.3 Delete Tracking (/api/shipment-tracking/{id})
- [ ] Test with valid tracking ID - should return 200 OK
- [ ] Test with invalid tracking ID - should return 404 Not Found
- [ ] Verify related events and documents are also deleted

#### 3.4 Add Tracking Event (/api/shipment-tracking/{trackingId}/events)
- [ ] Test with valid data - should return 200 OK
- [ ] Test with invalid tracking ID - should return 404 Not Found
- [ ] Test with invalid status - should return 422 Validation Error
- [ ] Test with invalid coordinates - should return 422 Validation Error
- [ ] Test with proof image upload - should store image correctly

#### 3.5 Upload Document (/api/shipment-tracking/{trackingId}/documents)
- [ ] Test with valid data - should return 200 OK
- [ ] Test with invalid tracking ID - should return 404 Not Found
- [ ] Test with invalid document type - should return 422 Validation Error
- [ ] Test with file upload - should store file correctly
- [ ] Test with large file (>5MB) - should return 422 Validation Error

#### 3.6 Get Tracking History (/api/sale-orders/{saleOrderId}/tracking-history)
- [ ] Test with valid sale order ID - should return 200 OK with tracking data
- [ ] Test with invalid sale order ID - should return 404 Not Found
- [ ] Test with sale order without tracking - should return empty array

#### 3.7 Get Statuses (/api/tracking-statuses)
- [ ] Test endpoint - should return 200 OK with status list

#### 3.8 Get Document Types (/api/tracking-document-types)
- [ ] Test endpoint - should return 200 OK with document type list

### 4. UI Tests

#### 4.1 Sale Order Edit Page
- [ ] Verify tracking section is visible
- [ ] Verify "Add Tracking" button is functional
- [ ] Verify existing tracking items are displayed correctly
- [ ] Verify tracking events are displayed correctly
- [ ] Verify "Add Event" buttons are functional
- [ ] Verify "Edit" and "Delete" buttons are functional

#### 4.2 Add Tracking Modal
- [ ] Verify modal opens when "Add Tracking" button is clicked
- [ ] Verify form fields are correctly populated
- [ ] Verify form validation works correctly
- [ ] Verify "Save" button creates new tracking record
- [ ] Verify "Close" button closes the modal

#### 4.3 Edit Tracking Modal
- [ ] Verify modal opens when "Edit" button is clicked
- [ ] Verify form fields are correctly populated with existing data
- [ ] Verify form validation works correctly
- [ ] Verify "Save" button updates tracking record
- [ ] Verify "Close" button closes the modal

#### 4.4 Add Event Modal
- [ ] Verify modal opens when "Add Event" button is clicked
- [ ] Verify form fields are correctly populated
- [ ] Verify form validation works correctly
- [ ] Verify "Save" button creates new event record
- [ ] Verify "Close" button closes the modal

#### 4.5 Delete Tracking
- [ ] Verify confirmation dialog appears when "Delete" button is clicked
- [ ] Verify tracking record is deleted when confirmed
- [ ] Verify operation is cancelled when not confirmed

### 5. Business Logic Tests

#### 5.1 Tracking Status Updates
- [ ] Verify tracking status updates when event status is added
- [ ] Verify actual delivery date is set when "Delivered" status event is added
- [ ] Verify tracking events are sorted by date

#### 5.2 Data Integrity
- [ ] Verify tracking data is correctly associated with sale orders
- [ ] Verify tracking data is correctly associated with carriers
- [ ] Verify uploaded documents are stored in correct location
- [ ] Verify uploaded images are stored in correct location

#### 5.3 User Permissions
- [ ] Verify only authenticated users can access tracking features
- [ ] Verify appropriate error messages for unauthorized access

### 6. Performance Tests
- [ ] Verify tracking history loads quickly for sale orders with many trackings
- [ ] Verify tracking events load quickly for trackings with many events
- [ ] Verify file uploads complete within reasonable time

### 7. Security Tests
- [ ] Verify users cannot access tracking data for sale orders they don't own
- [ ] Verify file uploads are properly sanitized
- [ ] Verify SQL injection protection is in place
- [ ] Verify XSS protection is in place

### 8. Edge Case Tests
- [ ] Verify system handles sale orders with no tracking gracefully
- [ ] Verify system handles trackings with no events gracefully
- [ ] Verify system handles trackings with no documents gracefully
- [ ] Verify system handles concurrent tracking updates correctly
- [ ] Verify system handles large numbers of tracking records correctly

## Test Data Requirements
- Sample sale orders with various statuses
- Sample carriers
- Sample tracking numbers
- Sample tracking events with various statuses
- Sample documents of different types
- Sample images for proof uploads

## Success Criteria
- All API endpoints return correct HTTP status codes
- All validation rules are properly enforced
- All UI elements function as expected
- All business logic is correctly implemented
- No data integrity issues occur
- Performance is acceptable under normal load
- Security vulnerabilities are not present
- Edge cases are handled gracefully

## Test Execution
1. Execute database migration tests
2. Execute model relationship tests
3. Execute API endpoint tests
4. Execute UI tests
5. Execute business logic tests
6. Execute performance tests
7. Execute security tests
8. Execute edge case tests

## Test Reporting
- Record all test results
- Document any failures with detailed steps to reproduce
- Create bug reports for any issues found
- Provide summary of test coverage
- Provide recommendations for improvements
