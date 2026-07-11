<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class DomainDeleteTest extends TestCase
{
    public function testDomainDelete()
    {
        $domain = 'silverfires1.me';

        $tool = $this->createTool([
            'name'      => $domain,
            'command'   => 'domain:delete',
        ], $this->getCommonSuccessResponse(), [], [
            'domain:info' => $this->getStubDomainInfoEppResponse(),
        ]);

        $result = $tool->domainDelete([
            'domain'    => $domain,
            'id'        => 25844386,
        ]);

        $this->assertSame($result, $this->getMappedCommonSuccessResponse());
    }
}
