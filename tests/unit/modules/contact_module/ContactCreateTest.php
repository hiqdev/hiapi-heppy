<?php

namespace hiapi\heppy\tests\unit\modules\contact_module;

class ContactCreateTest extends ContactTestCase
{
    public function testContactCreate()
    {
        $tool = $this->createTool([
            'id'      => $this->eppId,
            'name'    => 'WhoisProtectService.net',
            'email'   => 'silverfires21.me@whoisprotectservice.net',
            'voice'   => '+357.95713635',
            'fax'     => '+357.95713635',
            'cc'      => 'cy',
            'city'    => 'Limassol',
            'street1' => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'pc'      => '3025',
            'pw'      => 'rQ4&lP7*rZ',
            'command' => 'contact:create',
        ], $this->addCommonSuccessResponse([
            'crDate' => '2018-11-22T14:42:12.0Z',
            'id'     => $this->eppId,
        ]));

        $result = $tool->contactCreate($this->contactData);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'epp_id'       => $this->eppId,
            'created_date' => '2018-11-22T14:42:12.0Z',
        ]));
    }
}
