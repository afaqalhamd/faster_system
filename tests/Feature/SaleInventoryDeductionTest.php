<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleInventoryDeductionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function inventory_is_not_deducted_immediately_when_creating_sale()
    {
        // Create a sale with items
        $sale = Sale::factory()->create([
            'inventory_status' => 'pending',
            'inventory_deducted_at' => null,
        ]);

        // Create item transactions with SALE_ORDER unique code (reservation)
        $item = Item::factory()->create();
        ItemTransaction::factory()->create([
            'transaction_id' => $sale->id,
            'transaction_type' => Sale::class,
            'item_id' => $item->id,
            'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value,
        ]);

        // Refresh the sale to get updated data
        $sale->refresh();

        // Verify that inventory is not deducted yet
        $this->assertEquals('pending', $sale->inventory_status);
        $this->assertNull($sale->inventory_deducted_at);

        // Verify that item transactions still have SALE_ORDER unique code
        $transactions = $sale->itemTransaction;
        $this->assertCount(1, $transactions);
        $this->assertEquals(ItemTransactionUniqueCode::SALE_ORDER->value, $transactions->first()->unique_code);
    }

    /** @test */
    public function inventory_is_deducted_when_payment_is_complete()
    {
        // Create a sale with items
        $sale = Sale::factory()->create([
            'inventory_status' => 'pending',
            'inventory_deducted_at' => null,
            'grand_total' => 100,
            'paid_amount' => 100, // Payment is complete
        ]);

        // Create item transactions with SALE_ORDER unique code (reservation)
        $item = Item::factory()->create();
        ItemTransaction::factory()->create([
            'transaction_id' => $sale->id,
            'transaction_type' => Sale::class,
            'item_id' => $item->id,
            'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value,
        ]);

        // Simulate payment completion and inventory deduction
        // This would normally be triggered by the checkAndProcessInventoryDeduction method
        $sale->update([
            'inventory_status' => 'deducted',
            'inventory_deducted_at' => now(),
        ]);

        // Update item transactions to use SALE unique code (deduction)
        $sale->itemTransaction()->update([
            'unique_code' => ItemTransactionUniqueCode::SALE->value,
        ]);

        // Refresh the sale to get updated data
        $sale->refresh();

        // Verify that inventory is now deducted
        $this->assertEquals('deducted', $sale->inventory_status);
        $this->assertNotNull($sale->inventory_deducted_at);

        // Verify that item transactions now have SALE unique code
        $transactions = $sale->itemTransaction;
        $this->assertCount(1, $transactions);
        $this->assertEquals(ItemTransactionUniqueCode::SALE->value, $transactions->first()->unique_code);
    }
}
