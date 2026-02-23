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
                    'owner_name' => "Dueño $i",
                    'dni' => "DNI$i" . rand(1000, 9999),
                    'ruc' => "RUC$i" . rand(10000, 99999),
                    'business_name' => "Negocio $i " . $company->name,
                    'phone' => "999-555-00$i",
                    'email' => "cliente$i@example.com",
                    'address' => "Calle Falsa $i, Ciudad " . $company->id,
                    'origin' => $origins[array_rand($origins)],
                    'notes' => "Cliente frecuente $i",
                    'current_debt' => rand(100, 5000),
                ]);
            }
        }
    }
}
