<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\SalesStatusHistory;
use App\Models\User;
use App\Models\Party\Party;
use App\Enums\ItemTransactionUniqueCode;
use App\Services\SalesStatusService;

class SalesStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $sale;
    private $salesStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test sale
        $party = Party::factory()->create();
        $this->sale = Sale::factory()->create([
            'party_id' => $party->id,
            'sales_status' => 'Pending',
            'inventory_status' => 'pending',
            'inventory_deducted_at' => null,
        ]);

        // Create item transaction
        $item = Item::factory()->create();
        ItemTransaction::factory()->create([
            'transaction_id' => $this->sale->id,
            'transaction_type' => Sale::class,
            'item_id' => $item->id,
            'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value,
        ]);

        $this->salesStatusService = app(SalesStatusService::class);
    }

    /** @test */
    public function can_update_status_to_pod_and_deduct_inventory(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('proof.jpg');

        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'POD', [
            'notes' => 'Delivery completed and confirmed by customer',
            'proof_image' => $image,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['inventory_updated']);

        // Verify sale status updated
        $this->sale->refresh();
        $this->assertEquals('POD', $this->sale->sales_status);
        $this->assertEquals('deducted', $this->sale->inventory_status);
        $this->assertNotNull($this->sale->inventory_deducted_at);

        // Verify item transactions updated to SALE
        $itemTransaction = $this->sale->itemTransaction()->first();
        $this->assertEquals(ItemTransactionUniqueCode::SALE->value, $itemTransaction->unique_code);

        // Verify status history recorded
        $history = SalesStatusHistory::where('sale_id', $this->sale->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('Pending', $history->previous_status);
        $this->assertEquals('POD', $history->new_status);
        $this->assertEquals('Delivery completed and confirmed by customer', $history->notes);
        $this->assertNotNull($history->proof_image);

        // Verify image was stored
        // Storage::disk('public')->assertExists($history->proof_image);
    }

    /** @test */
    public function can_update_status_to_cancelled_and_restore_inventory(): void
    {
        // First, move to POD to deduct inventory
        $this->sale->update([
            'sales_status' => 'POD',
            'inventory_status' => 'deducted',
            'inventory_deducted_at' => now(),
        ]);

        $this->sale->itemTransaction()->update([
            'unique_code' => ItemTransactionUniqueCode::SALE->value
        ]);

        // Now cancel the sale
        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'Cancelled', [
            'notes' => 'Customer requested cancellation',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['inventory_updated']);

        // Verify sale status updated
        $this->sale->refresh();
        $this->assertEquals('Cancelled', $this->sale->sales_status);
        $this->assertEquals('restored', $this->sale->inventory_status);
        $this->assertNull($this->sale->inventory_deducted_at);

        // Verify item transactions reverted to SALE_ORDER
        $itemTransaction = $this->sale->itemTransaction()->first();
        $this->assertEquals(ItemTransactionUniqueCode::SALE_ORDER->value, $itemTransaction->unique_code);

        // Verify status history recorded
        $history = SalesStatusHistory::where('sale_id', $this->sale->id)
            ->where('new_status', 'Cancelled')
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals('POD', $history->previous_status);
        $this->assertEquals('Cancelled', $history->new_status);
        $this->assertEquals('Customer requested cancellation', $history->notes);
    }

    /** @test */
    public function cannot_transition_to_invalid_status(): void
    {
        // Try to move from Pending directly to POD (should be allowed)
        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'POD', [
            'notes' => 'Direct to POD',
        ]);

        $this->assertTrue($result['success']); // This should work

        // Now try to move from Cancelled to Processing (should not be allowed)
        $this->sale->update(['sales_status' => 'Cancelled']);

        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'Processing', [
            'notes' => 'Invalid transition attempt',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid status transition', $result['message']);
    }

    /** @test */
    public function can_retrieve_status_history(): void
    {
        // Create some status changes
        $this->salesStatusService->updateSalesStatus($this->sale, 'Processing', [
            'notes' => 'Started processing order',
        ]);

        $this->salesStatusService->updateSalesStatus($this->sale, 'Completed', [
            'notes' => 'Order completed',
        ]);

        $history = $this->salesStatusService->getStatusHistory($this->sale);

        $this->assertCount(2, $history);

        // Verify history is in reverse chronological order
        $this->assertEquals('Completed', $history[0]['new_status']);
        $this->assertEquals('Processing', $history[1]['new_status']);
    }

    /** @test */
    public function status_requiring_proof_validates_inputs(): void
    {
        // Test with missing notes for POD status
        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'POD', []);

        // This should still work in service level, validation happens at controller level
        $this->assertTrue($result['success']);

        // Test via HTTP request to controller
        $response = $this->postJson("/sale/invoice/update-sales-status/{$this->sale->id}", [
            'sales_status' => 'POD',
            // Missing required notes
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function can_get_status_history_via_api(): void
    {
        // Create some history
        $this->salesStatusService->updateSalesStatus($this->sale, 'Processing', [
            'notes' => 'Order processing started',
        ]);

        $response = $this->getJson("/sale/invoice/get-sales-status-history/{$this->sale->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'sale_id',
                        'previous_status',
                        'new_status',
                        'notes',
                        'proof_image',
                        'changed_by',
                        'changed_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function keeps_inventory_deducted_when_moving_from_pod_to_other_status(): void
    {
        // Move to POD first (deducts inventory)
        $this->salesStatusService->updateSalesStatus($this->sale, 'POD', [
            'notes' => 'Delivered',
        ]);

        $this->sale->refresh();
        $this->assertEquals('deducted', $this->sale->inventory_status);

        // Move to Completed (should keep inventory deducted)
        $result = $this->salesStatusService->updateSalesStatus($this->sale, 'Completed', [
            'notes' => 'Order completed successfully',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['inventory_updated']); // No inventory change

        $this->sale->refresh();
        $this->assertEquals('Completed', $this->sale->sales_status);
        $this->assertEquals('deducted', $this->sale->inventory_status); // Still deducted
    }
}
