<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

return [
    'container' => [
        'singletons' => [
            'heppyTool' => [
                '__class' => \hiapi\heppy\HeppyTool::class,
            ],
            \hiapi\heppy\ClientInterface::class => [
                '__class' => \hiapi\heppy\RabbitMQClient::class,
                '__construct()' => [
                    [
                        'main' => [
                            'host'      => $params['heppy.rabbitmq.host'],
                            'port'      => $params['heppy.rabbitmq.port'],
                            'user'      => $params['heppy.rabbitmq.user'],
                            'password'  => $params['heppy.rabbitmq.password'],
                            'vhost'     => $params['heppy.rabbitmq.vhost'],
                            'queue'     => $params['heppy.rabbitmq.queue'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
