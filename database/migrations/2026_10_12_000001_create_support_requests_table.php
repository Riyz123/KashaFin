<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['asesor', 'incidencia']);
            $table->text('message');
            $table->enum('status', ['pendiente', 'atendido'])->default('pendiente');
            $table->foreignId('attended_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
