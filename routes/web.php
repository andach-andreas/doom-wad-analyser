<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\WadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/logout', [HomeController::class, 'home'])->name('logout');

Route::get('/analyze', [WadController::class, 'analyze']);
Route::get('/map/{id}/image', [MapController::class, 'render']);
Route::get('/port', [PortController::class, 'index']);
