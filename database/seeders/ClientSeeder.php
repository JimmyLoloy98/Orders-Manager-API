<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Origin;
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
            $origins = Origin::where('company_id', $company->id)->pluck('name')->toArray();

            for ($i = 1; $i <= 5; $i++) {
                Client::create([
                    'company_id' => $company->id,
                    'username' => "user{$i}_company{$company->id}",
                    'current_debt' => rand(100, 5000),
                ]);
            }
        }
    }
}
