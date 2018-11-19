<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy;

use hiapi\heppy\exceptions\InvalidCallException;
use hiapi\heppy\modules\AbstractModule;
use hiapi\heppy\modules\ContactModule;
use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\modules\HostModule;

/**
 * hEPPy tool.
 */
class HeppyTool
{
    private $_client;

    private $base;

    private $defaultNss = ['ns1.topdns.me', 'ns2.topdns.me'];

    private $data;

    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
        'poll'      => PollModule::class,
        'polls'     => PollModule::class,
    ];

    public function __construct($base, $data)
    {
        $this->base = $base;
        $this->data = $data;
    }

    public function __call($command, $args): array
    {
        $parts = preg_split('/(?=[A-Z])/', $command);
        $entity = reset($parts);
        $module = $this->getModule($entity);

        return call_user_func_array([$module, $command], $args);
    }

    /**
     * @param string $name
     * @return AbstractModule
     */
    public function getModule(string $name): AbstractModule
    {
        if (empty($this->modules[$name])) {
            throw new InvalidCallException("module `$name` not found");
        }
        $module = $this->modules[$name];
        if (!is_object($module)) {
            $this->modules[$name] = $this->createModule($module);
        }

        return $this->modules[$name];
    }

    /**
     * @param string $class
     * @return AbstractModule
     */
    public function createModule(string $class): AbstractModule
    {
        return new $class($this);
    }

    /**
     * @return array
     */
    public function getDefaultNss(): array
    {
        return $this->defaultNss;
    }

    /**
     * @param string $command
     * @param array $input
     * @param array $returns
     * @return array
     */
    public function commonRequest(string $command, array $input, array $returns = []): array
    {
        $response = $this->request($command, $input);
        $returns = $this->addCommonResponseFields($returns);

        $res = [];
        foreach ($returns as $apiName => $eppName) {
            if (key_exists($eppName, $response)) {
                $res[$apiName] = $eppName instanceof \Closure
                    ? $eppName($response)
                    : $response[$eppName];
            }
        }

        return array_filter($res);
    }

    /**
     * @param string $command
     * @param array $data
     * @return array
     */
    public function request(string $command, array $data): array
    {
        $data['command'] = $command;

        return $this->getClient()->request($data);
    }

    /**
     * @param array $returns
     * @return array
     */
    private function addCommonResponseFields(array $returns): array
    {
        return array_merge($returns, [
            'result_msg'    => 'result_msg',
            'result_code'   => 'result_code',
            'result_lang'   => 'result_lang',
            'result_reason' => 'result_reason',
            'server_trid'   => 'svTRID',
            'client_trid'   => 'clTRID',
        ]);
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        if ($this->_client === null) {
            $this->_client = new RabbitMQClient([
                [
                    'host'      => $this->data['url']       ?? null,
                    'port'      => $this->data['port']      ?? 5672,
                    'user'      => $this->data['login']     ?? 'guest',
                    'password'  => $this->data['password']  ?? 'guest',
                    'vhost'     => $this->data['vhost']     ?? '/',
                ],
            ], $this->data['queue'] ?? null);
        }

        return $this->_client;
    }

    /**
     * @return \mrdpBase
     */
    public function getBase(): \mrdpBase
    {
        return $this->base;
    }

    /**
     * @param $client
     */
    public function setClient($client): void
    {
        $this->_client = $client;
    }
}
