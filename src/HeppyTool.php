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
use hiapi\heppy\extensions\AbstractExtension;
use hiapi\heppy\extensions\NamestoreExtension;
use hiapi\heppy\extensions\RGPExtension;
use hiapi\heppy\extensions\FeeExtension;
use hiapi\heppy\extensions\SecDNSExtension;
use hiapi\heppy\extensions\IDNLangExtension;
use hiapi\heppy\extensions\PriceExtension;
use hiapi\heppy\extensions\ChargeExtension;
use hiapi\heppy\extensions\KeySysExtension;
use hiapi\heppy\extensions\NeulevelExtension;
use hiapi\heppy\modules\AbstractModule;
use hiapi\heppy\modules\ContactModule;
use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\modules\SecDNSModule;
use hiapi\heppy\modules\HostModule;
use hiapi\heppy\modules\PollModule;
use hiapi\heppy\modules\EPPModule;
use hiapi\heppy\modules\BalanceModule;

use PhpAmqpLib\Exception\AMQPNoDataException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Exception\AMQPChannelClosedException;

use DateTimeImmutable;
use DateTimeZone;
use DateTime;

/**
 * hEPPy tool.
 */
class HeppyTool
{
    private $_client;

    private $base;

    private $defaultNss = ['ns1.topdns.me', 'ns2.topdns.me'];

    private $data;

    protected $ns;

    protected $objects;

    protected $contacts = [];

    protected $timezone;

    protected $extURNNames = [
        'secDNS' => 'urn:ietf:params:xml:ns:secDNS-1.1',
        'secDNShm' => ['http://hostmaster.ua/epp/secDNS-1.1', 'hm'],
        'rgp' => 'urn:ietf:params:xml:ns:rgp-1.0',
        'launch' => 'urn:ietf:params:xml:ns:launch-1.0',
        'idn' => 'urn:ietf:params:xml:ns:idn-1.0',
        'verificationCode' => 'urn:ietf:params:xml:ns:verificationCode-1.0',
        'price' => ['urn:ar:params:xml:ns:price-1.1'],
        'charge' => ['http://www.unitedtld.com/epp/charge-1.0'],
        'fee05' => ['urn:ietf:params:xml:ns:fee-0.5', 'version' => '05'],
        'fee06' => ['urn:ietf:params:xml:ns:fee-0.6', 'version' => '06'],
        'fee07' => ['urn:ietf:params:xml:ns:fee-0.7', 'version' => '07'],
        'fee08' => ['urn:ietf:params:xml:ns:fee-0.8', 'version' => '08'],
        'fee09' => ['urn:ietf:params:xml:ns:fee-0.9', 'version' => '09'],
        'fee11' => ['urn:ietf:params:xml:ns:fee-0.11','version' => '11'],
        'fee21' => ['urn:ietf:params:xml:ns:fee-0.21','version' => '21'],
        'coa' => 'urn:ietf:params:xml:ns:coa-1.0',
        'idnLang' => 'http://www.verisign.com/epp/idnLang-1.0',
        'premiumdomain' => 'http://www.verisign.com/epp/premiumdomain-1.0',
        'namestoreExt' => 'http://www.verisign-grs.com/epp/namestoreExt-1.1',
        'neulevel' => 'urn:ietf:params:xml:ns:neulevel',
        'neulevel10' => ['urn:ietf:params:xml:ns:neulevel-1.0', 'version' => '10'],
        'keysys' => 'http://www.key-systems.net/epp/keysys-1.0',
    ];

