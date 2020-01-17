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

use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\exceptions\InvalidCallException;
use hiapi\heppy\extensions\NamestoreExtension;
use hiapi\heppy\extensions\RGPExtension;
use hiapi\heppy\extensions\FeeExtension;
use hiapi\heppy\modules\AbstractModule;
use hiapi\heppy\modules\ContactModule;
use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\modules\HostModule;
use hiapi\heppy\modules\PollModule;

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
     * @return string
     */
    public function getRegistrar() : ?string
    {
        return (string) $this->data['registrar'];
    }

    /**
     * @param string $command
     * @param array $input
     * @param array $returns
     * @param array $payload
     * @return array
     */
    public function commonRequest(
        string $command,
        array $input,
        array $returns = [],
        array $payload = []
    ): array {
        $input = $this->applyExtensions($command, $input);
        $response = $this->request($command, $input);
        $rc = substr($response['result_code'] ?? '9999', 0, 1);
        if ($rc !== '1') {
            throw new EppErrorException('failed heppy request: ' . var_export($response, true));
        }
        $returns = $this->addCommonResponseFields($returns);

        $res = $payload;
        foreach ($returns as $apiName => $eppName) {
            if (key_exists($eppName, $response)) {
                $res[$apiName] = $response[$eppName];
            }
        }

        return array_filter($res);
    }

    protected function applyExtensions(string $command, array $input): array
    {
        foreach ($this->getExtensions() as $extension) {
            $input = $extension->apply($command, $input);
        }

        return $input;
    }

    private $extensions;

    protected function getExtensions(): array
    {
        if ($this->extensions === null) {
            $this->extensions = [
                new NamestoreExtension(),
                new RGPExtension(),
                new FeeExtension(),
            ];
        }

        return $this->extensions;
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
    public function getBase()
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

    /**
     * This method is for testing purpose only
     *
     * @param string $name
     * @param AbstractModule $module
     */
    public function setModule(string $name, AbstractModule $module): void
    {
        if (!key_exists($name, $this->modules)) {
            throw new InvalidCallException("module `$name` not found");
        }
        $this->modules[$name] = $module;
    }
}
