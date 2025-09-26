# Complete Shipment Tracking Implementation Example

This document provides a complete, practical example of implementing and using the shipment tracking system.

## Complete Implementation Flow

### 1. Database Setup

First, ensure the database tables are created by running the migrations:

```bash
php artisan migrate
```

This will create the three required tables:
- `shipment_trackings`
- `shipment_tracking_events`
- `shipment_documents`

### 2. Creating a Tracking Record via Web Interface

#### Step 1: Access Sale Order Edit Page
1. Navigate to **Sales** → **Sale Orders**
2. Click **Edit** on any sale order (e.g., ID: 1)

#### Step 2: Add Tracking Record
1. Scroll to the **Tracking** section at the bottom
2. Click the **"Add Tracking"** button
3. Fill in the form:
   ```
   Carrier: DHL
   Tracking Number: DHL1234567890
   Tracking URL: https://www.dhl.com/en/express/tracking.html?AWB=1234567890
   Status: In Transit
   Estimated Delivery Date: 2025-10-15
   Note: Fragile items - handle with care
   ```
4. Click **Save**

#### Step 3: Add Tracking Event
1. Click **"Add Event"** for the new tracking record
2. Fill in the event details:
   ```
   Event Date: 2025-09-26 14:30
   Location: New York Distribution Center
   Status: In Transit
   Description: Package sorted for delivery to destination
   Latitude: 40.7128
   Longitude: -74.0060
   ```
3. Click **Save**

### 3. Retrieving Tracking Data via API

