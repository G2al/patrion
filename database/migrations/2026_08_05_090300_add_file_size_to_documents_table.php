<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('documents', function (Blueprint $table): void { $table->unsignedBigInteger('file_size')->nullable()->after('file_path'); }); } public function down(): void { Schema::table('documents', fn (Blueprint $table) => $table->dropColumn('file_size')); } };
