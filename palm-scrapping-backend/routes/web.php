<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('api/products')->group(function () {
    Route::get('/', [ProductsController::class, 'index'])->name('products.index');
    Route::get('/{id}', [ProductsController::class, 'show'])->name('products.show');
    Route::post('/scrape', [ProductsController::class, 'scrape'])->name('products.scrape');
    Route::post('/scrape-multiple', [ProductsController::class, 'scrapeMultiple'])->name('products.scrape-multiple');
    Route::post('/fetch-apify', [ProductsController::class, 'fetchFromApify'])->name('products.fetch-apify');
});
