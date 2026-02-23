<?php

namespace Database\Seeders;

use App\Models\Origin;
use App\Models\Company;
use Illuminate\Database\Seeder;

class OriginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $origins = ['Zona Norte', 'Zona Sur', 'Zona Centro', 'Zona Industrial', 'Zona Rural'];

        foreach ($companies as $company) {
            foreach ($origins as $originName) {
                Origin::create([
                    'company_id' => $company->id,
                    'name' => $originName,
                    'description' => "Clientes de la " . strtolower($originName),
                ]);
            }
        }
    }
}
