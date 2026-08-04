<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->string('event_type')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
