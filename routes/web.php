<?php

use App\Http\Controllers\MergeProductController;
use App\Livewire\Audit;
use App\Livewire\Login;
use App\Livewire\Pos;
use App\Livewire\Shift;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});
Route::middleware('auth')->group(function () {
    Route::get('/', Shift::class)->name('shift');
    Route::get('/pos', Pos::class)->name('pos');
    Route::get('/audit', Audit::class)->name('audit');
    Route::get('/merge', [MergeProductController::class, 'mergeAndSaveProductsBySku'])->name('merge');
});
