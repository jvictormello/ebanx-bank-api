<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ResetController;
use Illuminate\Support\Facades\Route;

Route::post('/reset', [ResetController::class, 'store']);
Route::get('/balance', [BalanceController::class, 'show']);
Route::post('/event', [EventController::class, 'store']);
