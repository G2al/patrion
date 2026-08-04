<?php

declare(strict_types=1);

use App\Enums\ContactStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('tax_code', 16)->nullable()->unique();
            $table->string('identity_document_type')->nullable();
            $table->string('identity_document_number')->nullable();
            $table->date('identity_document_expires_at')->nullable();
            $table->string('profession')->nullable();
            $table->string('marital_status')->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->text('residence')->nullable();
            $table->text('domicile')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default(ContactStatus::Prospect->value)->index();
            $table->date('first_contact_date')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('referred_by_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('priority')->default(Priority::Medium->value)->index();
            $table->decimal('potential_value', 15, 2)->nullable();
            $table->decimal('managed_assets', 15, 2)->nullable();
            $table->string('relationship_level')->nullable();
            $table->dateTime('last_contact_at')->nullable()->index();
            $table->dateTime('next_follow_up_at')->nullable()->index();
            $table->json('interests')->nullable();
            $table->json('personal_goals')->nullable();
            $table->string('personality_style')->nullable();
            $table->string('preferred_communication')->nullable();
            $table->string('contact_frequency')->nullable();
            $table->json('hobbies')->nullable();
            $table->text('family_information')->nullable();
            $table->json('birthdays')->nullable();
            $table->json('anniversaries')->nullable();
            $table->text('important_information')->nullable();
            $table->text('relationship_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
