<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('noteable');
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('is_important')->default(false)->index();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
