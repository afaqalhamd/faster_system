<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\State;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
                     ["name" => "Riyadh"],
                     ["name" => "Makkah"],
                     ["name" => "Al Madinah"],
                     ["name" => "Al Qassim"],
                     ["name" => "Eastern Province"],
                     ["name" => "Asir"],
                     ["name" => "Tabuk"],
                     ["name" => "Hail"],
                     ["name" => "Northern Borders"],
                     ["name" => "Jazan"],
                     ["name" => "Najran"],
                     ["name" => "Al Bahah"],
                     ["name" => "Al Jawf"]
                ];

       foreach ($records as $record) {
            State::create([
                'name'               => $record['name'],
            ]);
       }
    }
}
