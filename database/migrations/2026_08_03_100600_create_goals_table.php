<?php

declare(strict_types=1);

use App\Enums\GoalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('practice_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('target_quantity');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default(GoalStatus::Active->value)->index();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
