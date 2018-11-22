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

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'epp_id'      => $this->eppId,
            'name'        => 'WhoisProtectService.net',
            'password'    => 'rQ4&lP7*rZ',
            'email'       => 'silverfires21.me@whoisprotectservice.net',
            'fax_phone'   => '+357.95713635',
            'voice_phone' => '+357.95713635',
            'country'     => 'CY',
            'city'        => 'Limassol',
            'roid'        => 'C2865751-AGRS',
            'postal_code' => '3025',
            'street1'     => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'statuses'    => [
                'ok'     => null,
                'linked' => null,
            ],
        ]));
    }
}
