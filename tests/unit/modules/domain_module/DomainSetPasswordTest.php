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
        ], $this->getCommonSuccessResponse());

        $domainModule->setTool($tool);
        $tool->setModule('domain', $domainModule);

        $result = $tool->domainSetPassword([
            'domain'     => $domain,
            'password'   => 'new_pass',
            'pincode'    => '1234',
            'id'         => 25844450,
            'pincode_ok' => true,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'id'          => 25844450,
            'domain'      => $domain,
        ]));
    }
}
