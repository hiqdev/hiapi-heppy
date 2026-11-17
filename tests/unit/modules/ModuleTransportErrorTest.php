<?php

namespace hiapi\heppy\tests\unit\modules;

use hiapi\heppy\exceptions\EppTransportException;
use hiapi\heppy\HeppyTool;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ModuleTransportErrorTest extends TestCase
{
    private const MESSAGE = 'failed heppy request: EPP: no reply from epp.example in 15.0s to clTRID AA-1, connection dropped';

    private function createDisconnectedTool(): HeppyTool
    {
        return $this->createTool([], [
            '_error' => 'EPP: no reply from epp.example in 15.0s to clTRID AA-1, connection dropped',
            HeppyTool::ERROR_KIND_KEY => HeppyTool::CONNECTION_ERROR_KIND,
        ]);
    }

    public function testDomainInfoKeepsTheTransportFailure(): void
    {
        $this->expectException(EppTransportException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        $this->createDisconnectedTool()->domainInfo(['domain' => 'example.com', 'id' => 1]);
    }

    public function testDomainsCheckKeepsTheTransportFailure(): void
    {
        $this->expectException(EppTransportException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        $this->createDisconnectedTool()->domainsCheck(['domains' => ['a.com', 'b.com']]);
    }

    public function testHostSetKeepsTheTransportFailure(): void
    {
        $this->expectException(EppTransportException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        $this->createDisconnectedTool()->hostSet(['host' => 'ns1.example.com', 'ips' => ['192.0.2.1']]);
    }
}
