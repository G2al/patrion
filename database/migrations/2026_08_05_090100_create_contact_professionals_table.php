<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('contact_professionals', function (Blueprint $table): void { $table->id(); $table->foreignId('contact_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->string('role'); $table->string('company_name')->nullable(); $table->string('email')->nullable(); $table->string('phone')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('contact_professionals'); } };
