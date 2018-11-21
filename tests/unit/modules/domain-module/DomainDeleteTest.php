<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainDeleteTest extends TestCase
{
    public function testDomainDelete()
    {
        $domain = 'silverfires1.me';

        $tool = $this->createTool([
            'name'      => $domain,
            'command'   => 'domain:delete',
        ], $this->getCommonSuccessResponse());

        $result = $tool->domainDelete([
            'domain'    => $domain,
            'id'        => 25844386,
        ]);

        $this->assertSame($result, $this->getMappedCommonSuccessResponse());
    }
}
