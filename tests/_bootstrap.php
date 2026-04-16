<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

// Load the root project's Composer autoloader
$loader = require dirname(__DIR__, 3) . '/autoload.php';

// Register test namespace manually:
// autoload-dev of vendor packages is not included in the root project's autoloader
$loader->addPsr4('hiapi\\heppy\\tests\\', __DIR__);
