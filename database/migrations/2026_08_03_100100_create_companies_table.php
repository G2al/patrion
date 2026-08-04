<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('vat_number', 32)->nullable()->unique();
            $table->string('tax_code', 32)->nullable()->unique();
            $table->string('rea_number')->nullable();
            $table->string('pec')->nullable();
            $table->string('sdi_code', 16)->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable()->index();
            $table->string('ateco_code')->nullable();
            $table->decimal('revenue', 15, 2)->nullable();
            $table->unsignedInteger('employees_count')->nullable();
            $table->unsignedInteger('shareholders_count')->nullable();
            $table->decimal('liquidity', 15, 2)->nullable();
            $table->decimal('investments', 15, 2)->nullable();
            $table->decimal('financing', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('pension', 15, 2)->nullable();
            $table->json('opportunities')->nullable();
            $table->timestamps();
        });

        Schema::create('company_contact', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_contact');
        Schema::dropIfExists('companies');
    }
};
