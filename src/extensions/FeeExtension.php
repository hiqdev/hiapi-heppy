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
class FeeExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public $availableCommands = [
        'domain' => [
            'check' => ['*' => true],
            'create' => ['*' => true],
            'renew' => ['*' => true],
            'transer' => ['request' => true, 'query' => 'true'],
            // 'update' => ['restore' => true],
            // 'restore' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        if (empty($this->version)) {
            return $data;
        }

        $data['extensions'][] = array_filter([
            'command' => "fee{$this->version}:" . substr($command, 7),
            'name' => $data['name'] ?? reset($data['names']),
            'currency' => $this->tool->getCurrency ?? 'USD',
            'fee' => $data['fee'],
            'period' => $data['period'],
            'action' => $data['fee-action'] ?? ($command === 'domain:renew' ? 'renew' : 'create'),
        ]);

        return $data;
    }
}
