<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->string('mode')->nullable();
            $table->string('status')->default(AppointmentStatus::Scheduled->value)->index();
            $table->string('outcome')->nullable();
            $table->text('emerged_need')->nullable();
            $table->boolean('prospect_interested')->nullable();
            $table->boolean('should_convert_to_client')->default(false);
            $table->boolean('should_open_practice')->default(false);
            $table->boolean('follow_up_required')->default(false);
            $table->dateTime('next_contact_at')->nullable();
            $table->text('final_notes')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
