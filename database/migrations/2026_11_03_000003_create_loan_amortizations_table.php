<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A partial-payment request on a loan linked to a real account: the
     * debtor says "ya pagué S/X", the creditor accepts or declines it
     * (same confirmation spirit as the loan's own creation/full-payment
     * flow in Loan::approve()/confirmPayment()). Only created for linked
     * loans — an unlinked (external person) loan has no one to confirm
     * with, so its partial payments apply immediately without a row here.
     */
    public function up(): void
    {
        Schema::create('loan_amortizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_amortizations');
    }
};
