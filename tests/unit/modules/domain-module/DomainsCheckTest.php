<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsCheckTest extends TestCase
{
    public function testDomainsCheck()
    {
        $domain1 = 'silverfires1.me';
        $domain42 = 'silverfires42.me';

        $tool = $this->createTool([
            'names'         => [
                0 => $domain1,
                1 => $domain42,
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
                $domain1   => '0',
                $domain42  => '1',
            ],
            'result_lang'   => 'en-US',
            'result_code'   => '1000',
        ]);

        $result = $tool->domainsCheck([
            'domains' => [
                0 => $domain1,
                1 => $domain42,
            ],
        ]);

        $this->assertSame($result, [
            'avails' => [
                $domain1   => '0',
                $domain42  => '1',
            ],
            'reasons' => [
                $domain1   => 'In use',
            ],
            'result_msg'    => 'Command completed successfully',
            'result_code'   => '1000',
            'result_lang'   => 'en-US',
            'server_trid'   => 'SRO-1542720376580',
            'client_trid'   => 'AA-00',
        ]);
    }
}
