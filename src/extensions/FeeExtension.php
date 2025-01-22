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
    public array $availableCommands = [
        'domain' => [
            'check' => ['*' => true],
            'create' => ['*' => true],
            'renew' => ['*' => true],
            'transfer' => ['request' => true, 'query' => true],
            // 'update' => ['restore' => true],
            // 'restore' => ['*' => true],
        ],
    ];

    public array $unsupportedExtensions = ['price'];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        if (empty($this->version)) {
            return $data;
        }

        if (isset($data['withoutExt']) && $data['withoutExt'] === true) {
            return $data;
        }

        foreach ($data['extensions'] ?? [] as $ext) {
            if (strpos($ext['command'], 'price') !== false) {
                return $data;
            }
        }

        if ($command !== 'domain:check' && empty($data['fee'])) {
            return $data;
        }

        $data['extensions'][] = array_filter([
            'command' => "fee{$this->version}:" . substr($command, 7),
            'name' => $data['name'] ?? reset($data['names']),
            'currency' => strtoupper($this->tool->getCurrency() ?? 'USD'),
            'fee' => $data['fee'] ?? null,
            'period' => $data['period'] ?? ($data['amount'] ?? 1),
            'action' => $data['fee-action'] ?? ($command === 'domain:check' ? 'create' : substr($command, 7)),
        ]);

        return $data;
    }
}
