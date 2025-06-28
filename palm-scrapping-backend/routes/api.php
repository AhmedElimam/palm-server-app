<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('products')->group(function () {
    Route::get('/fetch-apify', [ProductsController::class, 'fetchFromApifyGet'])->name('products.fetch-apify-get');
    Route::get('/fetch-jumia-apify', [ProductsController::class, 'fetchFromJumiaApify'])->name('products.fetch-jumia-apify');
    Route::get('/fetch-both-apis', [ProductsController::class, 'fetchFromBothApis'])->name('products.fetch-both-apis');
    Route::get('/', [ProductsController::class, 'index'])->name('products.index');
    Route::get('/platform/{platform}', [ProductsController::class, 'getByPlatform'])->name('products.by-platform');
    Route::post('/scrape', [ProductsController::class, 'scrape'])->name('products.scrape');
    Route::post('/scrape-multiple', [ProductsController::class, 'scrapeMultiple'])->name('products.scrape-multiple');
    Route::post('/fetch-apify', [ProductsController::class, 'fetchFromApify'])->name('products.fetch-apify');
    Route::post('/fetch-jumia-apify', [ProductsController::class, 'fetchFromJumiaApify'])->name('products.fetch-jumia-apify-post');
    Route::post('/fetch-both-apis', [ProductsController::class, 'fetchFromBothApis'])->name('products.fetch-both-apis-post');
    Route::get('/{id}', [ProductsController::class, 'show'])->name('products.show');
}); 