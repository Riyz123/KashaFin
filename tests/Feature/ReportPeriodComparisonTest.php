<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportPeriodComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_compares_the_current_month_against_the_previous_month(): void
    {
        $student = User::factory()->create();

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonth = Carbon::now()->subMonthNoOverflow();

        Expense::create(['user_id' => $student->id, 'amount' => 100, 'date' => $thisMonthStart->copy()->addDays(2), 'description' => 'Este mes']);
        Expense::create(['user_id' => $student->id, 'amount' => 50, 'date' => $lastMonth->copy()->startOfMonth()->addDays(2), 'description' => 'Mes pasado']);

        $comparison = (new ReportService)->periodComparison($student, $thisMonthStart, $thisMonthEnd);

        $this->assertSame(100.0, $comparison['current']['expense']);
        $this->assertSame(50.0, $comparison['previous']['expense']);
        $this->assertSame(100.0, $comparison['expense_change_percent']);
    }

    public function test_the_reports_page_shows_the_comparison(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('periodo anterior');
    }
}
