<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->string('noteable_type')->nullable(false)->change();
            $table->unsignedBigInteger('noteable_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->string('noteable_type')->nullable()->change();
            $table->unsignedBigInteger('noteable_id')->nullable()->change();
        });
    }
};
