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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // ID заказа, обязательный
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // ID продукта, обязательный
            $table->integer('quantity'); // Количество продукта
            $table->decimal('price', 8, 2); // Цена за единицу продукта на момент заказа
            $table->decimal('discount', 8, 2); // Цена за единицу продукта на момент заказа
            $table->decimal('subtotal', 10, 2); // Промежуточная сумма для этой позиции
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
