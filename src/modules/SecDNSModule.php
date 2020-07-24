<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\modules;

use hiapi\heppy\exceptions\EppErrorException;
use arr;
use err;

class SecDNSModule extends AbstractModule
{
    public $extURIs = [
        'secDNS' => 'urn:ietf:params:xml:ns:secDNS-1.1',
        'secDNS_hm' => 'http://hostmaster.ua/epp/secDNS-1.1',
    ];
    /**
    * List of available extensions
    */
    protected $availableExtension = [
        'secDNS' => '1.1',
        'secDNS_hm' => 'HostMaster',
    ];

    /** {@inheritdoc} */
    public $uris = [
        'domain' => 'urn:ietf:params:xml:ns:domain-1.0',
        'domain_hm' => 'http://hostmaster.ua/epp/domain-1.1',
    ];

    protected $extension;

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $exts = $this->tool->getExtensions();
        foreach ($extURIs as $obj => $uri) {
            if (!empty($exts[$obj])) {
                $this->extension = $obj;
                return $this;
            }
        }

        return $this;
    }

    /**
     * Set SecDNS refresh
     *
     * @param array $row
     * @reurn array
     */
    public function secdnsChange(array $row): array
    {
        $this->isSecDNSAvailable();
        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'chg',
                'xmlns' => $this->extension,
            ]),
        ]), [], [
            'domain'    => $row['domain'],
        ]);
    }

    /**
     * Create SecDNS record
     *
     * @param array $row
     * @return array
     */
    public function secdnsCreate(array $row): array
    {
        $this->isSecDNSAvailable();
        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'add',
                'xmlns' => $this->extension,
            ]),
        ]), [], [
            'domain'    => $row['domain'],
        ]);
    }

    /**
     * Remove SecDNS record
     *
     * @param array $row
     * @return array
     */
    public function secdnsDelete(array $row): array
    {
        $this->isSecDNSAvailable();
        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'rem',
                'xmlns' => $this->extension,
            ]),
        ]), [], [
            'domain'    => $row['domain'],
        ]);
    }

    /**
     * @return bool
     * @throw EppErrorException
     */
    protected function isSecDNSAvailable(): bool
    {
        if ($this->extension) {
            return true;
        }

        throw new EppErrorException('SecDNS not provided by registry');
    }
}
