<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainRenewTest extends TestCase
{
    public function testDomainRenew()
    {
        $domain = 'silverfires1.me';

        $tool = $this->createTool([
            'name'          => $domain,
            'curExpDate'    => '2019-11-09',
            'period'        => '1',
            'command'       => 'domain:renew',
        ], $this->addCommonSuccessResponse([
            'name'          => 'silverfires1.me',
            'exDate'        => '2020-11-09T10:43:04.0Z',
        ]));

        $result = $tool->domainRenew([
            'domain'        => $domain,
            'amount'        => '1',
            'period'        => '1',
            'expires'       => '2019-11-09',
            'coupon'        => NULL,
            'id'            => 25844450,
            'type'          => 'drenewal',
            'object'        => 'domain',
            'client_id'     => '2024202',
            'seller_id'     => '1004697',
            'expires_time'  => NULL,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'domain'            => $domain,
            'expiration_date'   => '2020-11-09T10:43:04.0Z',
        ]));
    }
}
