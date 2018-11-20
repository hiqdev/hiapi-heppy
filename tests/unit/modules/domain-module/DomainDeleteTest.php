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
        ], [
            'result_lang'   => 'en-US',
            'clTRID'        => 'AA-00',
            'svTRID'        => 'SRW-425500000011737478',
            'result_code'   => '1001',
            'result_msg'    => 'Command completed successfully; action pending',
        ]);

        $result = $tool->domainDelete([
            'domain'    => $domain,
            'id'        => 25844386,
        ]);

        $this->assertSame($result, [
            'result_msg'    => 'Command completed successfully; action pending',
            'result_code'   => '1001',
            'result_lang'   => 'en-US',
            'server_trid'   => 'SRW-425500000011737478',
            'client_trid'   => 'AA-00',
        ]);
    }
}
