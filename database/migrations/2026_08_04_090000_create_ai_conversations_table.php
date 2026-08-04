<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'created_at']);
        });

        Schema::create('ai_tool_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('call_id')->nullable()->index();
            $table->string('name');
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->string('status', 16);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_calls');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
