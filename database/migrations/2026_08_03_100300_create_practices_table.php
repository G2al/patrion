<?php

declare(strict_types=1);

use App\Enums\PracticeStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practices', function (Blueprint $table): void {
            $table->id();
            $table->string('internal_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('practice_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status')->default(PracticeStatus::Draft->value)->index();
            $table->string('priority')->default(Priority::Medium->value)->index();
            $table->date('opened_at');
            $table->date('expected_at')->nullable();
            $table->date('completed_at')->nullable()->index();
            $table->decimal('expected_value', 15, 2)->nullable();
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['practice_type_id', 'status', 'completed_at'], 'practices_goal_progress_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practices');
    }
};
