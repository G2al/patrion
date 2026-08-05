<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('contact_goals', function (Blueprint $table): void { $table->id(); $table->foreignId('contact_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->text('description')->nullable(); $table->string('status')->default('planned')->index(); $table->date('due_date')->nullable(); $table->unsignedTinyInteger('progress_percentage')->default(0); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('contact_goals'); } };
