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

class SecDNSModule extends DomainModule
{
    /**
    * List of available extensions
    */
    protected $availableExtension = [
        'secDNS' => '1.1',
        'secDNS10' => '1.0',
        'secDNSUA' => 'HostMaster',
    ];

    /**
     * Set SecDNS refresh
     *
     * @param array $row
     * @reurn array
     */
    public function secdnsChange(array $row): array
    {
        $this->isSecDNSAvailable();
        return $this->tool->commonRequest('domain:update', array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'chg',
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
        return $this->tool->commonRequest('domain:update', array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'add',
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
        return $this->tool->commonRequest('domain:update', array_filter([
            'name'      => $row['domain'],
            'secDNS'    => array_merge($row, [
                'command' => 'rem',
            ]),
        ]), [], [
            'domain'    => $row['domain'],
        ]);
    }

    /**
     * @return bool|| Raiser EppErrorException
     */
    protected function isSecDNSAvailable(): bool
    {
        $extensions = $this->tool->getExtensions();
        foreach ($this->availableExtension as $key => $version) {
            if (!empty($extensions[$key])) {
                return true;
            }
        }

        throw new EppErrorException('SecDNS not provided by registry');
    }
}
