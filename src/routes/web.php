<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\FailedEventController;

Route::get('/admin/dlq', [FailedEventController::class, 'page'])->name('dlq.page');

