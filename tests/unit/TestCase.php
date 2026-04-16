<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\HeppyTool;
use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyBaseStub;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HeppyTool
     */
    protected $tool;

    /**
     * @param array $requestData      (doc-only; not used for strict input matching)
     * @param array $responseData     default response returned for every EPP command
     * @param array $baseMethods
     * @param array $extraResponses   command-specific overrides: ['domain:info' => rawEppArray]
     *                                Pass \Throwable instances to make that command throw.
     * @return HeppyTool
     */
    public function createTool(
        array $requestData,
        array $responseData,
        array $baseMethods = [],
        array $extraResponses = []
    ): HeppyTool {
        $base = $this->mockBase($baseMethods);
        $client = $this->mockClient($requestData, $responseData, $extraResponses);

        $this->tool = new HeppyToolStub($base, []);
        $this->tool->setClient($client);

        return $this->tool;
    }

    /**
     * @param array $methods
     * @return MockObject
     */
    protected function mockBase(array $methods = []): MockObject
    {
        return $this->mockEntity(HeppyBaseStub::class, $methods);
    }

    /**
     * Create a flexible RabbitMQClient mock.
     *
     * Returns $responseData for any EPP command unless an override is provided in
     * $extraResponses.  Pass a \Throwable as the value to make a specific command
     * throw instead of returning data — useful to force code paths that catch EPP
     * errors (e.g. skip contact-info lookups in domainInfo).
     *
     * @param array $requestData    (doc-only; not checked)
     * @param array $responseData
     * @param array $extraResponses
     * @return MockObject
     */
    protected function mockClient(
        array $requestData,
        array $responseData,
        array $extraResponses = []
    ): MockObject {
        $mock = $this->createMock(RabbitMQClient::class);

        if (empty($extraResponses)) {
            $mock->method('request')->willReturn($responseData);
        } else {
            $mock->method('request')->willReturnCallback(
                function (array $data) use ($responseData, $extraResponses) {
                    $command = $data['command'] ?? null;
                    if (array_key_exists($command, $extraResponses)) {
                        $override = $extraResponses[$command];
                        if ($override instanceof \Throwable) {
                            throw $override;
                        }
                        return $override;
                    }
                    return $responseData;
                }
            );
        }

        return $mock;
    }

    /**
     * Create a flexible module mock whose configured methods return their
     * outputData for **any** input (no strict ->with() matching).
     *
     * @param string $moduleClassName
     * @param array  $methods
     * @return MockObject
     */
    protected function mockModule(string $moduleClassName, array $methods): MockObject
    {
        $builder = $this->getMockBuilder($moduleClassName)
            ->disableOriginalConstructor();

        $methodNames = $this->getMethodsNames($methods);
        if (!empty($methodNames)) {
            $builder->onlyMethods($methodNames);
        }

        $entity = $builder->getMock();

        foreach ($methods as $method) {
            $entity->method($method['methodName'])
                ->willReturn($method['outputData']);
        }

        return $entity;
    }

    /**
     * @param string $entityName
     * @param array $methods
     * @return MockObject
     */
    private function mockEntity(string $entityName, array $methods): MockObject
    {
        $builder = $this->getMockBuilder($entityName)
            ->disableOriginalConstructor();

        $methodNames = $this->getMethodsNames($methods);
        if (!empty($methodNames)) {
            $builder->onlyMethods($methodNames);
        }

        $entity = $builder->getMock();

        foreach ($methods as $method) {
            $entity->method($method['methodName'])
                ->with($method['inputData'])
                ->willReturn($method['outputData']);
        }

        return $entity;
    }

    /**
     * @param array $methods
     * @return array
     */
    private function getMethodsNames(array $methods): array
    {
        $methodNames = [];
        foreach ($methods as $method) {
            $methodNames[] = $method['methodName'];
        }
        return $methodNames;
    }

    /**
     * Minimal raw EPP domain:info response that is safe to use as a stub for
     * intermediate domainInfo() calls within domainDelete / domainSetNSs / lock
     * operations. Contains non-empty statuses so domainDisableUpdateProhibited()
     * doesn't crash, but no lock flags so lock-state checks short-circuit.
     */
    protected function getStubDomainInfoEppResponse(array $extra = []): array
    {
        return array_merge([
            'result_code' => '1000',
            'result_msg'  => 'Command completed successfully',
            'result_lang' => 'en-US',
            'svTRID'      => 'SRW-425500000011746893',
            'clTRID'      => 'AA-00',
            'statuses'    => ['ok' => 'ok'],
        ], $extra);
    }

    /**
     * Like getStubDomainInfoEppResponse() but with lock statuses set so that
     * domainDisableLock correctly detects them and removes them.
     */
    protected function getLockedDomainInfoEppResponse(): array
    {
        return $this->getStubDomainInfoEppResponse([
            'statuses' => [
                'clientTransferProhibited' => 'clientTransferProhibited',
                'clientDeleteProhibited'   => 'clientDeleteProhibited',
            ],
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function addCommonSuccessResponse(array $data = null): array
    {
        return array_merge($data, $this->getCommonSuccessResponse());
    }

    /**
     * @return array
     */
    protected function getCommonSuccessResponse(): array
    {
        return [
            'result_lang' => 'en-US',
            'clTRID'      => 'AA-00',
            'svTRID'      => 'SRW-425500000011746893',
            'result_code' => '1000',
            'result_msg'  => 'Command completed successfully',
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function addMappedCommonSuccessResponse(array $data = null): array
    {
        return array_merge($data, $this->getMappedCommonSuccessResponse());
    }

    /**
     * @return array
     */
    protected function getMappedCommonSuccessResponse(): array
    {
        return [
            'result_msg'  => 'Command completed successfully',
            'result_code' => '1000',
            'result_lang' => 'en-US',
            'server_trid' => 'SRW-425500000011746893',
            'client_trid' => 'AA-00',
        ];
    }
}
