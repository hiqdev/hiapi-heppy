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
 * RGP class of EPP extension
 */
class RGPExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public $availableCommands = [
        'domain' => [
            'restore' => ['*' => true],
            'update' => ['restore' => true],
        ],
        'domain_hm' => [
            'restore' => ['*' => true],
            'update' => ['restore' => true],
        ],

    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data = []): array
    {
        $data['extensions'][] = array_filter($data['rgp']);
        unset($data['rgp']);
        return $data;
    }
}
