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
