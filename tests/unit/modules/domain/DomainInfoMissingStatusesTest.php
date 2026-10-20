<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * HQD-577: a successful domain:info reply without statuses must fail the domain
 * with an EPP error naming it and the transaction IDs, not a TypeError/warning
 * deep inside lock/delete/restore.
 */
#[AllowMockObjectsWithoutExpectations]
class DomainInfoMissingStatusesTest extends TestCase
{
    private string $domain = 'bestusedcars.name';

    private function infoWithoutStatuses(): array
    {
        $info = $this->getStubDomainInfoEppResponse([
            'name'   => strtoupper($this->domain),
            'svTRID' => '1096078890-1792505952818',
            'clTRID' => '391f8b48-cc91-11f1-a5fb-2614a7c12231',
        ]);
        unset($info['statuses']);

        return $info;
    }

    private function createToolWithInfo(array $info)
    {
        return $this->createTool([], $this->getCommonSuccessResponse(), [], [
            'domain:info' => $info,
        ]);
    }

    public static function callsReadingStatuses(): array
    {
        return [
            'domainInfo'        => ['domainInfo'],
            'domainDelete'      => ['domainDelete'],
            'domainDisableLock' => ['domainDisableLock'],
            'domainRestore'     => ['domainRestore'],
        ];
    }

    #[DataProvider('callsReadingStatuses')]
    public function testReplyWithoutStatusesFailsWithEppError(string $method): void
    {
        $tool = $this->createToolWithInfo($this->infoWithoutStatuses());

        try {
            $tool->{$method}(['domain' => $this->domain, 'id' => 1]);
            $this->fail("$method should fail on a domain:info reply without statuses");
        } catch (EppErrorException $e) {
            $this->assertSame(
                'domain:info reply for bestusedcars.name has no statuses (result 1000 Command completed successfully, '
                    . 'clTRID 391f8b48-cc91-11f1-a5fb-2614a7c12231, svTRID 1096078890-1792505952818)',
                $e->getMessage()
            );
            $this->assertSame(1000, $e->getCode());
        }
    }

    public function testEmptyStatusesFailToo(): void
    {
        $info = $this->infoWithoutStatuses();
        $info['statuses'] = [];
        $tool = $this->createToolWithInfo($info);

        $this->expectException(EppErrorException::class);
        $this->expectExceptionMessage('has no statuses');

        $tool->domainInfo(['domain' => $this->domain]);
    }

    public function testReplyWithStatusesStillWorks(): void
    {
        $tool = $this->createToolWithInfo($this->getStubDomainInfoEppResponse([
            'name'     => strtoupper($this->domain),
            'statuses' => ['pendingDelete' => 'pendingDelete'],
        ]));

        $info = $tool->domainInfo(['domain' => $this->domain]);

        $this->assertSame(['pendingDelete' => 'pendingDelete'], $info['statuses']);
        $this->assertSame($this->domain, $info['domain']);
    }
}
