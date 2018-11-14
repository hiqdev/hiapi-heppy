#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../vendor/yiisoft/yii2/Yii.php';

require_once __DIR__ . '/../../../../vendor/hiqdev/hiapi-directi/vendor/hiqdev/hiapi-legacy/src/lib/deps/data.php';

use hiapi\heppy\HeppyTool;

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
