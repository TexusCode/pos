<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3); // Генерируем уникальное название продукта
        return [
            'name' => $name,
            'quantity' => rand(1, 10),
            'sku' => rand(111111111, 999999999),
            'slug' => Str::slug($name), // Генерируем slug из названия
            'selling_price' => $this->faker->randomFloat(2, 10, 1000), // Цена продажи от 10 до 1000
            'status' => $this->faker->randomElement(['active', 'inactive', 'draft']), // Случайный статус
        ];
    }
}
