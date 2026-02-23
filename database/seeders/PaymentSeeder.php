<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Client;
use App\Models\Company;
use App\Models\Scrap;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();
            $scraps = Scrap::where('company_id', $company->id)->get();

            foreach ($clients as $client) {
                // Create 5 payments per client
                for ($i = 1; $i <= 5; $i++) {
                    $payment = Payment::create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'date' => Carbon::now()->subDays(rand(1, 15)),
                        'notes' => "Pago con chatarra $i de " . $client->business_name,
                        'total_value' => 0, // Will update after creating items
                    ]);

                    $totalValue = 0;
                    // Create 1-2 items per payment
                    $numItems = min(rand(1, 2), $scraps->count());
                    $selectedScraps = $scraps->random($numItems);

                    foreach ($selectedScraps as $scrap) {
                        $amount = rand(50, 500);
                        PaymentItem::create([
                            'payment_id' => $payment->id,
                            'scrap_id' => $scrap->id,
                            'amount' => $amount,
                            'quantity' => rand(1, 50),
                        ]);
                        $totalValue += $amount;
                    }

                    $payment->update(['total_value' => $totalValue]);
                }
            }
        }
    }
}
