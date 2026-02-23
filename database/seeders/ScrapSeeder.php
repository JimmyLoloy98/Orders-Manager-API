<?php

namespace Database\Seeders;

use App\Models\Scrap;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ScrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $scrapTypes = [
            ['name' => 'Chatarra', 'description' => 'Chatarra general', 'unit_measure' => 'kg'],
            ['name' => 'Cobre', 'description' => 'Cobre', 'unit_measure' => 'kg'],
            ['name' => 'Bronce', 'description' => 'Bronce', 'unit_measure' => 'kg'],
            ['name' => 'Aluminio', 'description' => 'Aluminio', 'unit_measure' => 'kg'],
            ['name' => 'Antimonio', 'description' => 'Antimonio', 'unit_measure' => 'kg'],
            ['name' => 'Zapata', 'description' => 'Zapata', 'unit_measure' => 'kg'],
            ['name' => 'Bateria entera', 'description' => 'Bateria entera', 'unit_measure' => 'und'],
            ['name' => '1/2 bateria', 'description' => '1/2 bateria', 'unit_measure' => 'und'],
            ['name' => 'Bateria de moto', 'description' => 'Bateria de moto', 'unit_measure' => 'und'],
            ['name' => 'Botas', 'description' => 'Botas', 'unit_measure' => 'kg'],
            ['name' => 'Papel', 'description' => 'Papel', 'unit_measure' => 'kg'],
            ['name' => 'Botella', 'description' => 'Botella', 'unit_measure' => 'kg'],
            ['name' => 'Rallador de aluminio', 'description' => 'Rallador de aluminio', 'unit_measure' => 'kg'],
            ['name' => 'Rallador de bronce', 'description' => 'Rallador de bronce', 'unit_measure' => 'kg'],
            ['name' => 'Motor peso', 'description' => 'Motor peso', 'unit_measure' => 'kg'],
            ['name' => 'Motor de moto', 'description' => 'Motor de moto', 'unit_measure' => 'und'],
            ['name' => 'Cilindros', 'description' => 'Cilindros', 'unit_measure' => 'und'],
            ['name' => 'Culatas', 'description' => 'Culatas', 'unit_measure' => 'und'],
            ['name' => 'Bocamasa', 'description' => 'Bocamasa', 'unit_measure' => 'und'],
            ['name' => 'Aro delantero', 'description' => 'Aro delantero', 'unit_measure' => 'und'],
            ['name' => 'Aro posterior', 'description' => 'Aro posterior', 'unit_measure' => 'und'],
            ['name' => 'Bobinas', 'description' => 'Bobinas', 'unit_measure' => 'und'],
            ['name' => 'Cable', 'description' => 'Cable', 'unit_measure' => 'kg'],
        ];

        foreach ($companies as $company) {
            foreach ($scrapTypes as $type) {
                Scrap::create([
                    'company_id' => $company->id,
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'unit_measure' => $type['unit_measure'],
                ]);
            }
        }
    }
}
