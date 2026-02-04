<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class TicketCreatedWorker extends Command
{
    protected $signature = 'worker:ticket-created';
    protected $description = 'Consume ticket.created events';

    public function handle()
    {
        $conn = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );

        $channel = $conn->channel();

        $channel->exchange_declare('tickets.events', 'topic', false, true, false);
        $channel->queue_declare('notify.queue', false, true, false, false);
        $channel->queue_bind('notify.queue', 'tickets.events', 'ticket.created');

        $this->info('🟢 Waiting for ticket.created events...');

        $channel->basic_consume(
            'notify.queue',
            '',
            false,
            true,
            false,
            false,
            function ($msg) {
                $data = json_decode($msg->body, true);
                $this->info('📩 Received event: ' . json_encode($data));
            }
        );

        while (true) {
            $channel->wait();
        }
    }
}
