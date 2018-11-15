#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__  . '/../vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/vendor/hiqdev/hiapi-legacy/src/lib/deps/data.php';

use hiapi\heppy\HeppyTool;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$heppyTool = new HeppyTool(new stdClass(), [
    'queue'     => $_ENV['HEPPY_QUEUE'],
    'url'       => $_ENV['HEPPY_URL'],
    'login'     => $_ENV['HEPPY_LOGIN'],
    'password'  => $_ENV['HEPPY_PASSWORD'],
]);

$heppyTool->domainTransfer([
    'domain'    => 'silverfires1.me',
    'password'  => 'adf-AA01',
    'period'    => 1,
    'roid'      => 'D425500000000823001-AGRS',
]);
