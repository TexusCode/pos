<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PosActionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/shift/current', [ShiftController::class, 'current']);
        Route::post('/shift/open', [ShiftController::class, 'open']);
        Route::post('/shift/close', [ShiftController::class, 'close']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show'])
            ->whereNumber('product');
        Route::get('/products/by-sku/{sku}', [ProductController::class, 'bySku']);
        Route::post('/products/stock', [PosActionController::class, 'upsertProductStock']);

        Route::get('/carts', [CartController::class, 'index']);
        Route::post('/carts', [CartController::class, 'store']);
        Route::get('/carts/{cart}', [CartController::class, 'show'])->whereNumber('cart');
        Route::delete('/carts/{cart}', [CartController::class, 'destroy'])->whereNumber('cart');

        Route::post('/carts/{cart}/items', [CartController::class, 'addItem'])->whereNumber('cart');
        Route::patch('/carts/{cart}/items/{item}', [CartController::class, 'updateItem'])
            ->whereNumber('cart')
            ->whereNumber('item');
        Route::delete('/carts/{cart}/items/{item}', [CartController::class, 'removeItem'])
            ->whereNumber('cart')
            ->whereNumber('item');

        Route::post('/carts/{cart}/discount', [CartController::class, 'applyDiscount'])
            ->whereNumber('cart');
        Route::post('/carts/{cart}/checkout', [CartController::class, 'checkout'])
            ->whereNumber('cart');

        Route::post('/shift/expenses', [PosActionController::class, 'addExpense']);
        Route::get('/customers/by-phone/{phone}', [PosActionController::class, 'findCustomerByPhone']);
        Route::post('/debts/pay', [PosActionController::class, 'payDebt']);
    });
});
