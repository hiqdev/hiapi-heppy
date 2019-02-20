<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

$singletons = [
    'heppyTool' => [
        '__class' => \hiapi\heppy\HeppyTool::class,
    ],
    \hiapi\heppy\ClientInterface::class => [
        '__class' => \hiapi\heppy\RabbitMQClient::class,
        '__construct()' => [
            'connections' => [
                'main' => [
                    'host'      => $params['hiapi.heppy.rabbitmq.host'],
                    'port'      => $params['hiapi.heppy.rabbitmq.port'],
                    'user'      => $params['hiapi.heppy.rabbitmq.user'],
                    'password'  => $params['hiapi.heppy.rabbitmq.password'],
                    'vhost'     => $params['hiapi.heppy.rabbitmq.vhost'],
                ],
            ],
            'queue' => $params['hiapi.heppy.rabbitmq.queue'],
        ],
    ],
];

return class_exists('Yii') ? ['container' => ['singletons' => $singletons]] : $singletons;
