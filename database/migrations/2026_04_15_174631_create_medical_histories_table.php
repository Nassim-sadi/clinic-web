<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('clinic_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('encounter_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type', 50)->default('general');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('record_date');
            $table->enum('severity', ['normal', 'mild', 'moderate', 'severe'])->default('normal');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'record_date']);
            $table->index(['clinic_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
