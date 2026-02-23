<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            ['name' => 'Metro Scrap Solutions'],
            ['name' => 'Reciclajes Industriales S.A.'],
            ['name' => 'EcoMetales del Norte'],
            ['name' => 'Global Recycling Group'],
            ['name' => 'Chatarra Express S.A.C.'],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
