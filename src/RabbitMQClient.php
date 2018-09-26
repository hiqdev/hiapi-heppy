<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ Client
 */
class RabbitMQClient implements ClientInterface
{
    protected $connection;

    protected $channel;

    protected $callback_queue;

    protected $correlation_id;

    protected $reply;

    public function __construct(array $connections)
    {
        /// TODO: in new amqp lib should be like this
        /// $this->connection = AMQPStreamConnection::create_connection($connections);

        $config = reset($connections);
        extract($config);
        $this->queue = $queue;
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
        $this->callback_queue = $this->startCallbackQueue($this->channel);
    }

    protected function startCallbackQueue($channel)
    {
        [$callback_queue, ,] = $channel->queue_declare(
            '',
            false,
            false,
            true,
            false
        );
        $channel->basic_consume(
            $callback_queue,
            '',
            false,
            false,
            false,
            false,
            [$this, 'onResponse']
        );

        return $callback_queue;
    }

    public function onResponse(AMQPMessage $message)
    {
        if ($message->get('correlation_id') === $this->correlation_id) {
            $this->reply = $message->body;
        }
    }

    public function request(array $data): array
    {
        $this->correlation_id = uniqid();

        $query = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $msg = new AMQPMessage($query, [
            'correlation_id'    => $this->correlation_id,
            'reply_to'          => $this->callback_queue,
        ]);
        $this->channel->basic_publish($msg, '', $this->queue);

        $this->reply = null;
        while (!$this->reply) {
            $this->channel->wait();
        }

        return json_decode($this->reply, true);
    }
}
