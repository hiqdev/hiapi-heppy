<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainInfoTest extends TestCase
{
    public function testDomainInfo()
    {
        $domain   = 'silverfires1.me';
        $password = 'adf-AA01';

        // Mock returns the new heppy format:
        // - admin/billing/tech are always lists
        // - statuses dict uses the status name as value (not null)
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
                'inactive'                 => 'inactive',
                'serverTransferProhibited' => 'realtime',
            ],
            'exDate'     => '2019-11-09T10:43:04.0Z',
        ]));

        $result = $tool->domainInfo([
            'domain'   => $domain,
            'password' => $password,
            'id'       => null,
        ]);

        // fixStatuses() preserves the new-format dict as-is (key=>value pairs).
        // Dates are converted to 'Y-m-d H:i:s' by getUTCDateTime()->format().
        // getContactsInfo() skips contacts when the mock throws on mismatch.
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
                'serverTransferProhibited' => 'realtime',
            ],
        ], $this->getMappedCommonSuccessResponse(), [
            'another_registrar' => true,
        ]));
    }
}
