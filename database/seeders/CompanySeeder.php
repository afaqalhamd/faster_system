<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{

    public function run(): void
    {
        Company::create([
            'id'                    => 1,
            'name'                  => 'Fasterxpress Systems',
            'mobile'                => '+8616620154555',
            'email'                =>  'fasterxpress@gmail.com',
            'address'               => 'China/Guangzhou.Foshan.Yiwu',
            'language_code'         => null,
            'language_name'         => null,
            'timezone'              => 'Asia/Kolkata',
            'date_format'           => 'Y-m-d',
            'time_format'           => '24',
        ]);
    }
}
