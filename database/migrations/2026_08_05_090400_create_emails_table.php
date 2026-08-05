<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('emails', function (Blueprint $table): void { $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete(); $table->string('sender_name'); $table->string('sender_email'); $table->string('recipient_email'); $table->string('subject'); $table->longText('body'); $table->text('preview')->nullable(); $table->string('direction')->index(); $table->boolean('is_read')->default(false)->index(); $table->boolean('is_important')->default(false)->index(); $table->dateTime('received_at')->nullable()->index(); $table->dateTime('sent_at')->nullable(); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('emails'); } };
