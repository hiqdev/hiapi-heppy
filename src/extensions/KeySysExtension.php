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
 * Fee class of EPP extension
 * Select right version of extension from urlns
 */
class KeySysExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public array $availableCommands = [
        'contact' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
        ],
        'domain' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
            'delete' => ['*' => true],
        ],
    ];

    public function addExtension(string $command, array $data): array
    {
        if (empty($data['keysys'])) {
            return $data;
        }

        $data['extensions'][] = $data['keysys'];
        return $data;
    }
}

