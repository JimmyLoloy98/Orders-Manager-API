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
            ['name' => 'Recreo Panchito Falcon'],
            ['name' => 'Monteprado Club Campestre'],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
