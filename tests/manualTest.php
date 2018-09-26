#!/usr/bin/env php
<?php

require "_bootstrap.php";

use hiapi\heppy\ClientInterface;

$client = Yii::$container->get(ClientInterface::class);

$res = $client->request([
    'command'       => 'domain:info',
    'name'          => 'ahnames.com',
    'clTRID'        => 'TEST-00001',
    'extensions'    => [
        'first' => 'namestoreExt:subProduct',
    ],
    'subProduct'    => 'COM',
]);

var_dump($res);die;
