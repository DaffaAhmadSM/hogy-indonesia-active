<?php

use App\Http\Controllers\ProductWipMainController;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventInMainController;
use App\Http\Controllers\InventOutMainController;
use App\Http\Controllers\ProductBbMainController;
use App\Http\Controllers\ProductRejectController;

Route::get('/', function () {
    return view('main-layout');
})->name('home');

Route::group(['prefix' => 'report'], function () {

    Route::group(['prefix' => 'invent-in-main'], function () {
        Route::get('/', [InventInMainController::class, 'index'])->name('report.invent-in-main');
        Route::get('/export', [InventInMainController::class, 'export'])->name('report.invent-in-main.export');

        Route::group(['prefix' => 'hx'], function () {
            Route::get('/search', [InventInMainController::class, 'hxSearch'])->name('report.invent-in-main.search');
        });
    });

    Route::group(['prefix' => 'invent-out-main'], function () {
        Route::get('/', [InventOutMainController::class, 'index'])->name('report.invent-out-main');
        Route::get('/export', [InventOutMainController::class, 'export'])->name('report.invent-out-main.export');

        Route::group(['prefix' => 'hx'], function () {
            Route::get('/search', [InventOutMainController::class, 'hxSearch'])->name('report.invent-out-main.search');
        });
    });

    Route::group(['prefix' => 'product-wip-main'], function () {
        Route::get('/', [ProductWipMainController::class, 'index'])->name('report.product-wip-main');
        Route::get('/export', [ProductWipMainController::class, 'export'])->name('report.product-wip-main.export');

        Route::group(['prefix' => 'hx'], function () {
            Route::get('/search', [ProductWipMainController::class, 'hxSearch'])->name('report.product-wip-main.search');
        });
    });

    Route::group(['prefix' => 'product-bb-main'], function () {
        Route::get('/{type}', [ProductBbMainController::class, 'index'])->name('report.product-bb-main');
        Route::get('/export', [ProductBbMainController::class, 'export'])->name('report.product-bb-main.export');

        Route::group(['prefix' => 'hx'], function () {
            Route::get('{type}/search', [ProductBbMainController::class, 'hxSearch'])->name('report.product-bb-main.search');
        });
    });

    Route::group(['prefix' => 'product-reject-main'], function () {
        Route::get('/', [ProductRejectController::class, 'index'])->name('report.product-reject-main');
        Route::get('/export', [ProductRejectController::class, 'export'])->name('report.product-reject-main.export');

        Route::group(['prefix' => 'hx'], function () {
            Route::get('/search', [ProductRejectController::class, 'hxSearch'])->name('report.product-reject-main.search');
        });
    });

});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__ . '/auth.php';