#### Using cURL
```bash
# Get specific tracking record
curl -X GET "http://your-domain.com/api/shipment-tracking/1" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# Get all tracking records for a sale order
curl -X GET "http://your-domain.com/api/sale-orders/1/tracking-history" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

#### Using JavaScript (Web Interface)
```javascript
// Get tracking data for display
async function loadTrackingData(saleOrderId) {
    try {
        const response = await fetch(`/api/sale-orders/${saleOrderId}/tracking-history`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        if (result.status) {
            displayTrackingData(result.data);
        }
    } catch (error) {
        console.error('Error loading tracking data:', error);
    }
}

function displayTrackingData(trackings) {
    const container = document.getElementById('trackingContent');
    container.innerHTML = '';
    
    if (trackings.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No tracking available</p>
                <p class="text-muted small">Tracking will be recorded when shipments are processed</p>
            </div>
        `;
        return;
    }
    
    trackings.forEach(tracking => {
        const trackingElement = createTrackingElement(tracking);
        container.appendChild(trackingElement);
    });
}

function createTrackingElement(tracking) {
    const div = document.createElement('div');
    div.className = 'tracking-item mb-4 p-3 border rounded';
    div.dataset.trackingId = tracking.id;
    
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1">
                    ${tracking.carrier ? tracking.carrier.name : 'Unknown Carrier'}
                    ${tracking.tracking_number ? '(#' + tracking.tracking_number + ')' : ''}
                </h6>
                <span class="badge bg-${getStatusColor(tracking.status)}">
                    ${tracking.status}
                </span>
                ${tracking.estimated_delivery_date ? 
                    `<small class="d-block text-muted">
                        Estimated Delivery: ${formatDate(tracking.estimated_delivery_date)}
                    </small>` : ''}
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary edit-tracking" data-tracking-id="${tracking.id}">
                    <i class="bx bx-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-tracking" data-tracking-id="${tracking.id}">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        </div>
        ${tracking.tracking_events && tracking.tracking_events.length > 0 ? 
            `<div class="tracking-events mt-3">
                <h6 class="mb-2">Events</h6>
                <div class="timeline">
                    ${tracking.tracking_events.map(event => createEventElement(event)).join('')}
                </div>
            </div>` : ''}
        <div class="mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary add-event-btn" data-tracking-id="${tracking.id}">
                <i class="bx bx-plus-circle me-1"></i>Add Event
            </button>
        </div>
    `;
    
    return div;
}

function createEventElement(event) {
    return `
        <div class="timeline-item d-flex mb-2">
            <div class="flex-shrink-0 me-3">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                    <i class="bx bx-map text-white"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>${event.location || 'Unknown Location'}</strong>
                    <small class="text-muted">${formatDateTime(event.event_date)}</small>
                </div>
                <p class="mb-1">${event.description || ''}</p>
                ${event.status ? `<span class="badge bg-secondary">${event.status}</span>` : ''}
            </div>
        </div>
    `;
}

function getStatusColor(status) {
    const colors = {
        'Delivered': 'success',
        'Failed': 'danger',
        'Returned': 'warning',
        default: 'info'
    };
    return colors[status] || colors.default;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
```

### 4. External API Integration Example

#### Python Example
```python
import requests
import json

class ShipmentTrackingClient:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.headers = {
            'Accept': 'application/json',
            'Authorization': f'Bearer {token}'
        }
    
    def get_tracking(self, tracking_id):
        """Get a specific tracking record"""
        url = f"{self.base_url}/api/shipment-tracking/{tracking_id}"
        response = requests.get(url, headers=self.headers)
        
        if response.status_code == 200:
            return response.json()
        else:
            raise Exception(f"Failed to get tracking: {response.status_code}")
    
    def create_tracking(self, sale_order_id, tracking_data):
        """Create a new tracking record"""
        url = f"{self.base_url}/api/sale-orders/{sale_order_id}/tracking"
        response = requests.post(url, 
                                headers={**self.headers, 'Content-Type': 'application/json'},
                                data=json.dumps(tracking_data))
        
        if response.status_code == 201:
            return response.json()
        else:
            raise Exception(f"Failed to create tracking: {response.status_code}")
    
    def add_event(self, tracking_id, event_data):
        """Add an event to a tracking record"""
        url = f"{self.base_url}/api/shipment-tracking/{tracking_id}/events"
        response = requests.post(url,
                                headers={**self.headers, 'Content-Type': 'application/json'},
                                data=json.dumps(event_data))
        
        if response.status_code == 200:
            return response.json()
        else:
            raise Exception(f"Failed to add event: {response.status_code}")

# Usage example
client = ShipmentTrackingClient('http://your-domain.com', 'your-api-token')

# Create tracking
tracking_data = {
    'carrier_id': 1,
    'tracking_number': 'TRK123456789',
    'tracking_url': 'https://carrier.com/track/TRK123456789',
    'status': 'In Transit',
    'estimated_delivery_date': '2025-10-15',
    'notes': 'Fragile items'
}

try:
    result = client.create_tracking(1, tracking_data)
    print(f"Tracking created with ID: {result['data']['id']}")
    
    # Add event
    event_data = {
        'event_date': '2025-09-26T14:30:00.000000Z',
        'location': 'Distribution Center',
        'status': 'In Transit',
        'description': 'Package sorted for delivery'
    }
    
    event_result = client.add_event(result['data']['id'], event_data)
    print(f"Event added with ID: {event_result['data']['id']}")
    
    # Get tracking data
    tracking = client.get_tracking(result['data']['id'])
    print(f"Tracking status: {tracking['data']['status']}")
    
except Exception as e:
    print(f"Error: {e}")
```

### 5. Mobile Integration Example (React Native)

```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, StyleSheet, TouchableOpacity } from 'react-native';

const ShipmentTrackingScreen = ({ route }) => {
  const { saleOrderId } = route.params;
  const [trackings, setTrackings] = useState([]);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    loadTrackingData();
  }, []);
  
  const loadTrackingData = async () => {
    try {
      const response = await fetch(`http://your-domain.com/api/sale-orders/${saleOrderId}/tracking-history`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${userToken}`
        }
      });
      
      const result = await response.json();
      if (result.status) {
        setTrackings(result.data);
      }
    } catch (error) {
      console.error('Error loading tracking data:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const renderTrackingItem = ({ item: tracking }) => (
    <View style={styles.trackingItem}>
      <View style={styles.trackingHeader}>
        <Text style={styles.carrierName}>
          {tracking.carrier ? tracking.carrier.name : 'Unknown Carrier'}
          {tracking.tracking_number && ` (#${tracking.tracking_number})`}
        </Text>
        <Text style={[styles.statusBadge, getStatusStyle(tracking.status)]}>
          {tracking.status}
        </Text>
      </View>
      
      {tracking.estimated_delivery_date && (
        <Text style={styles.deliveryDate}>
          Estimated Delivery: {formatDate(tracking.estimated_delivery_date)}
        </Text>
      )}
      
      {tracking.tracking_events && tracking.tracking_events.length > 0 && (
        <View style={styles.eventsContainer}>
          <Text style={styles.eventsTitle}>Events</Text>
          {tracking.tracking_events.map((event, index) => (
            <View key={index} style={styles.eventItem}>
              <Text style={styles.eventLocation}>{event.location || 'Unknown Location'}</Text>
              <Text style={styles.eventDate}>{formatDateTime(event.event_date)}</Text>
              <Text style={styles.eventDescription}>{event.description || ''}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
  
  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <Text>Loading tracking data...</Text>
      </View>
    );
  }
  
  return (
    <View style={styles.container}>
      <FlatList
        data={trackings}
        renderItem={renderTrackingItem}
        keyExtractor={(item) => item.id.toString()}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>No tracking information available</Text>
          </View>
        }
      />
    </View>
  );
};

const getStatusStyle = (status) => {
  const styles = {
    'Delivered': { backgroundColor: '#d4edda', color: '#155724' },
    'Failed': { backgroundColor: '#f8d7da', color: '#721c24' },
    'Returned': { backgroundColor: '#fff3cd', color: '#856404' },
    default: { backgroundColor: '#d1ecf1', color: '#0c5460' }
  };
  return styles[status] || styles.default;
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

const formatDateTime = (dateTimeString) => {
  const date = new Date(dateTimeString);
  return date.toLocaleString();
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa'
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center'
  },
  trackingItem: {
    backgroundColor: 'white',
    margin: 10,
    padding: 15,
    borderRadius: 8,
    elevation: 2
  },
  trackingHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10
  },
  carrierName: {
    fontSize: 16,
    fontWeight: 'bold'
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 12,
    fontSize: 12,
    fontWeight: 'bold'
  },
  deliveryDate: {
    color: '#6c757d',
    marginBottom: 10
  },
  eventsContainer: {
    marginTop: 10
  },
  eventsTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 5
  },
  eventItem: {
    paddingVertical: 5
  },
  eventLocation: {
    fontWeight: 'bold'
  },
  eventDate: {
    color: '#6c757d',
    fontSize: 12
  },
  eventDescription: {
    marginTop: 2
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20
  },
  emptyText: {
    textAlign: 'center',
    color: '#6c757d'
  }
});

export default ShipmentTrackingScreen;
```

## Testing the Implementation

### 1. Manual Testing Checklist

- [ ] Create tracking record via web interface
- [ ] Verify tracking appears in list
- [ ] Add event to tracking record
- [ ] Verify event appears in timeline
- [ ] Edit tracking record
- [ ] Verify changes are saved
- [ ] Delete tracking record
- [ ] Verify record is removed
- [ ] Test API endpoints with valid data
- [ ] Test API endpoints with invalid data
- [ ] Test authentication requirements

### 2. Automated Testing Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\ShipmentTracking;
use App\Models\User;

class ShipmentTrackingTest extends TestCase
{
    public function test_create_tracking_via_api()
    {
        $user = User::factory()->create();
        $saleOrder = SaleOrder::factory()->create();
        
        $trackingData = [
            'carrier_id' => 1,
            'tracking_number' => 'TRK123456789',
            'tracking_url' => 'https://carrier.com/track/TRK123456789',
            'status' => 'In Transit',
            'estimated_delivery_date' => '2025-10-15',
            'notes' => 'Test tracking'
        ];
        
        $response = $this->actingAs($user)
                         ->postJson("/api/sale-orders/{$saleOrder->id}/tracking", $trackingData);
        
        $response->assertStatus(201)
                 ->assertJson([
                     'status' => true,
                     'data' => [
                         'sale_order_id' => $saleOrder->id,
                         'tracking_number' => 'TRK123456789',
                         'status' => 'In Transit'
                     ]
                 ]);
        
        $this->assertDatabaseHas('shipment_trackings', [
            'sale_order_id' => $saleOrder->id,
            'tracking_number' => 'TRK123456789'
        ]);
    }
    
    public function test_get_tracking_via_api()
    {
        $user = User::factory()->create();
        $tracking = ShipmentTracking::factory()->create();
        
        $response = $this->actingAs($user)
                         ->getJson("/api/shipment-tracking/{$tracking->id}");
        
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'data' => [
                         'id' => $tracking->id,
                         'tracking_number' => $tracking->tracking_number
                     ]
                 ]);
    }
}
```

## Conclusion

This complete example demonstrates how to implement and use the shipment tracking system. The system provides:

1. **Web Interface**: For manual creation and management of tracking records
2. **API Endpoints**: For programmatic access and integration with external systems
3. **Mobile Support**: For mobile application integration
4. **Comprehensive Testing**: For ensuring reliability and correctness

The implementation supports all required features including multi-tracking per sale order, event logging, document management, and carrier integration. The system is secure, scalable, and provides consistent data access through both web and API interfaces.
