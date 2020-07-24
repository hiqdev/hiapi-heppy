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
        if (empty($data['secDNS'])) {
            return $data;
        }

        $row = $data['secDNS'];

        $version = $row['xmlns'] ?? null;

        if (empty($version)) {
            return $data;
        }

        $data['extensions'][] = [
            'command' => strpos($command, ':create') !== false ? "{$version}:create" : "{$version}:update",
            $row['command'] => array_filter([
                'maxSigLife' => $row['max_sig_life'],
                'keyTag' => $row['key_tag'],
                'keyAlg' => $row['key_alg'],
                'digestAlg' => $row['digest_alg'],
                'digest' => $row['digest'],
                'digestType' => $row['digest_type'],
                'pubKey' => $row['pub_key'],
            ]),
        ];

        return $data;
    }
}
