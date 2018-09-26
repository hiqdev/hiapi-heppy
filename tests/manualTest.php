#!/usr/bin/env php
<?php

require "_bootstrap.php";

use hiapi\heppy\ClientInterface;
use hiqdev\composer\config\Builder;
use yii\console\Application;

Yii::setAlias('@root', dirname(__DIR__));
Yii::$app = new Application(require Builder::path('tests'));

$client = Yii::$container->get(ClientInterface::class);

var_dump($client);die;
