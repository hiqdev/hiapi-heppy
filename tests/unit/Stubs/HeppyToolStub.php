<?php

namespace hiapi\heppy\tests\unit\Stubs;

use hiapi\heppy\ClientInterface;
use hiapi\heppy\HeppyTool;

/**
 * HeppyTool subclass for unit tests.
 * Overrides requestHello() so modules can initialise without a real EPP connection,
 * retries without waiting, and reconnects to the test client instead of RabbitMQ.
 */
class HeppyToolStub extends HeppyTool
{
    protected int $retryDelaySeconds = 0;

    /** @var string[] extension URIs announced in the greeting */
    public array $extURIs = [];

    private $testClient;

    public function requestHello(int $tries = 0): ?array
    {
        return [
            'svID'    => 'Test EPP Server',
            'objURIs' => [],
            'extURIs' => $this->extURIs,
        ];
    }

    public function setClient($client): void
    {
        $this->testClient = $client;
        parent::setClient($client);
    }

    protected function getClient(): ClientInterface
    {
        return $this->testClient ?? parent::getClient();
    }
}
