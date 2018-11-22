<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\tests\unit\TestCase;

class DomainSetNSsTest extends TestCase
{
    public function testDomainSetNSs()
    {
        $domain = 'silverfires42.me';

        $apiData = [
            'domain' => $domain,
            'nss'    => [
                'ns1.silverfires1.me' => 'ns1.silverfires1.me',
                'ns2.silverfires1.me' => 'ns2.silverfires1.me',
            ],
            'id'     => 25844481,
        ];

        $domainModule = $this->mockModule(DomainModule::class, [
            [
                'methodName' => 'domainInfo',
                'inputData'  => $apiData,
                'outputData' => [
                    'domain'     => $domain,
                    'nss'        => [
                        'ns3.silverfires1.me',
                    ],
                    'result_msg' => 'Command completed successfully',
                ],
            ],
        ]);

        $tool = $this->createTool([
            'name'    => $domain,
            'add'     => [
                'nss' => [
                    'ns1.silverfires1.me' => 'ns1.silverfires1.me',
                    'ns2.silverfires1.me' => 'ns2.silverfires1.me',
                ],
            ],
            'rem'     => [
                'nss' => [
                    0 => 'ns3.silverfires1.me',
                ],
            ],
            'command' => 'domain:update',
        ], $this->getCommonSuccessResponse());

        $domainModule->setTool($tool);
        $tool->setModule('domain', $domainModule);

        $result = $tool->domainSetNSs($apiData);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'id'          => 25844481,
            'domain'      => $domain,
        ]));
    }
}
