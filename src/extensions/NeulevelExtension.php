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
use hiapi\heppy\helpers\LanguageHelper;

/**
 * IDNLang class of EPP extension
 */
class NeulevelExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public array $availableCommands = [
        'domain' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
        ],
        'contact' => [
            'create' => ['*' => true],
            'update' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        if (empty($data['neulevel'])) {
            return $data;
        }
        $data['extensions'][] = [
            'command' => "neulevel{$this->version}:default",
            'neulevel' => $data['neulevel'],
        ];

        return $data;
    }
}
