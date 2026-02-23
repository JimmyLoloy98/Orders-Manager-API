<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();

            foreach ($clients as $client) {
                // Create 5 credits per client
                for ($i = 1; $i <= 5; $i++) {
                    $credit = Credit::create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'date' => Carbon::now()->subDays(rand(1, 30)),
                        'notes' => "Crédito de ejemplo $i para " . $client->business_name,
                        'amount' => 0, // Will update after creating items
                    ]);

                    $totalAmount = 0;
                    // Create 1-3 items per credit
                    for ($j = 1; $j <= rand(1, 3); $j++) {
                        $price = rand(100, 1000);
                        CreditItem::create([
                            'credit_id' => $credit->id,
                            'description' => "Item $j del crédito $i",
                            'price' => $price,
                        ]);
                        $totalAmount += $price;
                    }

                    $credit->update(['amount' => $totalAmount]);
                }
            }
        }
    }
}
