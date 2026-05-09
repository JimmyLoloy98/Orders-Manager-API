<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupMozoSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mozo:cleanup-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia sesiones de mozo de días anteriores al actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();
        $deleted = \App\Models\MozoSession::where('session_date', '<', $today)->delete();
        $this->info("Se han eliminado {$deleted} sesiones de mozo de días anteriores.");
    }
}
