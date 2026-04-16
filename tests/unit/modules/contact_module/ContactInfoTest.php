<?php

namespace hiapi\heppy\tests\unit\modules\contact_module;

class ContactInfoTest extends ContactTestCase
{
    public function testContactInfo()
    {
        $tool = $this->createTool([
            'id'      => $this->eppId,
            'command' => 'contact:info',
            'pw'      => 'rQ4&lP7*rZ',
        ], $this->addCommonSuccessResponse([
            'city'     => 'Limassol',
            'fax'      => '+357.95713635',
            'name'     => 'WhoisProtectService.net',
            'roid'     => 'C2865751-AGRS',
            'cc'       => 'CY',
            'crDate'   => '2018-11-21T16:54:31.0Z',
            'pc'       => '3025',
            'email'    => 'silverfires21.me@whoisprotectservice.net',
            'crID'     => 'OTE1186-EP1',
            'clID'     => 'OTE1186-EP1',
            'street'   => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'voice'    => '+357.95713635',
            'id'       => $this->eppId,
            'statuses' => [
                'ok'     => null,
                'linked' => null,
            ],
            'pw'       => 'rQ4&lP7*rZ',
        ]));

        $result = $tool->contactInfo($this->contactData);

        // parseEPPInfo() prepends first_name / last_name / organization (all null
        // because the mock response has no int/loc sections).  The remaining keys
        // follow the order defined in the $returns mapping of contactInfo().
        $this->assertSame($result, [
            'first_name'  => null,
            'last_name'   => null,
            'organization' => null,
            'epp_id'      => $this->eppId,
            'password'    => 'rQ4&lP7*rZ',
            'fax_phone'   => '+357.95713635',
            'voice_phone' => '+357.95713635',
            'statuses'    => [
                'ok'     => null,
                'linked' => null,
            ],
            'email'       => 'silverfires21.me@whoisprotectservice.net',
            'name'        => 'WhoisProtectService.net',
            'country'     => 'CY',
            'city'        => 'Limassol',
            'roid'        => 'C2865751-AGRS',
            'postal_code' => '3025',
            'street1'     => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'result_msg'  => 'Command completed successfully',
            'result_code' => '1000',
            'result_lang' => 'en-US',
            'server_trid' => 'SRW-425500000011746893',
            'client_trid' => 'AA-00',
        ]);
    }
}
