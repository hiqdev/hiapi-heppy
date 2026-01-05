<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

use hiqdev\yii\compat\yii;
use yii\web\Application;
use Yiisoft\Composer\Config\Builder;
use yii\di\Container;

defined('APP_TYPE') or define('APP_TYPE', 'tests');

$config = require Builder::path('tests');

error_reporting(E_ALL);


if (yii::is2()) {
    require_once __DIR__ . '/../../../yiisoft/yii2/Yii.php';
    \Yii::setAlias('@root', dirname(__DIR__, 4));
    \Yii::$app = new Application($config);
} else {
    \yii\helpers\Yii::setContainer(new Container($config));
}

