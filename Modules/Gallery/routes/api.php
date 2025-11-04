<?php

use Illuminate\Support\Facades\Route;
use Modules\Gallery\Http\Controllers\GalleryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('galleries', GalleryController::class)->names('gallery');
});
