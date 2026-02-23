<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            for ($i = 1; $i <= 2; $i++) {
                Client::create([
                    'company_id' => $company->id,
                    'username' => "user{$i}_company{$company->id}",
                    'current_debt' => rand(100, 5000),
                ]);
            }
        }
    }
}
