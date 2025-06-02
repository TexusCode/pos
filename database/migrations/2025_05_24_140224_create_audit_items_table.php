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
        Schema::create('audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade'); // ID ревизии
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // ID продукта
            $table->integer('old_quantity'); // Старое количество продукта до ревизии
            $table->integer('user_id'); // Старое количество продукта до ревизии
            $table->integer('new_quantity'); // Новое (фактическое) количество продукта после ревизии
            $table->integer('difference')->default(0); // Разница (new_quantity - old_quantity)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_items');
    }
};
