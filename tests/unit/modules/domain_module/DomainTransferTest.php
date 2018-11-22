<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainTransferTest extends TestCase
{
    public function testDomainTransfer()
    {
        $domain = 'silverfires1.me';

        $tool = $this->createTool([
            'op'      => 'request',
            'name'    => $domain,
            'pw'      => 'adf-AA01',
            'period'  => 1,
            'roid'    => 'D425500000000823001-AGRS',
            'command' => 'domain:transfer',
        ], $this->addCommonSuccessResponse([
            'name'        => $domain,
            'acDate'      => '2000-06-13T22:00:00.0Z',
            'exDate'      => '2002-09-08T22:00:00.0Z',
            'reDate'      => '2000-06-08T22:00:00.0Z',
            'acID'        => 'ClientY',
            'reID'        => 'ClientX',
            'trStatus'    => 'pending',
        ]));

        $result = $tool->domainTransfer([
            'domain'   => $domain,
            'password' => 'adf-AA01',
            'period'   => 1,
            'roid'     => 'D425500000000823001-AGRS',
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'domain'            => $domain,
            'expiration_date'   => '2002-09-08T22:00:00.0Z',
            'action_date'       => '2000-06-13T22:00:00.0Z',
            'action_client_id'  => 'ClientY',
            'request_date'      => '2000-06-08T22:00:00.0Z',
            'request_client_id' => 'ClientX',
            'transfer_status'   => 'pending',
        ]));
    }
}
