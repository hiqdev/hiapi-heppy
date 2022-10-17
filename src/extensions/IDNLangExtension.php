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
class IDNLangExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public array $availableCommands = [
        'domain' => [
            'create' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        $language = $data['language'] ?? $this->detectLanguage($data['name']);
        $data['extensions'][] = array_filter([
            'command' => "idnLang",
            'language' => strtoupper($language ?? 'RUS'),
        ]);

        return $data;
    }

    /** {@inheritdoc} */
    public function isApplicable(string $command, array $data): bool
    {
        if (!parent::isApplicable($command, $data)) {
            return false;
        }

        return $this->isDomainIDN($this->findName($command, $data));
    }

    /**
     * Check is domain is IDN
     *
     * @param array
     * @return bool
     */
    protected function isDomainIDN(string $name): bool
    {
        return idn_to_utf8($name) !== idn_to_ascii($name);
    }

    /**
     * Detect language
     *
     * @param string
     * @return string
     */
    protected function detectLanguage(string $name = null): string
    {
        return LanguageHelper::getInstance()->detect($name);
    }

}
