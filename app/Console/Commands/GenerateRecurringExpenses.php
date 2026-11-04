<?php

namespace App\Console\Commands;

use App\Services\RecurringExpenseService;
use Illuminate\Console\Command;

class GenerateRecurringExpenses extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Generate due occurrences for fixed ("fijo") expenses';

    public function handle(RecurringExpenseService $service): int
    {
        $count = $service->generateDueOccurrences();

        $this->info("Generated {$count} recurring expense occurrence(s).");

        return self::SUCCESS;
    }
}
