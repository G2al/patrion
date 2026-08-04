<?php

declare(strict_types=1);

use App\Enums\ActivityStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->index();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->string('priority')->default(Priority::Medium->value)->index();
            $table->string('status')->default(ActivityStatus::Pending->value)->index();
            $table->dateTime('completed_at')->nullable();
            $table->text('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