    /**
     * List of available extensions classses
     * @var array
     */
    private $extURNClasses = [
        'secDNS' => SecDNSExtension::class,
        'secDNShm' => SecDNSExtension::class,
        'rgp' => RGPExtension::class,
        'namestoreExt' => NamestoreExtension::class,
        'fee05' => FeeExtension::class,
        'fee06' => FeeExtension::class,
        'fee07' => FeeExtension::class,
        'fee08' => FeeExtension::class,
        'fee09' => FeeExtension::class,
        'fee11' => FeeExtension::class,
        'fee21' => FeeExtension::class,
        'charge' => ChargeExtension::class,
        'idnLang' => IDNLangExtension::class,
        'price' => PriceExtension::class,
        'keysys' => KeySysExtension::class,
        'neulevel10' => NeulevelExtension::class,
        'neulevel' => NeulevelExtension::class,
    ];

    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
        'secdns'    => SecDNSModule::class,
        'secdnss'   => SecDNSModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
        'poll'      => PollModule::class,
        'polls'     => PollModule::class,
        'epp'       => EPPModule::class,
        'balance'   => BalanceModule::class,
    ];

    /**
     * List of enabled extensions
     * @var array of AbstractExtension
     */
    private $extensions;

    /**
     * @var array
     */
    private $helloData;

    private $cache;

    public function __construct($base, $data)
    {
        $this->base = $base;
        $this->data = $data;
        $this->contacts = $this->data['contacts'] ?? [];
        $this->cache = $base->getCache();
        $this->timezone = $this->data['timezone'] ?? null;
    }

    public function __call($command, $args): array
    {
        $parts = preg_split('/(?=[A-Z])/', $command);
        $entity = reset($parts);
        $module = $this->getModule($entity);

        if (!method_exists($module, $command)) {
            throw new InvalidCallException("command `$command` not found");
        }

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

    public function getRegistrar() : ?string
    {
        return (string) $this->data['registrar'];
    }

    public function getContract() : ?string
    {
        return (string) $this->data['contract'];
    }

    public function getMinBalance() : ?float
    {
        return (float) $this->data['balancelimit'];
    }

    /**
     * @return string
     */
    public function getCurrency() : string
    {
        return (string) ($this->data['currency'] ?? 'USD');
    }

    /**
     * @param string
     *
     * @return [[AbstractExtension]]
     */
    public function createExtension(string $class, array $data = []): AbstractExtension
    {
        return new $class($data, $this);
    }

    /**
     * Get extensions URIs
     *
     * @param void
     * @return array
     */
    public function getExtensions(): array
    {
        if ($this->extensions !== null) {
            return $this->extensions;
        }

        $this->extensions = [];
        $helloData = $this->getHelloData();

        foreach ($this->extURNNames as $name => $data) {
            $urlns = is_string($data) ? $data : array_shift($data);
            $data = is_string($data) ? [$data] : $data;
            if (!isset($helloData['extURIs'])) {
                continue;
            }

            if (!in_array($urlns, $helloData['extURIs'], true)) {
                continue;
            }

            if (empty($this->extURNClasses[$name])) {
                continue;
            }

            $extension = $this->extURNClasses[$name];
            if (!is_object($extension)) {
                $extension = $this->createExtension($extension, $data);
            }

            $this->extensions[$name] = $extension;
        }

        return $this->extensions;
    }

    /**
     * Get objects URI
     *
     * @param void
     * @return array
     */
    public function getObjects()
    {
        if ($this->objects !== null) {
            return $this->objects;
        }

        $helloData = $this->getHelloData();
        $this->objects = $helloData['objURIs'] ?? [];

        return $this->objects;
    }

    public function getHelloData(): ?array
    {
        if ($this->helloData === null) {
            $this->helloData = $this->requestHello();
        }

        return $this->helloData;
    }

    public function requestHello(): ?array
    {
        return $this->cache->getOrSet(['epp:hello', $this->getRegistrar(), $this->data['queue']], function() {
            return $this->request('epp:hello', []);
        }, 3600);
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
        array $payload = [],
        bool $second = false
    ): array {
        $origin = $input;
        $input = $this->applyExtensions($command, $input);
        try {
            $response = $this->request($command, $input);
        } catch (AMQPTimeoutException $e) {
            throw new \Exception("The connection timed out");
        } catch (AMQPChannelClosedException $e) {
            if ($second === true) {
                throw new \Exception($e->getMessage());
            }

            unset($this->_client);
            $this->_client = null;
            return $this->commonRequest($command, $origin, $returns, $payload, true);
        }

        if (isset($response['result_code'])) {
            $rc = substr($response['result_code'], 0, 1);
        } else {
            throw new EppErrorException('failed heppy request: no answer');
        }

        if ($rc !== '1') {
            if (!empty($response['msg'])) {
                if (in_array($response['result_code'], ['2200', '2501', '2502', '2500', '2002', '2500']) && $second === false) {
                    unset($this->_client);
                    $this->_client = null;
                    sleep(5);
                    return $this->commonRequest($command, $input, $returns, $payload, true);
                }
                throw new EppErrorException(trim($response['msg'] . " " . ($response['result_reason'] ?? '')), (int) $response['result_code'], $response);
            }

            if ($second === false) {
                sleep(5);
                return $this->commonRequest($command, $input, $returns, $payload, true);
            }

            throw new EppErrorException($response['msg'] ?? ('failed heppy request: ' . var_export($response, true)), (int) $response['result_code'], $response);
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

    public function getContactTypes()
    {
        foreach (['registrant', 'admin', 'tech', 'billing'] as $type) {
            if (!empty($this->contacts['disabled']) && in_array($type, $this->contacts['disabled'], true)) {
                continue;
            }

            $contacts[$type] = $type;
        }

        return $contacts ?? [];
    }

    public function getDisabledWPZones(): array
    {
        if (empty($this->contacts['disabled_wp'])) {
            return [];
        }

        return $this->contacts['disabled_wp'];
    }

    public function getDateTime(string $datetime): DateTimeImmutable
    {
        if ($this->getTimeZone() === null) {
            return new DateTimeImmutable($datetime);
        }

        return DateTimeImmutable::createFromMutable(new DateTime($datetime))
            ->setTimeZone($this->getTimeZone());
    }

    public function getCache()
    {
        return $this->base->getCache();
    }

    public function getTimeZone(): ?DateTimeZone
    {
        return $this->timezone !== null ? new DateTimeZone($this->timezone) : null;
    }
}
