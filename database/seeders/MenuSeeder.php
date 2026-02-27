<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MenuCategory::create(['name' => 'Comidas']);
        MenuCategory::create(['name' => 'Bebidas']);
        MenuCategory::create(['name' => 'Extras']);
    }
}
