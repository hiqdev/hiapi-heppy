<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class DomainsLockTest extends TestCase
{
    /**
     * @var int
     */
    private $id = 25844450;

    /**
     * @var string
     */
    private $domain = 'silverfires1.me';

    /**
     * @return array
     */
    private function getApiData(): array
    {
        return [
            $this->id => [
                'domain' => $this->domain,
                'id'     => $this->id,
            ],
        ];
    }

    public function testDomainsEnableLock()
    {
        // domain:info is fetched inside domainUpdateStatuses to read current statuses.
        // Return a response with no lock flags so all locks are added.
        $tool = $this->createTool([
            'name'    => $this->domain,
            'command' => 'domain:update',
        ], $this->getCommonSuccessResponse(), [], [
            'domain:info' => $this->getStubDomainInfoEppResponse(),
        ]);

        $result = $tool->domainsEnableLock($this->getApiData());

        // domainUpdateStatuses strips 'id' from the row before calling domainUpdate,
        // so the result only contains 'domain' plus the common EPP success fields.
        $this->assertSame($result, [
            $this->id => $this->addMappedCommonSuccessResponse([
                'domain' => $this->domain,
            ]),
        ]);
    }

    public function testDomainsDisableLock()
    {
        // domain:info must report existing lock statuses so they are removed.
        $tool = $this->createTool([
            'name' => $this->domain,
            'command' => 'domain:update',
        ], $this->getCommonSuccessResponse(), [], [
            'domain:info' => $this->getLockedDomainInfoEppResponse(),
        ]);

        $result = $tool->domainsDisableLock($this->getApiData());

        $this->assertSame($result, [
            $this->id => $this->addMappedCommonSuccessResponse([
                'domain' => $this->domain,
            ]),
        ]);
    }

    public static function lockBatches(): array
    {
        return [
            'domainsEnableLock'  => ['domainsEnableLock', 'getStubDomainInfoEppResponse'],
            'domainsDisableLock' => ['domainsDisableLock', 'getLockedDomainInfoEppResponse'],
        ];
    }

    /**
     * HQD-577: a failing domain must not abort the rest of the batch.
     */
    #[DataProvider('lockBatches')]
    public function testFailingDomainDoesNotAbortBatch(string $method, string $infoResponse)
    {
        $broken = 'broken-' . $this->domain;
        $tool = $this->createTool([], $this->getCommonSuccessResponse(), [], [
            'domain:info' => function (array $data) use ($broken, $infoResponse) {
                $info = $this->{$infoResponse}();
                if (strcasecmp($data['name'] ?? '', $broken) === 0) {
                    unset($info['statuses']);
                }
                return $info;
            },
        ]);

        $result = $tool->{$method}([
            1 => ['domain' => $broken, 'id' => 1],
            $this->id => ['domain' => $this->domain, 'id' => $this->id],
        ]);

        $this->assertSame($broken, $result[1]['domain']);
        $this->assertStringContainsString("domain:info reply for $broken has no statuses", $result[1]['_error']);
        $this->assertSame($this->addMappedCommonSuccessResponse([
            'domain' => $this->domain,
        ]), $result[$this->id]);
    }
}
