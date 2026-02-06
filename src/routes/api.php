<?php

use Illuminate\Http\Request;
use App\Messaging\RabbitPublisher;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FailedEventController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test-event', function (RabbitPublisher $publisher) {
    $publisher->publish('tickets.events', 'ticket.created', [
        'ticket_id' => rand(1, 100),
        'message' => 'Evento disparado',
        'created_at' => now()
    ]);

    return ['status' => 'event published'];
});
Route::get('/failed-events', [FailedEventController::class, 'index']);
Route::post('/failed-events/{id}/retry', [FailedEventController::class, 'retry']);
Route::delete('/failed-events/{id}', [FailedEventController::class, 'destroy']);
