<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('waiting');
            $table->integer('position')->nullable();
            $table->integer('priority')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $timestamp = now();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index(['clinic_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_queue');
    }
};
