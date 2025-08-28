<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AppSettings;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppSettings::create([
            'application_name'  => 'Faster-Weal',
            'footer_text'       => 'Copyright© Faster-Weal - 2025',
            'language_id'       => 1,
        ]);
    }
}
