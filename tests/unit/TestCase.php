<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\HeppyTool;
use hiapi\heppy\RabbitMQClient;
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
        array $baseMethods = null)
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
    protected function mockBase(array $methods=null): MockObject
    {
        return $this->getMockBuilder(\mrdpBase::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $requestData
     * @param array $responseData
     * @return MockObject
     */
    protected function mockClient(array $requestData, array $responseData): MockObject
    {
        $client = $this->getMockBuilder(RabbitMQClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['request'])
            ->getMock();

        $client->method('request')
            ->with($requestData)
            ->willReturn($responseData);

        return $client;
    }

    /**
     * @param string $moduleClassName
     * @param array $methods
     * @return MockObject
     */
    protected function mockModule(string $moduleClassName, array $methods): MockObject
    {
        $module =  $this->getMockBuilder($moduleClassName)
            ->disableOriginalConstructor()
            ->setMethods($this->getMethodsNames($methods))
            ->getMock();

        foreach ($methods as $method) {
            $module->method($method['methodName'])
                ->with($method['inputData'])
                ->willReturn($method['outputData']);
        }

        return $module;
    }

    private function getMethodsNames($methods): array
    {
        $methodNames = [];

        foreach ($methods as $method) {
            $methodNames[] = $method['methodName'];
        }

        return $methodNames;
    }
}
