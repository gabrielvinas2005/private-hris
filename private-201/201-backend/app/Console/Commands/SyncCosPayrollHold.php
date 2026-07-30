<?php

namespace App\Console\Commands;

use App\Services\CosPayrollHoldService;
use Illuminate\Console\Command;

class SyncCosPayrollHold extends Command
{
    protected $signature = 'cos:sync-payroll-hold';

    protected $description = 'Hold payroll for COS employees without an active contract or with expired contracts';

    public function handle(CosPayrollHoldService $service): int
    {
        $this->info('Syncing COS payroll hold status...');

        $result = $service->syncAllCosEmployees();

        $this->info("Processed {$result['total']} active COS employee(s).");
        $this->info("Contracts on file: {$result['with_contract']} employee(s).");
        $this->info("Newly set on hold this run: {$result['held']}");
        $this->info("Released from auto-hold this run: {$result['released']}");
        $this->info("Already on hold (no/expired contract): {$result['already_held']}");
        $this->info("Already active (valid contract, not held): {$result['already_active']}");

        return 0;
    }
}
