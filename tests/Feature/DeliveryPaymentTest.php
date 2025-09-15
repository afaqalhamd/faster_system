<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale\Sale;
use App\Models\Party\Party;
use App\Models\PaymentTypes;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeliveryPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $deliveryUser;
    protected $sale;
    protected $paymentType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a delivery user
        $this->deliveryUser = User::factory()->create();

        // Create delivery role if it doesn't exist
        $deliveryRole = Role::firstOrCreate(['name' => 'Delivery']);
        $this->deliveryUser->assignRole($deliveryRole);

        // Create a customer
        $customer = Party::factory()->create([
            'party_type' => 'customer'
        ]);

        // Create a payment type
        $this->paymentType = PaymentTypes::factory()->create();

        // Create a sale
        $this->sale = Sale::factory()->create([
            'party_id' => $customer->id,
            'grand_total' => 1000,
            'paid_amount' => 0,
            'sales_status' => 'Delivery'
        ]);
    }

    /** @test */
    public function delivery_user_can_record_payment_for_sale()
    {
        $this->actingAs($this->deliveryUser);

        $response = $this->postJson("/sale/invoice/delivery-payment/{$this->sale->id}", [
            'amount' => 500,
            'payment_type_id' => $this->paymentType->id,
            'note' => 'Payment at delivery'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Payment recorded successfully'
        ]);

        // Refresh the sale model
        $this->sale->refresh();

        // Check that the payment was recorded
        $this->assertEquals(500, $this->sale->paid_amount);

        // Check that the sale status was updated
        $this->assertEquals('Delivery Payment', $this->sale->sales_status);
    }

    /** @test */
    public function regular_user_with_permission_can_record_delivery_payment()
    {
        // Create a regular user with sale invoice edit permission
        $regularUser = User::factory()->create();
        $regularUser->givePermissionTo('sale.invoice.edit');

        $this->actingAs($regularUser);

        $response = $this->postJson("/sale/invoice/delivery-payment/{$this->sale->id}", [
            'amount' => 300,
            'payment_type_id' => $this->paymentType->id,
            'note' => 'Partial payment at delivery'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Payment recorded successfully'
        ]);

        // Refresh the sale model
        $this->sale->refresh();

        // Check that the payment was recorded
        $this->assertEquals(300, $this->sale->paid_amount);

        // Check that the sale status was updated
        $this->assertEquals('Delivery Payment', $this->sale->sales_status);
    }

    /** @test */
    public function user_without_permission_cannot_record_delivery_payment()
    {
        // Create a regular user without permissions
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        $response = $this->postJson("/sale/invoice/delivery-payment/{$this->sale->id}", [
            'amount' => 500,
            'payment_type_id' => $this->paymentType->id,
            'note' => 'Unauthorized payment'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_get_delivery_payment_details()
    {
        $this->actingAs($this->deliveryUser);

        $response = $this->getJson("/sale/invoice/delivery-payment-details/{$this->sale->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'sale_id' => $this->sale->id,
                'sale_code' => $this->sale->sale_code,
                'grand_total' => number_format($this->sale->grand_total, 2),
                'paid_amount' => number_format($this->sale->paid_amount, 2),
                'balance' => number_format($this->sale->grand_total - $this->sale->paid_amount, 2)
            ]
        ]);
    }
}
