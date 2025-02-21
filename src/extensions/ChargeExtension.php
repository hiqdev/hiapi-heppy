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
class ChargeExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public array $availableCommands = [
        'domain' => [
            'create' => ['*' => true],
            'renew' => ['*' => true],
            'transfer' => ['request' => true, 'query' => 'true'],
            'update' => ['restore' => true],
            'restore' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        if (isset($data['withoutExt']) && $data['withoutExt'] === true) {
            return $data;
        }

        if (empty($data['fee'])) {
            return $data;
        }

        $data['extensions'][] = array_filter([
            'command' => "charge:" .  substr($command, 7),
            'amount' => $data['fee'] ?? null,
            'period' => $data['period'] ?? ($data['amount'] ?? 1),
            'category_name' => $data['category_name'],
        ]);

        return $data;
    }
}
