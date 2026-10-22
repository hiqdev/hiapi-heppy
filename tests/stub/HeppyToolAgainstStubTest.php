<?php

namespace hiapi\heppy\tests\stub;

use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\HeppyTool;
use hiapi\heppy\tests\unit\Stubs\HeppyBaseStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * HQD-577: HeppyTool -> RabbitMQ -> heppyd -> the EPP stub of evoplus-heppy
 * (test/stub/epp_stub.py, see its README.md), which replays the registry
 * faults of 2026-10-16/17 for some domain names.
 *
 * Needs a running stack: heppyd with hiqdev/heppy#67 (frame/clTRID checks)
 * against the stub, and its RabbitMQ reachable. Run:
 *
 *     HEPPY_STUB_RABBITMQ=rabbitmq vendor/bin/phpunit \
 *         --configuration vendor/hiqdev/hiapi-heppy/phpunit.xml.dist --testsuite stub
 *
 * HEPPY_STUB_QUEUE defaults to heppy-epp-dev (test/stub/epp.dev.json).
 * The slow-reply case also needs HEPPY_STUB_SLOW_DELAY: the stub's
 * --slow-delay, between heppy's 15s registry timeout and hiapi's 20s wait.
 */
#[Group('heppy-stub')]
class HeppyToolAgainstStubTest extends TestCase
{
    private const HEPPY_TIMEOUT = 15;
    private const HIAPI_WAIT = 20;

    private HeppyTool $tool;

    protected function setUp(): void
    {
        $host = getenv('HEPPY_STUB_RABBITMQ');
        if (!$host) {
            $this->markTestSkipped('HEPPY_STUB_RABBITMQ is not set: no heppyd + EPP stub stack to talk to');
        }

        $this->tool = new HeppyTool(new HeppyBaseStub(), [
            'url'   => $host,
            'queue' => getenv('HEPPY_STUB_QUEUE') ?: 'heppy-epp-dev',
        ]);
    }

    public function testGreetingFromHello(): void
    {
        $greeting = $this->tool->requestHello();

        $this->assertStringContainsString('epp_stub', $greeting['svID']);
    }

    public function testDomainInfo(): void
    {
        $info = $this->tool->domainInfo(['domain' => 'testdomain.test']);

        $this->assertArrayHasKey('clientUpdateProhibited', $info['statuses']);
    }

    public function testDomainInfoWithoutStatusesFailsWithTransactionIds(): void
    {
        $this->expectException(EppErrorException::class);
        $this->expectExceptionMessageMatches(
            '/^domain:info reply for nostatus\.test has no statuses \(result 1000 .+, clTRID \S+, svTRID SRO-\d+\)$/'
        );

        $this->tool->domainInfo(['domain' => 'nostatus.test']);
    }

    public function testRegistryError(): void
    {
        $this->expectExceptionMessage('Authorization error');

        $this->tool->domainInfo(['domain' => 'authfail.test']);
    }

    public function testReplyToAnotherRequestIsRejected(): void
    {
        $this->assertFailsThenRecovers(
            'foreign.test',
            '/^failed heppy request: EPP reply clTRID stub-foreign-0001 does not match request clTRID \S+, connection dropped$/'
        );
    }

    public function testCutFrameIsRejected(): void
    {
        $this->assertFailsThenRecovers(
            'cut.test',
            '/^failed heppy request: connection closed inside EPP frame \(\d+ of \d+ bytes\)$/'
        );
    }

    #[Group('slow')]
    public function testSlowReplyTimesOutInHeppyBeforeHiapi(): void
    {
        $delay = (float) getenv('HEPPY_STUB_SLOW_DELAY');
        if ($delay <= self::HEPPY_TIMEOUT || $delay >= self::HIAPI_WAIT) {
            $this->markTestSkipped(sprintf(
                'HEPPY_STUB_SLOW_DELAY (the stub\'s --slow-delay) must be between %ds and %ds',
                self::HEPPY_TIMEOUT,
                self::HIAPI_WAIT
            ));
        }

        $started = microtime(true);
        $this->assertFailsThenRecovers(
            'slow.test',
            '/^failed heppy request: EPP: no reply from \S+ in 15(\.0)?s to clTRID \S+, connection dropped$/'
        );
        $this->assertLessThan(self::HIAPI_WAIT + $delay, microtime(true) - $started);
    }

    public function testBatchFailsOnlyTheRefusedDomains(): void
    {
        $res = $this->tool->domainsDisableLock([
            1 => ['id' => 1, 'domain' => 'first.test'],
            2 => ['id' => 2, 'domain' => 'nostatus.test'],
            3 => ['id' => 3, 'domain' => 'foreign.test'],
            4 => ['id' => 4, 'domain' => 'authfail.test'],
            5 => ['id' => 5, 'domain' => 'last.test'],
        ]);

        $this->assertSame([1, 2, 3, 4, 5], array_keys($res));
        $this->assertArrayNotHasKey('_error', $res[1]);
        $this->assertStringStartsWith('domain:info reply for nostatus.test has no statuses', $res[2]['_error']);
        $this->assertStringContainsString('does not match request clTRID', $res[3]['_error']);
        $this->assertSame('Authorization error', $res[4]['_error']);
        $this->assertArrayNotHasKey('_error', $res[5]);
    }

    /**
     * The request fails with $pattern, heppy drops the connection, and the
     * next request reconnects and gets its answer.
     */
    private function assertFailsThenRecovers(string $domain, string $pattern): void
    {
        $error = null;
        try {
            $this->tool->domainInfo(['domain' => $domain]);
        } catch (\Exception $e) {
            // domainInfo() rethrows heppy's failure as a plain Exception.
            $error = $e->getMessage();
        }
        $this->assertNotNull($error, "domainInfo($domain) should have failed");
        $this->assertMatchesRegularExpression($pattern, $error);

        $info = $this->tool->domainInfo(['domain' => 'testdomain.test']);
        $this->assertNotEmpty($info['statuses']);
    }
}
