<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\WadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/logout', [HomeController::class, 'home'])->name('logout');

Route::get('/analyze', [WadController::class, 'analyze']);
Route::get('/install/{id}', [InstallController::class, 'show'])->name('install.show');
Route::get('/install/{id}/extract', [InstallController::class, 'extract'])->name('install.extract');
Route::get('/map/{id}/image', [MapController::class, 'render']);
Route::get('/port', [PortController::class, 'index'])->name('port.index');
Route::get('/port/{id}', [PortController::class, 'show'])->name('port.show');;
