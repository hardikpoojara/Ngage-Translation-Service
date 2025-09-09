<?php

use App\Http\Controllers\Api\ContentTranslationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('translations', ContentTranslationController::class)
    ->only(['index', 'store', 'show']);
