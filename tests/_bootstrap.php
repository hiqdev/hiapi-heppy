<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

$vendorDir = is_dir(dirname(__DIR__) . '/vendor')
    ? dirname(__DIR__) . '/vendor'
    : dirname(__DIR__, 3);

$loader = require $vendorDir . '/autoload.php';
require_once $vendorDir . '/yiisoft/yii2/Yii.php';

$loader->addPsr4('hiapi\\heppy\\tests\\', __DIR__);
