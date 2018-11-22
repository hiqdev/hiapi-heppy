<?php

namespace hiapi\heppy\tests\unit\modules\contact_module;

use hiapi\heppy\tests\unit\TestCase;

class ContactInfoTest extends TestCase
{
    public function testContactInfo()
    {
        $eppId = 'MR_25844511';

        $tool = $this->createTool([
            'id'      => $eppId,
            'command' => 'contact:info',
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
            'id'       => $eppId,
            'statuses' => [
                'ok'     => null,
                'linked' => null,
            ],
            'pw'       => 'bL0@kX8!mB',
        ]));

        $result = $tool->contactInfo([
            'id'       => '25844511',
            'epp_id'   => $eppId,
            'password' => '',
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'epp_id'      => $eppId,
            'name'        => 'WhoisProtectService.net',
            'password'    => 'bL0@kX8!mB',
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
