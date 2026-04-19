<?php

use Illuminate\Support\Facades\Route;

// Catch-all route — the Vue SPA handles all frontend routing
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
