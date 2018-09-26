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

/**
 * RabbitMQ Client
 */
class RabbitMQClient implements ClientInterface
{
    public function __construct(array $connections)
    {
        /// TODO: in new amqp lib should be like this
        /// $this->conn = AMQPStreamConnection::create_connection($connections);

        $config = reset($connections);
        extract($config);
        $this->conn = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }

    public function request(array $data): array
    {

    }
}
