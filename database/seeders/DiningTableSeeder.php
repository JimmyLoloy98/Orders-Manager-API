<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;

class DiningTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DiningTable::create([
                'name' => "Mesa $i",
                'status' => 'free',
            ]);
        }
    }
}
