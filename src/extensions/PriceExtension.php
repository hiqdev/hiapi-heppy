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
class PriceExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public array $availableCommands = [
        'domain' => [
            'check' => ['*' => true],
            'create' => ['*' => true],
            'renew' => ['*' => true],
            'transfer' => ['request' => true, 'query' => 'true'],
            // 'update' => ['restore' => true],
            // 'restore' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        if ($data['withoutExt'] === true) {
            return $data;
        }

        $d = [];
        foreach ($data['extensions'] ?? [] as $id => $extension) {
            if (strpos($extension['command'], 'fee') !== false) {
                unset($data[$id]);
                continue;
            }

            $d[] = $extension;
        }

        $data['extensions'] = $d;
        $data['extensions'][] = array_filter([
            'command' => "price:" .  substr($command, 7),
            'name' => $data['name'] ?? reset($data['names']),
            'currency' => $this->tool->getCurrency() ?? 'USD',
            'fee' => $data['fee'] ?? null,
            'period' => $data['period'] ?? ($data['amount'] ?? 1),
        ]);

        return $data;
    }
}
