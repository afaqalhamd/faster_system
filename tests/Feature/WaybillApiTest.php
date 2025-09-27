<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\ShipmentTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaybillApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test waybill validation endpoint with valid waybill
     */
    public function test_validate_waybill_with_valid_dhl_format()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/waybill/validate', [
                'waybill_number' => 'GM1234567890',
                'carrier' => 'DHL'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => true,
                'message' => 'Waybill number is valid'
            ]);
    }

    /**
     * Test waybill validation endpoint with invalid waybill
     */
    public function test_validate_waybill_with_invalid_format()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/waybill/validate', [
                'waybill_number' => 'INVALID123',
                'carrier' => 'DHL'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => false,
                'message' => 'Waybill number format is invalid'
            ]);
    }

    /**
     * Test waybill validation endpoint without waybill number
     */
    public function test_validate_waybill_without_waybill_number()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/waybill/validate', []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Waybill number is required'
            ]);
    }

    /**
     * Test waybill barcode validation endpoint with valid barcode
     */
    public function test_validate_waybill_barcode_with_valid_format()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/waybill/validate-barcode', [
                'waybill_number' => '1Z123456789012345678'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => true,
                'message' => 'Waybill barcode format is valid'
            ]);
    }

    /**
     * Test waybill barcode validation endpoint with invalid barcode
     */
    public function test_validate_waybill_barcode_with_invalid_format()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/waybill/validate-barcode', [
                'waybill_number' => 'INVALID'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => false,
                'message' => 'Waybill barcode format is invalid'
            ]);
    }

    /**
     * Test waybill rules endpoint
     */
    public function test_get_waybill_rules()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/waybill/rules');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'waybill_number',
                    'waybill_type'
                ]
            ]);
    }

    /**
     * Test creating shipment tracking with waybill through API
     */
    public function test_create_shipment_tracking_with_waybill()
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

        $this->assertEquals('GM1234567890', $response->json('data.waybill_number'));
        $this->assertEquals('AirwayBill', $response->json('data.waybill_type'));
        $this->assertTrue($response->json('data.waybill_validated'));
    }

    /**
     * Test updating shipment tracking with waybill through API
     */
    public function test_update_shipment_tracking_with_waybill()
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

        $this->assertEquals('1Z123456789012345678', $response->json('data.waybill_number'));
        $this->assertEquals('CourierWaybill', $response->json('data.waybill_type'));
        $this->assertTrue($response->json('data.waybill_validated'));
    }
}
