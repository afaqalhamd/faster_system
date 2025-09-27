<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale\SaleOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaybillUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that the add tracking modal includes waybill fields
     */
    public function test_add_tracking_modal_includes_waybill_fields()
    {
        $saleOrder = SaleOrder::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('sale.order.edit', $saleOrder->id));

        $response->assertStatus(200);
        $response->assertSee('Waybill Information');
        $response->assertSee('Waybill Number');
        $response->assertSee('Waybill Type');
        $response->assertSee('Airway Bill');
        $response->assertSee('Bill of Lading');
        $response->assertSee('Courier Waybill');
        $response->assertSee('Other');
    }

    /**
     * Test that the JavaScript validation is working
     */
    public function test_javascript_includes_waybill_validation()
    {
        $saleOrder = SaleOrder::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('sale.order.edit', $saleOrder->id));

        $response->assertStatus(200);
        $response->assertSee('shipment-tracking.js');
    }
}
