<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show']);
Route::post('/install', [InstallController::class, 'run']);
