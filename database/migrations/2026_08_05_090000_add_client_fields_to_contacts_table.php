<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('contacts', function (Blueprint $table): void { $table->string('client_type')->default('private')->after('status'); $table->json('tags')->nullable()->after('client_type'); $table->unsignedTinyInteger('relationship_score')->nullable()->after('relationship_level'); $table->foreignId('assigned_user_id')->nullable()->after('relationship_score')->constrained('users')->nullOnDelete(); }); } public function down(): void { Schema::table('contacts', function (Blueprint $table): void { $table->dropForeign(['assigned_user_id']); $table->dropColumn(['client_type','tags','relationship_score','assigned_user_id']); }); } };
