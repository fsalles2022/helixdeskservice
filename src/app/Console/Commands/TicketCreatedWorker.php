<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use App\Models\FailedEvent;

class TicketCreatedWorker extends Command
{
    protected $signature = 'worker:ticket-created';
    protected $description = 'Consume ticket.bug.at.userdata events with retry + DLQ';

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

        // Exchanges
        $channel->exchange_declare('tickets.events', 'topic', false, true, false);
        $channel->exchange_declare('tickets.retry', 'topic', false, true, false);

        // Queues
        $channel->queue_declare(
            'notify.queue',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => 'tickets.retry'
            ])
        );

        $channel->queue_declare(
            'retry.queue',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-message-ttl' => 5000,
                'x-dead-letter-exchange' => 'tickets.events'
            ])
        );

        $channel->queue_bind('notify.queue', 'tickets.events', 'ticket.bug.at.userdata');
        $channel->queue_bind('retry.queue', 'tickets.retry', 'ticket.bug.at.userdata');

        $this->info('🟢 Waiting for ticket.bug.at.userdata events...');

        $channel->basic_consume(
            'notify.queue',
            '',
            false,
            false,
            false,
            false,
            function ($msg) use ($channel) {

                try {
                    $data = json_decode($msg->body, true);

                    // FORÇA ERRO (teste)
                    throw new \Exception("Simulated failure");
                    // Exemplo de processamento real
                    //if (!isset($data['ticket_id'])) {
                      //  throw new \Exception("Invalid payload");
                    //}

                    $this->info("🎫 Ticket {$data['ticket_id']} processed successfully");
                } catch (\Throwable $e) {

                    $headers = $msg->has('application_headers')
                        ? $msg->get('application_headers')->getNativeData()
                        : [];

                    $attempts = (int) ($headers['x-attempts'] ?? 0);
                    $attempts++;

                    if ($attempts < 3) {
                        $this->error("❌ Fail #{$attempts} → retry");

                        $headers['x-attempts'] = $attempts;

                        $channel->basic_publish(
                            new AMQPMessage(
                                $msg->body,
                                [
                                    'application_headers' => new AMQPTable($headers),
                                    'delivery_mode' => 2
                                ]
                            ),
                            'tickets.retry',
                            'ticket.bug.at.userdata'
                        );
                    } else {
                        $this->warn("☠️ Saved to DLQ");

                        FailedEvent::create([
                            'routing_key' => 'ticket.bug.at.userdata',
                            'payload' => json_encode($data ?? ['raw' => $msg->body]),
                            'error' => $e->getMessage(),
                            'attempts' => $attempts
                        ]);
                    }
                } finally {
                    $channel->basic_ack($msg->getDeliveryTag());
                }
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
