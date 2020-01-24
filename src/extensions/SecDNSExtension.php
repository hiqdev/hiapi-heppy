<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\extensions;

use hiapi\heppy\interfaces\ExtensionInterface;

/**
 * SecDNS class of EPP extension
 */
class SecDNSExtension extends AbstractExtension implements ExtensionInterface
{
    public $availableCommands = [
        'domain' => [
            'update' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        return $data;
    }
}
