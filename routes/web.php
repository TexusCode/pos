<?php

use App\Http\Controllers\MergeProductController;
use App\Livewire\Audit;
use App\Livewire\Login;
use App\Livewire\Pos;
use App\Livewire\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sw.js', function (Request $request) {
    $swPath = public_path('sw.js');
    if (!file_exists($swPath)) {
        abort(404);
    }

    $basePath = rtrim($request->getBaseUrl(), '/');
    $allowedScope = $basePath === '' ? '/' : $basePath . '/';

    return response()->file($swPath, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Service-Worker-Allowed' => $allowedScope,
    ]);
})->name('pwa.sw');

Route::get('/offline.html', function () {
    $offlinePath = public_path('offline.html');
    if (!file_exists($offlinePath)) {
        abort(404);
    }

    return response()->file($offlinePath, [
        'Content-Type' => 'text/html; charset=utf-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('pwa.offline');

Route::get('/manifest.webmanifest', function (Request $request) {
    $manifestPath = public_path('manifest.webmanifest');
    if (!file_exists($manifestPath)) {
        abort(404);
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!is_array($manifest)) {
        abort(500);
    }

    $basePath = rtrim($request->getBaseUrl(), '/');
    $scope = $basePath === '' ? '/' : $basePath . '/';

    $manifest['start_url'] = $scope;
    $manifest['scope'] = $scope;

    return response(
        json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        200,
        [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ],
    );
})->name('pwa.manifest');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});
Route::middleware('auth')->group(function () {
    Route::get('/', Shift::class)->name('shift');
    Route::get('/pos', Pos::class)->name('pos');
    Route::get('/audit', Audit::class)->name('audit');
    Route::get('/merge', [MergeProductController::class, 'mergeAndSaveProductsBySku'])->name('merge');
});
