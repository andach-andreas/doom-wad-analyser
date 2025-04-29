<?php

use App\Http\Controllers\WadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/analyze', [WadController::class, 'analyze']);
