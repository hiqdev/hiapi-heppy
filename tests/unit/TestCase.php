<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\HeppyTool;
use hiapi\heppy\RabbitMQClient;
use mrdpBase;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HeppyTool
     */
    protected $tool;

    /**
     * @param array $requestData
     * @param array $responseData
     * @param array|null $baseMethods
     * @return HeppyTool
     */
    public function createTool(
        array $requestData,
        array $responseData,
        array $baseMethods = [])
    {
        $base = $this->mockBase($baseMethods);
        $client = $this->mockClient($requestData, $responseData);

        $this->tool = new HeppyTool($base, []);
        $this->tool->setClient($client);

        return $this->tool;
    }

    /**
     * @param array|null $methods
     * @return MockObject
     */
    protected function mockBase(array $methods = []): MockObject
    {
        return $this->mockEntity(mrdpBase::class, $methods);
    }

    /**
     * @param array $requestData
     * @param array $responseData
     * @return MockObject
     */
    protected function mockClient(array $requestData, array $responseData): MockObject
    {
        return $this->mockEntity(RabbitMQClient::class, [
           [
               'methodName' => 'request',
               'inputData'  => $requestData,
               'outputData' => $responseData
           ]
        ]);
    }

    /**
     * @param string $moduleClassName
     * @param array $methods
     * @return MockObject
     */
    protected function mockModule(string $moduleClassName, array $methods): MockObject
    {
        return $this->mockEntity($moduleClassName, $methods);
    }

    /**
     * @param string $entityName
     * @param array $methods
     * @return MockObject
     */
    private function mockEntity(string $entityName, array $methods): MockObject
    {
        $entity =  $this->getMockBuilder($entityName)
            ->disableOriginalConstructor()
            ->setMethods($this->getMethodsNames($methods))
            ->getMock();

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
