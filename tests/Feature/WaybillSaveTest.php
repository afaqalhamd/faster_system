<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\ShipmentTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaybillSaveTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that waybill information is saved when creating shipment tracking
     */
    public function test_waybill_information_is_saved_when_creating_tracking()
    {
        $saleOrder = SaleOrder::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/sale-orders/{$saleOrder->id}/tracking", [
                'waybill_number' => 'GM1234567890',
                'waybill_type' => 'AirwayBill',
                'status' => 'Pending'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Shipment tracking created successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'waybill_number',
                    'waybill_type',
                    'waybill_validated'
                ]
            ]);

        // Verify the data was saved in the database
        $this->assertDatabaseHas('shipment_trackings', [
            'waybill_number' => 'GM1234567890',
            'waybill_type' => 'AirwayBill',
            'waybill_validated' => true
        ]);
    }

    /**
     * Test that waybill information is saved when updating shipment tracking
     */
    public function test_waybill_information_is_saved_when_updating_tracking()
    {
        $saleOrder = SaleOrder::factory()->create();
        $tracking = ShipmentTracking::factory()->create([
            'sale_order_id' => $saleOrder->id
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/shipment-tracking/{$tracking->id}", [
                'waybill_number' => '1Z123456789012345678',
                'waybill_type' => 'CourierWaybill',
                'status' => 'In Transit'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Shipment tracking updated successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'waybill_number',
                    'waybill_type',
                    'waybill_validated'
                ]
            ]);

        // Verify the data was updated in the database
        $this->assertDatabaseHas('shipment_trackings', [
            'id' => $tracking->id,
            'waybill_number' => '1Z123456789012345678',
            'waybill_type' => 'CourierWaybill',
            'waybill_validated' => true
        ]);
    }

    /**
     * Test that waybill information is displayed in the tracking list
     */
    public function test_waybill_information_is_displayed_in_tracking_list()
    {
        $saleOrder = SaleOrder::factory()->create();
        ShipmentTracking::factory()->create([
            'sale_order_id' => $saleOrder->id,
            'waybill_number' => 'GM1234567890',
            'waybill_type' => 'AirwayBill'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('sale.order.edit', $saleOrder->id));

        $response->assertStatus(200);
        $response->assertSee('GM1234567890');
        $response->assertSee('AirwayBill');
    }
}
