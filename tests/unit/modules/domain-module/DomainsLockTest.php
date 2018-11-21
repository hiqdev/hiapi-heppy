<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsLockTest extends TestCase
{
    private $id = 25844450;
    private $domain = 'silverfires1.me';

    public function testDomainsEnableLock()
    {
        $tool = $this->createTool([
            'name'    => 'silverfires1.me',
            'add'     => [
                'statuses' => [
                    'clientDeleteProhibited'   => null,
                    'clientTransferProhibited' => null,
                ],
            ],
            'command' => 'domain:update',
        ], [
            'result_lang' => 'en-US',
            'clTRID'      => 'AA-00',
            'svTRID'      => 'SRW-425500000011746893',
            'result_code' => '1000',
            'result_msg'  => 'Command completed successfully',
        ]);

        $result = $tool->domainsEnableLock([
            $this->id => [
                'domain' => $this->domain,
                'id'     => $this->id,
            ],
        ]);

        $this->assertSame($result, [
            25844450 => [
                'id'          => $this->id,
                'domain'      => $this->domain,
                'result_msg'  => 'Command completed successfully',
                'result_code' => '1000',
                'result_lang' => 'en-US',
                'server_trid' => 'SRW-425500000011746893',
                'client_trid' => 'AA-00',
            ],
        ]);
    }

//    public function testDomainsDisableLock()
//    {
//        $tool = $this->createTool();
//
//        $result = $tool->domainsDisableLock();
//
//        $this->assertSame($result, );
//    }
}
