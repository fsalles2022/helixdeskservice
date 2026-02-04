<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedEvent extends Model
{

    protected $fillable = [
        'exchange',
        'routing_key',
        'payload',
        'error',
        'attempts'
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
