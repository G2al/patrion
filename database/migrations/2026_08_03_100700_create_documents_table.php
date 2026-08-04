<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('disk')->default('local');
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status')->default(DocumentStatus::Valid->value)->index();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
