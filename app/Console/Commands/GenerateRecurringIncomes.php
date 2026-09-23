<?php

namespace App\Console\Commands;

use App\Services\RecurringIncomeService;
use Illuminate\Console\Command;

class GenerateRecurringIncomes extends Command
{
    protected $signature = 'incomes:generate-recurring';

    protected $description = 'Generate due occurrences for fixed ("fijo") incomes';

    public function handle(RecurringIncomeService $service): int
    {
        $count = $service->generateDueOccurrences();

        $this->info("Generated {$count} recurring income occurrence(s).");

        return self::SUCCESS;
    }
}
