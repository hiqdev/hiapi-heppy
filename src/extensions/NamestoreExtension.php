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
 * Namestore class of EPP extension
 */
class NamestoreExtension extends AbstractExtension implements ExtensionInterface
{
    public $availableCommands = [
        'domain' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
            'check' => ['*' => true],
            'info' => ['*' => true],
            'transfer' => ['*' => true],
            'delete' => ['*' => true],
        ],
    ];

    /**
     * @param array $data
     * @param string|null $zone
     * @return array
     */
    public function addExtension(string $command, array $data): array
    {
        $zone = $this->findZone($command, $data);
        if (empty($zone)) {
            return $data;
        }

        $zone = mb_strtoupper($zone);
        $data['extensions'][] = [
            'command' => 'namestoreExt',
            'subProduct' => "dot{$zone}",
        ];

        return $data;
    }
}
