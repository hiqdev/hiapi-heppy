<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\tests\unit\TestCase;

class DomainSetPasswordTest extends TestCase
{
    public function testDomainSetPassword()
    {
        $domain = 'silverfires1.me';

        $domainModule = $this->mockModule(DomainModule::class, [
            [
                'methodName' => 'domainInfo',
                'inputData'  => ['domain' => $domain],
                'outputData' => [
                    'domain'     => $domain,
                    'password'   => 'old_pass',
                    'result_msg' => 'Command completed successfully',
                ],
            ],
        ]);

        $tool = $this->createTool([
            'name'    => $domain,
            'chg'     => [
                'pw' => 'new_pass',
            ],
            'command' => 'domain:update',
        ], [
            'result_lang' => 'en-US',
            'clTRID'      => 'AA-00',
            'svTRID'      => 'SRW-425500000011746783',
            'result_code' => '1000',
            'result_msg'  => 'Command completed successfully',
        ]);

        $domainModule->setTool($tool);
        $tool->setModule('domain', $domainModule);

        $result = $tool->domainSetPassword([
            'domain'     => $domain,
            'password'   => 'new_pass',
            'pincode'    => '1234',
            'id'         => 25844450,
            'pincode_ok' => true,
        ]);

        $this->assertSame($result, [
            'id'          => 25844450,
            'domain'      => $domain,
            'result_msg'  => 'Command completed successfully',
            'result_code' => '1000',
            'result_lang' => 'en-US',
            'server_trid' => 'SRW-425500000011746783',
            'client_trid' => 'AA-00',
        ]);
    }
}
