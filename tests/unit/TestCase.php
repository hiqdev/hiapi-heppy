<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\HeppyTool;
use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyBaseStub;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected HeppyTool $tool;

    /**
     * @param array $requestData
     * @param array $responseData     default response returned for every EPP command
     * @param array $baseMethods
     * @param array $extraResponses   command-specific overrides: ['domain:info' => rawEppArray]
     *                                Pass \Throwable instances to make that command throw.
     * @param bool $loose             If true, mockClient will not strictly match requestData
     * @return HeppyTool
     */
    public function createTool(
        array $requestData,
        array $responseData,
        array $baseMethods = [],
        array $extraResponses = [],
        bool $loose = true
    ): HeppyTool {
        $base = $this->mockBase($baseMethods);
        $client = $this->mockClient($requestData, $responseData, $extraResponses, $loose);

        $this->tool = new HeppyToolStub($base, []);
        $this->tool->setClient($client);

        return $this->tool;
    }

    /**
     * @param array $methods
     * @return MockObject
     */
    protected function mockBase(?array $methods = null): MockObject
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
     * @param array $requestData
     * @param array $responseData
     * @param array $extraResponses
     * @param bool $loose
     * @return MockObject
     */
    protected function mockClient(
        array $requestData,
        array $responseData,
        array $extraResponses = [],
        bool $loose = true
    ): MockObject {
        $mock = $this->createMock(RabbitMQClient::class);

        if (!$loose && !empty($requestData)) {
            $mock->expects($this->once())->method('request')->with($requestData)->willReturn($responseData);
            return $mock;
        }

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
     * Create a module mock.
     *
     * @param string $moduleClassName
     * @param array  $methods         Each entry can have 'methodName', 'outputData' and optional 'inputData'
     * @param bool   $loose           If true, will not strictly match inputData even if present
     * @return MockObject
     */
    protected function mockModule(string $moduleClassName, array $methods, bool $loose = true): MockObject
    {
        $builder = $this->getMockBuilder($moduleClassName)
            ->disableOriginalConstructor();

        $methodNames = $this->getMethodsNames($methods);
        if (!empty($methodNames)) {
            $builder->onlyMethods($methodNames);
        }

        $entity = $builder->getMock();

        foreach ($methods as $method) {
            $m = $entity->method($method['methodName']);
            if (!$loose && isset($method['inputData'])) {
                $m->with($method['inputData']);
            }
            $m->willReturn($method['outputData']);
        }

        return $entity;
    }

    /**
     * @param string $entityName
     * @param array $methods
     * @return MockObject
     */
    protected function mockEntity(string $entityName, array $methods): MockObject
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
    protected function getMethodsNames(array $methods): array
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
    protected function addCommonSuccessResponse(?array $data = null): array
    {
        return array_merge($data ?? [], $this->getCommonSuccessResponse());
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
     * @param array|null $data
     * @return array
     */
    protected function addMappedCommonSuccessResponse(?array $data = null): array
    {
        return array_merge($data ?? [], $this->getMappedCommonSuccessResponse());
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
