<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection; // Используем коллекцию Laravel

class MergeProductController extends Controller
{
    function mergeAndSaveProductsBySku(): Collection
    {
        // Шаг 1: Получаем все товары из базы данных
        $allProducts = Product::all();

        $productsToKeep = [];      // Ассоциативный массив: SKU => Product Model (который будет обновлен)
        $productIdsToDelete = [];  // Массив ID дубликатов для удаления

        // Шаг 2: Группируем товары по SKU и определяем, что сохранить/удалить
        foreach ($allProducts as $product) {
            $sku = $product->sku;

            // Обрабатываем только товары с непустым SKU
            if (!empty($sku)) {
                if (array_key_exists($sku, $productsToKeep)) {
                    // Если товар с таким SKU уже есть в списке для сохранения,
                    // суммируем его количество.
                    // Важно: quantity в вашей таблице varchar, поэтому приводим к int для суммирования,
                    // а затем обратно к string для сохранения.
                    $productsToKeep[$sku]->quantity = (string) ((int) $productsToKeep[$sku]->quantity + (int) $product->quantity);

                    // Добавляем ID текущего (дублирующего) товара в список на удаление
                    $productIdsToDelete[] = $product->id;
                } else {
                    // Если это первый товар с таким SKU, добавляем его в список для сохранения.
                    // Его quantity будет обновлено позже, если найдутся дубликаты.
                    $productsToKeep[$sku] = $product;
                    // Название и selling_price остаются от этой первой записи.
                }
            }
            // Товары без SKU (sku = null или '') не обрабатываются здесь для объединения/удаления.
            // Они останутся в базе данных как есть.
        }

        // Шаг 3: Выполняем операции с базой данных в транзакции
        // Транзакции гарантируют, что либо все операции будут выполнены успешно,
        // либо ни одна из них не будет применена (в случае ошибки).
        DB::beginTransaction();
        try {
            // Обновляем количество для объединенных товаров
            foreach ($productsToKeep as $sku => $productModel) {
                // Сохраняем модель. Laravel автоматически обновит поле `updated_at`.
                $productModel->save();
            }

            // Удаляем дубликаты из базы данных
            if (!empty($productIdsToDelete)) {
                Product::whereIn('id', $productIdsToDelete)->delete();
            }

            // Если все операции прошли успешно, фиксируем транзакцию
            DB::commit();

            // Шаг 4: Возвращаем обновленную коллекцию товаров из базы данных
            // Это гарантирует, что возвращаемые данные точно отражают текущее состояние БД.
            return Product::all();

        } catch (\Exception $e) {
            // В случае любой ошибки откатываем транзакцию, чтобы избежать частичных изменений
            DB::rollBack();
            // Можно логировать ошибку для отладки:
            // \Log::error("Ошибка при объединении и сохранении товаров по SKU: " . $e->getMessage());
            // Перебрасываем исключение, чтобы вызывающий код мог его обработать
            throw $e;
        }

        return redirect()->route('login');
    }
}
