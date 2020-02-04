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
 * Fee class of EPP extension
 * Select right version of extension from urlns
 */
class IDNLangExtension extends AbstractExtension implements ExtensionInterface
{
    /** {@inheritdoc} */
    public $availableCommands = [
        'domain' => [
            'create' => ['*' => true],
        ],
    ];

    /** {@inheritdoc} */
    public function addExtension(string $command, array $data): array
    {
        $data = $this->clearData($data);
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
    protected function isDomainIDN(array $data): bool
    {
        return idn_to_utf8($data['name']) !== idn_to_ascii($data['name']);
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

    /**
     * Clear contacts from data
     *
     * @param array
     * @return array
     */
    protected function clearData(array $data): array
    {
        if ($this->isNamestoreAvailable() === false) {
            return $data;
        }

        foreach (['registrant', 'admin', 'tech', 'support', 'billing'] as $type) {
            unset($data[$type]);
        }

        return $data;
    }

    /**
     * Check is NamestoreExtensions available
     * Register of idn domain with NamestoreExtensions does not support contacts!
     *
     * @return bool
     */
    protected function isNamestoreAvailable(): bool
    {
        $extensions = $this->tool->getExtensions();
        return !empty($extensions['namestore']);
    }
}
