<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\ClientInterface;
use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\HeppyTool;
use hiapi\heppy\tests\unit\Stubs\HeppyBaseStub;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * HQD-577: the epp:hello reply is cached for a day and decides the extensions every
 * request gets, so only a real greeting may be cached.
 */
#[AllowMockObjectsWithoutExpectations]
class HeppyToolHelloTest extends \PHPUnit\Framework\TestCase
{
    private const GREETING = [
        'svID'    => 'Verisign CTLD EPP Registration Server',
        'objURIs' => ['urn:ietf:params:xml:ns:domain-1.0'],
        'extURIs' => ['http://www.verisign-grs.com/epp/namestoreExt-1.1'],
    ];

    /**
     * @param array[] $replies epp:hello replies, in order
     */
    private function createTool(array $replies, ?int &$calls = null): HeppyTool
    {
        $calls = 0;
        $client = $this->createMock(ClientInterface::class);
        $client->method('request')->willReturnCallback(function () use (&$replies, &$calls) {
            $calls++;
            return array_shift($replies);
        });

        return new class(new HeppyBaseStub(), [], $client) extends HeppyTool {
            public function __construct($base, $data, private ClientInterface $testClient)
            {
                parent::__construct($base, $data);
            }

            protected function getClient(): ClientInterface
            {
                return $this->testClient;
            }
        };
    }

    public function testGreetingIsReturnedAndCached(): void
    {
        $tool = $this->createTool([self::GREETING], $calls);

        $this->assertSame(self::GREETING, $tool->requestHello());
        $this->assertSame(self::GREETING, $tool->requestHello());
        $this->assertSame(1, $calls);
    }

    public function testNonGreetingIsRetriedNotCached(): void
    {
        $tool = $this->createTool([
            ['result_code' => '1000', 'result_msg' => 'Command completed successfully', 'clTRID' => 'AA-1'],
            ['_error' => 'timed out'],
            self::GREETING,
        ], $calls);

        $this->assertSame(self::GREETING, $tool->requestHello());
        $this->assertSame(3, $calls);
        $this->assertArrayHasKey('namestoreExt', $tool->getExtensions());
    }

    public function testFailsWithoutLeakingReplyData(): void
    {
        $notGreeting = [
            'result_code' => '1000',
            'clTRID'      => 'AA-1',
            'svTRID'      => 'S-1',
            'pw'          => 'secret-auth-info',
        ];
        $tool = $this->createTool([$notGreeting, $notGreeting, $notGreeting, self::GREETING], $calls);

        try {
            $tool->requestHello();
            $this->fail('requestHello should fail without a greeting');
        } catch (EppErrorException $e) {
            $this->assertSame('could not get EPP greeting, got: {"result_code":"1000","clTRID":"AA-1","svTRID":"S-1"}', $e->getMessage());
        }
        $this->assertSame(3, $calls);

        // Nothing was left cached: the next call asks the server again.
        $this->assertSame(self::GREETING, $tool->requestHello());
        $this->assertSame(4, $calls);
    }
}
