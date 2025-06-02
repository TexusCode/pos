<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->date('audit_date')->unique(); // Дата ревизии, уникальная для каждой даты
            $table->string('name')->nullable(); // Название/имя ревизии (например, "Ежемесячная ревизия May 2025")
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Пользователь, который провел ревизию
            $table->text('notes')->nullable(); // Дополнительные заметки к ревизии
            $table->text('status')->default('open'); // Дополнительные заметки к ревизии
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
