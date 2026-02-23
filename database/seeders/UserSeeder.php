<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $index => $company) {
            User::create([
                'name' => "Admin User " . ($index + 1),
                'username' => "admin" . ($index + 1),
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]);
        }
    }
}
