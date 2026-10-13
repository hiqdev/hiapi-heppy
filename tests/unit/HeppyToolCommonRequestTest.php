<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class HeppyToolCommonRequestTest extends TestCase
{
    private const NAMESTORE_URI = 'http://www.verisign-grs.com/epp/namestoreExt-1.1';

    /** @var array[] requests sent to the client, in order */
    private array $sent = [];

    /**
     * @param array[] $responses returned one per request, in order
     */
    private function createSequenceTool(array $responses): HeppyToolStub
    {
        $client = $this->createMock(RabbitMQClient::class);
        $client->method('request')->willReturnCallback(function (array $data) use (&$responses) {
            $this->sent[] = $data;
            return array_shift($responses);
        });

        $tool = new HeppyToolStub($this->mockBase(), []);
        $tool->extURIs = [self::NAMESTORE_URI];
        $tool->setClient($client);

        return $tool;
    }

    private function renew(HeppyToolStub $tool): array
    {
        return $tool->commonRequest('domain:renew', [
            'name'       => 'example.net',
            'curExpDate' => '2026-09-13',
            'period'     => 1,
        ]);
    }

    private function countNamestoreExtensions(array $request): int
    {
        return count(array_filter(
            $request['extensions'] ?? [],
            fn (array $extension): bool => $extension['command'] === 'namestoreExt'
        ));
    }

    public function testRegistryErrorIsThrownWithItsMessageAndNotRetried(): void
    {
        $tool = $this->createSequenceTool([[
            'result_code'   => '2004',
            'result_msg'    => 'Parameter value range error',
            'result_reason' => 'Does not match expiration',
        ]]);

        try {
            $this->renew($tool);
            $this->fail('EppErrorException expected');
        } catch (EppErrorException $e) {
            $this->assertSame('Parameter value range error Does not match expiration', $e->getMessage());
            $this->assertSame(2004, $e->getCode());
        }

        $this->assertCount(1, $this->sent);
    }

    public function testSessionErrorIsRetriedOnceWithTheSameExtensions(): void
    {
        $tool = $this->createSequenceTool([
            ['result_code' => '2002', 'result_msg' => 'Command use error'],
            ['result_code' => '1000', 'result_msg' => 'Command completed successfully'],
        ]);

        $result = $this->renew($tool);

        $this->assertSame('1000', $result['result_code']);
        $this->assertCount(2, $this->sent);
        $this->assertSame(1, $this->countNamestoreExtensions($this->sent[0]));
        $this->assertSame($this->sent[0]['extensions'], $this->sent[1]['extensions']);
    }

    public function testResponseWithoutMessageIsRetriedOnceWithTheSameExtensions(): void
    {
        $tool = $this->createSequenceTool([
            ['result_code' => '2400'],
            ['result_code' => '2400'],
        ]);

        try {
            $this->renew($tool);
            $this->fail('EppErrorException expected');
        } catch (EppErrorException $e) {
            $this->assertStringStartsWith('failed heppy request: ', $e->getMessage());
        }

        $this->assertCount(2, $this->sent);
        $this->assertSame(1, $this->countNamestoreExtensions($this->sent[0]));
        $this->assertSame($this->sent[0]['extensions'], $this->sent[1]['extensions']);
    }
}
