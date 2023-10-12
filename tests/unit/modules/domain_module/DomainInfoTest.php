<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;


use hiapi\heppy\tests\unit\TestCase;

class DomainInfoTest extends TestCase
{
    public function testDomainInfo()
    {
        $domain = 'silverfires1.me';
        $password = 'adf-AA01';

        $tool = $this->createTool([
            'name'      => $domain,
            'pw'        => $password,
            'command'   => 'domain:info',
        ], $this->addCommonSuccessResponse([
            'billing'       => ['MR_25844382'],
            'name'          => $domain,
            'roid'          => 'D425500000000823001-AGRS',
            'admin'         => ['MR_25844382'],
            'crDate'        => '2018-11-09T10:43:04.0Z',
            'upID'          => 'OTE1186-EP1',
            'upDate'        => '2018-11-19T13:09:35.0Z',
            'crID'          => 'OTE1186-EP1',
            'clID'          => 'OTE1186-EP1',
            'tech'          => ['MR_25844382'],
            'pw'            => $password,
            'registrant'    => 'MR_25844382',
            'statuses'      => [
                'inactive'                  => NULL,
                'serverTransferProhibited'  => 'realtime',
            ],
            'exDate'        => '2019-11-09T10:43:04.0Z',
        ]));

        $result = $tool->domainInfo([
            'domain'    => $domain,
            'password'  => $password,
            'id'        => NULL,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'domain'            => $domain,
            'name'              => $domain,
            'roid'              => 'D425500000000823001-AGRS',
            'created_by'        => 'OTE1186-EP1',
            'created_date'      => '2018-11-09T10:43:04.0Z',
            'updated_by'        => 'OTE1186-EP1',
            'updated_date'      => '2018-11-19T13:09:35.0Z',
            'expiration_date'   => '2019-11-09T10:43:04.0Z',
            'registrant'        => 'MR_25844382',
            'admin'             => ['MR_25844382'],
            'billing'           => ['MR_25844382'],
            'tech'              => ['MR_25844382'],
            'password'          => $password,
            'epp_client_id'     => 'OTE1186-EP1',
            'statuses'          => [
                'inactive'                  => NULL,
                'serverTransferProhibited'  => 'realtime',
            ],
        ]));
    }
}
