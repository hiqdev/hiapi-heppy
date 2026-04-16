<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainInfoTest extends TestCase
{
    public function testDomainInfo()
    {
        $domain   = 'silverfires1.me';
        $password = 'adf-AA01';

        // Make contact:info throw so getContactsInfo() skips all contact lookups.
        // (The catch block inside getContactsInfo() handles any \Throwable.)
        $tool = $this->createTool([
            'name'    => $domain,
            'pw'      => $password,
            'command' => 'domain:info',
        ], $this->addCommonSuccessResponse([
            'billing'    => ['MR_25844382'],
            'name'       => $domain,
            'roid'       => 'D425500000000823001-AGRS',
            'admin'      => ['MR_25844382'],
            'crDate'     => '2018-11-09T10:43:04.0Z',
            'upID'       => 'OTE1186-EP1',
            'upDate'     => '2018-11-19T13:09:35.0Z',
            'crID'       => 'OTE1186-EP1',
            'clID'       => 'OTE1186-EP1',
            'tech'       => ['MR_25844382'],
            'pw'         => $password,
            'registrant' => 'MR_25844382',
            'statuses'   => [
                'inactive'                 => null,
                'serverTransferProhibited' => 'realtime',
            ],
            'exDate'     => '2019-11-09T10:43:04.0Z',
        ]), [], [
            'contact:info' => new \Exception('Contact not found'),
        ]);

        $result = $tool->domainInfo([
            'domain'   => $domain,
            'password' => $password,
            'id'       => null,
        ]);

        // Dates are formatted to 'Y-m-d H:i:s' by getUTCDateTime()->format().
        // fixStatuses() expands each (key => value) pair into two entries so that
        // both the key and the value are individually searchable.
        // another_registrar is true because the mock tool has no 'registrar' data.
        $this->assertSame($result, array_merge([
            'domain'          => $domain,
            'name'            => $domain,
            'roid'            => 'D425500000000823001-AGRS',
            'created_by'      => 'OTE1186-EP1',
            'created_date'    => '2018-11-09 10:43:04',
            'updated_by'      => 'OTE1186-EP1',
            'updated_date'    => '2018-11-19 13:09:35',
            'expiration_date' => '2019-11-09 10:43:04',
            'registrant'      => 'MR_25844382',
            'admin'           => ['MR_25844382'],
            'billing'         => ['MR_25844382'],
            'tech'            => ['MR_25844382'],
            'password'        => $password,
            'epp_client_id'   => 'OTE1186-EP1',
            'statuses'        => [
                'inactive'                 => 'inactive',
                'serverTransferProhibited' => 'serverTransferProhibited',
                ''                         => null,
                'realtime'                 => 'realtime',
            ],
        ], $this->getMappedCommonSuccessResponse(), [
            'another_registrar' => true,
        ]));
    }
}
