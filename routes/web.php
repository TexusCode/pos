<?php

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
});
