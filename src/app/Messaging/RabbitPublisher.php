<?php

namespace App\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class RabbitPublisher
{
    protected $channel;

    public function __construct()
    {
        $conn = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );

        $this->channel = $conn->channel();
    }

    public function publish(string $exchange, string $routingKey, array $payload)
    {
        $this->channel->exchange_declare($exchange, 'topic', false, true, false);

        $msg = new AMQPMessage(json_encode($payload), [
            'delivery_mode' => 2
        ]);

        $this->channel->basic_publish($msg, $exchange, $routingKey);
    }
}
