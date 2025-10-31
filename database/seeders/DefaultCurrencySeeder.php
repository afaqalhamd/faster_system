<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class DefaultCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if currencies already exist
        $currenciesCount = Currency::count();

        if ($currenciesCount > 0) {
            $this->command->info('ℹ️  Currencies already exist. Checking for SAR...');

            // Check if SAR exists
            $sar = Currency::where('code', 'SAR')->first();
            if ($sar) {
                $this->command->info("✅ SAR currency exists with ID: {$sar->id}");

                // If SAR exists but not with ID = 2, show warning
                if ($sar->id != 2) {
                    $this->command->warn("⚠️  SAR currency has ID {$sar->id}, not 2. Update AuthController if needed.");
                }
            } else {
                $this->command->warn('⚠️  SAR currency not found. Creating it...');
                $this->createSAR();
            }

            return;
        }

        // Create default currencies
        $this->command->info('Creating default currencies...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Currency 1: USD (for reference)
        Currency::create([
            'id' => 1,
            'name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
            'exchange_rate' => 3.75, // 1 USD = 3.75 SAR
            'is_company_currency' => 0,
        ]);

        // Currency 2: SAR (Default for customers)
        Currency::create([
            'id' => 2,
            'name' => 'Saudi Riyal',
            'symbol' => 'ر.س',
            'code' => 'SAR',
            'exchange_rate' => 1.00, // Base currency
            'is_company_currency' => 1, // Company currency
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Default currencies created successfully');
        $this->command->info('   - Currency ID 1: USD (US Dollar)');
        $this->command->info('   - Currency ID 2: SAR (Saudi Riyal) - Default for customers');
    }

    /**
     * Create SAR currency if it doesn't exist
     */
    private function createSAR(): void
    {
        Currency::create([
            'name' => 'Saudi Riyal',
            'symbol' => 'ر.س',
            'code' => 'SAR',
            'exchange_rate' => 1.00,
            'is_company_currency' => 1,
        ]);

        $sar = Currency::where('code', 'SAR')->first();
        $this->command->info("✅ SAR currency created with ID: {$sar->id}");

        if ($sar->id != 2) {
            $this->command->warn("⚠️  SAR currency has ID {$sar->id}, not 2. Update AuthController currency_id to {$sar->id}");
        }
    }
}
