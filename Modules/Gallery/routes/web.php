<?php

use Illuminate\Support\Facades\Route;
use Modules\Gallery\App\Http\Controllers\GalleryController;

Route::middleware(['auth'])->controller(GalleryController::class)->prefix('ajax/gallery')->name('ajax.gallery.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/all', 'list')->name('all');
    Route::post('/store', 'store')->name('store');
    Route::get('/destroy/{id}', 'destroy')->name('destroy');
});