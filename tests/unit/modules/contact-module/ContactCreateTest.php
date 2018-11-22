<?php

namespace hiapi\heppy\tests\unit\modules\contact_module;

use hiapi\heppy\tests\unit\TestCase;

class ContactCreateTest extends TestCase
{
    private $eppId = 'MR_25844511f';

    private $contactData = [
        'id'            => '25844511',
        'epp_id'        => 'MR_25844511f',
        'type'          => 'domain',
        'obj_id'        => '25844511',
        'type_id'       => '10532410',
        'state_id'      => '1000248',
        'roid'          => null,
        'client_id'     => '2024202',
        'seller_id'     => '1004697',
        'client'        => 'solex',
        'name'          => 'WhoisProtectService.net',
        'first_name'    => 'WhoisProtectService.net',
        'last_name'     => '',
        'birth_date'    => null,
        'email'         => 'silverfires21.me@whoisprotectservice.net',
        'abuse_email'   => null,
        'passport_no'   => null,
        'passport_date' => null,
        'passport_by'   => null,
        'organization'  => 'PROTECTSERVICE, LTD.',
        'street1'       => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
        'street2'       => null,
        'street3'       => null,
        'city'          => 'Limassol',
        'province'      => null,
        'province_name' => null,
        'postal_code'   => '3025',
        'country'       => 'cy',
        'country_name'  => 'Cyprus',
        'voice_phone'   => '+357.95713635',
        'fax_phone'     => '+357.95713635',
        'password'      => 'rQ4&lP7*rZ',
        'created_date'  => null,
        'updated_date'  => null,
        'seller'        => 'ahnames',
        'client_type'   => 'client',
        'create_time'   => '2018-11-21 16:23:52.845585',
        'update_time'   => '2018-11-22 14:34:18.974071',
        'remote'        => '',
    ];

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
