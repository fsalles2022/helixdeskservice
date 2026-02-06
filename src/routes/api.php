<?php

use App\Http\Controllers\FailedEventController;
use App\Messaging\RabbitPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/test-event', function (RabbitPublisher $publisher) {
    $publisher->publish('tickets.events', 'ticket.created', [
        'ticket_id' => rand(1, 100),
        'message' => 'Evento disparado',
        'created_at' => now()
    ]);

    return ['status' => 'event published'];
});

Route::prefix('failed-events')->group(function () {
    Route::get('/', [FailedEventController::class, 'index']);
    Route::get('/stats', [FailedEventController::class, 'stats']);
    Route::post('/{id}/retry', [FailedEventController::class, 'retry']);
    Route::delete('/{id}', [FailedEventController::class, 'destroy']);

    Route::get('/dlq/charts', [FailedEventController::class, 'charts']);
});
