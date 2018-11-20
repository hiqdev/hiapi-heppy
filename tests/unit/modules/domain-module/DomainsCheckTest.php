<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsCheckTest extends TestCase
{
    public function testDomainsCheck()
    {
        $tool = $this->createTool([
            'names'         => [
                0 => 'silverfires1.me',
                1 => 'silverfires42.me',
            ],
            'command'       => 'domain:check',
        ], [
            'clTRID'        => 'AA-00',
            'svTRID'        => 'SRO-1542720376580',
            'result_msg'    => 'Command completed successfully',
            'reasons'       => [
                'silverfires1.me'   => 'In use',
            ],
            'avails'        => [
                'silverfires42.me'  => '1',
                'silverfires1.me'   => '0',
            ],
            'result_lang'   => 'en-US',
            'result_code'   => '1000',
        ]);

        $result = $tool->domainsCheck([
            'domains' => [
                0 => 'silverfires1.me',
                1 => 'silverfires42.me',
            ],
        ]);

        $this->assertSame($result, [
            'avails' => [
                'silverfires42.me'  => '1',
                'silverfires1.me'   => '0',
            ],
            'reasons' => [
                'silverfires1.me'   => 'In use',
            ],
            'result_msg'    => 'Command completed successfully',
            'result_code'   => '1000',
            'result_lang'   => 'en-US',
            'server_trid'   => 'SRO-1542720376580',
            'client_trid'   => 'AA-00',
        ]);
    }
}
