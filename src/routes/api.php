<?php

use Illuminate\Http\Request;
use App\Messaging\RabbitPublisher;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test-event', function (RabbitPublisher $publisher) {
    $publisher->publish('tickets.events', 'ticket.created', [
        'ticket_id' => rand(1,100),
        'message' => 'Evento disparado',
        'created_at' => now()
    ]);

    return ['status' => 'event published'];
});
