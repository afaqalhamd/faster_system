<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ShipmentTrackingService;
use App\Services\WaybillValidationService;
use App\Models\Sale\ShipmentTracking;
use App\Models\Sale\SaleOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class ShipmentTrackingServiceWaybillTest extends TestCase
{
    use RefreshDatabase;

    protected $shipmentTrackingService;
    protected $waybillValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waybillValidationService = new WaybillValidationService();
        $this->shipmentTrackingService = new ShipmentTrackingService($this->waybillValidationService);
    }

    /**
     * Test creating shipment tracking with valid waybill
     */
    public function test_create_tracking_with_valid_waybill()
    {
        // Create a sale order for testing
        $saleOrder = SaleOrder::factory()->create();

        $data = [
            'sale_order_id' => $saleOrder->id,
            'waybill_number' => 'GM1234567890',
            'waybill_type' => 'AirwayBill',
            'status' => 'Pending'
        ];

        $tracking = $this->shipmentTrackingService->createTracking($data);

        $this->assertNotNull($tracking);
        $this->assertEquals('GM1234567890', $tracking->waybill_number);
        $this->assertEquals('AirwayBill', $tracking->waybill_type);
        $this->assertTrue($tracking->waybill_validated);
    }

    /**
     * Test creating shipment tracking with invalid waybill
     */
    public function test_create_tracking_with_invalid_waybill_throws_validation_exception()
    {
        // Create a sale order for testing
        $saleOrder = SaleOrder::factory()->create();

        $data = [
            'sale_order_id' => $saleOrder->id,
            'waybill_number' => 'INVALID', // Invalid format
            'waybill_type' => 'AirwayBill',
            'status' => 'Pending'
        ];

        $this->expectException(ValidationException::class);

        $this->shipmentTrackingService->createTracking($data);
    }

    /**
     * Test updating shipment tracking with valid waybill
     */
    public function test_update_tracking_with_valid_waybill()
    {
        // Create a sale order and shipment tracking for testing
        $saleOrder = SaleOrder::factory()->create();
        $tracking = ShipmentTracking::factory()->create([
            'sale_order_id' => $saleOrder->id
        ]);

        $data = [
            'waybill_number' => '1Z123456789012345678',
            'waybill_type' => 'CourierWaybill',
            'status' => 'In Transit'
        ];

        $updatedTracking = $this->shipmentTrackingService->updateTracking($tracking, $data);

        $this->assertEquals('1Z123456789012345678', $updatedTracking->waybill_number);
        $this->assertEquals('CourierWaybill', $updatedTracking->waybill_type);
        $this->assertTrue($updatedTracking->waybill_validated);
        $this->assertEquals('In Transit', $updatedTracking->status);
    }

    /**
     * Test updating shipment tracking with invalid waybill
     */
    public function test_update_tracking_with_invalid_waybill_throws_validation_exception()
    {
        // Create a sale order and shipment tracking for testing
        $saleOrder = SaleOrder::factory()->create();
        $tracking = ShipmentTracking::factory()->create([
            'sale_order_id' => $saleOrder->id
        ]);

        $data = [
            'waybill_number' => 'INVALID', // Invalid format
            'waybill_type' => 'CourierWaybill'
        ];

        $this->expectException(ValidationException::class);

        $this->shipmentTrackingService->updateTracking($tracking, $data);
    }

    /**
     * Test creating shipment tracking without waybill
     */
    public function test_create_tracking_without_waybill()
    {
        // Create a sale order for testing
        $saleOrder = SaleOrder::factory()->create();

        $data = [
            'sale_order_id' => $saleOrder->id,
            'tracking_number' => 'FAT230926123456',
            'status' => 'Pending'
        ];

        $tracking = $this->shipmentTrackingService->createTracking($data);

        $this->assertNotNull($tracking);
        $this->assertNull($tracking->waybill_number);
        $this->assertFalse($tracking->waybill_validated);
        $this->assertEquals('FAT230926123456', $tracking->tracking_number);
    }
}
