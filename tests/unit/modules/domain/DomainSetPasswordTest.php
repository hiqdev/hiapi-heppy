<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
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

    public function testClientUpdateProhibitedIsLiftedBeforeThePasswordIsSet(): void
    {
        $domain = 'silverfires1.me';
        $domainModule = $this->mockModule(DomainModule::class, [
            [
                'methodName' => 'domainInfo',
                'outputData' => [
                    'domain'   => $domain,
                    'statuses' => ['clientUpdateProhibited' => 'clientUpdateProhibited'],
                ],
            ],
        ]);
        $updates = [];
        $tool = $this->createTool([], $this->getCommonSuccessResponse(), [], [
            'domain:update' => function (array $data) use (&$updates): array {
                $updates[] = $data;
                return $this->getCommonSuccessResponse();
            },
        ]);
        $domainModule->setTool($tool);
        $tool->setModule('domain', $domainModule);

        $tool->domainSetPassword(['domain' => $domain, 'password' => 'new_pass', 'id' => 25844450]);

        $this->assertCount(2, $updates);
        $this->assertArrayHasKey('rem', $updates[0]);
        $this->assertSame(['pw' => 'new_pass'], $updates[1]['chg']);
    }
}
