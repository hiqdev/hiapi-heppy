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
class UAEppExtension extends AbstractExtension implements ExtensionInterface
{
    public array $availableCommands = [
        'domain_hm' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
        ],
        'host_hm' => [
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
        if (strpos($command, 'domain') !== false && empty($data['licence'])) {
            return $data;
        }

        $data['extensions'][] = [
            'command' => preg_replace(['/domain_hm/', '/host_hm/'], 'uaepp', $command),
            'license' => $data['license'] ?? null,
        ];

        return $data;
    }
}
