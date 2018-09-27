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
                            'host'      => $params['hiapi.heppy.rabbitmq.host'],
                            'port'      => $params['hiapi.heppy.rabbitmq.port'],
                            'user'      => $params['hiapi.heppy.rabbitmq.user'],
                            'password'  => $params['hiapi.heppy.rabbitmq.password'],
                            'vhost'     => $params['hiapi.heppy.rabbitmq.vhost'],
                        ],
                    ],
                    $params['hiapi.heppy.rabbitmq.queue'],
                ],
            ],
        ],
    ],
];
