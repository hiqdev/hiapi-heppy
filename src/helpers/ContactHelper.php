<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\helpers;

/**
 * LanguageHelper
 * Find right language code for domain name
 */
final class ContactHelper
{
    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var array $_disabled: key - zone, value - bool or array of disabled contacts
     */
    private array $_disabled = [
        'com' => true,
        'net' => true,
        'cc' => true,
        'tv' => true,
        'by' => ['tech'],
        'ua' => ['registrant', 'billing'],
        'com.ua' => ['billing'],
        'kiev.ua' => ['billing'],
        'kyiv.ua' => ['billing'],
    ];

    /**
     * Gets the instance via lazy initialization (created on first usage)
     */
    public static function create(array $config = []): self
    {
        if (static::$instance === null) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }

    /**
     * Check if contact support zone
     *
     * @param string
     * @param string
     * @param ?array
     * @return bool
     */
    public static function check(string $zone, string $type, array $config = []): bool
    {
        $checker = self::create($config);
        return $checker->isSupported($zone, $type);
    }

    /**
     * Check if contact support zone
     *
     * @param string
     * @param string
     * @return bool
     */
    public function isSupported($zone, $type) : bool
    {
        if (empty($this->_disabled[$zone])) {
            return true;
        }

        if (is_bool($this->_disabled[$zone]) && $this->_disabled[$zone] === true) {
            return false;
        }

        if (is_array($this->_disabled[$zone]) && in_array($type, $this->_disabled[$zone])) {
            return false;
        }

        return true;
    }

    private function __construct(array $config = [])
    {
        $this->_disabled = array_merge($this->_disabled, $config);
    }
}
