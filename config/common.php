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
        ],
    ],
];
