<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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
}
