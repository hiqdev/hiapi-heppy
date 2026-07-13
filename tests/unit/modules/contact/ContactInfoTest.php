<?php

namespace hiapi\heppy\tests\unit\modules\contact;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ContactInfoTest extends ContactTestCase
{
    public function testContactInfo()
    {
        $street = 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601';

        $tool = $this->createTool([
            'id'      => $this->eppId,
            'command' => 'contact:info',
            'pw'      => 'rQ4&lP7*rZ',
        ], $this->addCommonSuccessResponse([
            // New heppy format: postalInfo flattened to root AND nested under int/loc
            'int'      => [
                'name' => 'WhoisProtectService.net',
                'org'  => 'PROTECTSERVICE, LTD.',
                'addr' => [
                    'city'    => 'Limassol',
                    'pc'      => '3025',
                    'cc'      => 'CY',
                    'street1' => $street,
                ],
            ],
            'city'     => 'Limassol',
            'fax'      => '+357.95713635',
            'name'     => 'WhoisProtectService.net',
            'org'      => 'PROTECTSERVICE, LTD.',
            'roid'     => 'C2865751-AGRS',
            'cc'       => 'CY',
            'pc'       => '3025',
            'email'    => 'silverfires21.me@whoisprotectservice.net',
            'street1'  => $street,
            'voice'    => '+357.95713635',
            'id'       => $this->eppId,
            // New heppy format: status value is the status name, not null
            'statuses' => [
                'ok'     => 'ok',
                'linked' => 'linked',
            ],
            'pw'       => 'rQ4&lP7*rZ',
        ]));

        $result = $tool->contactInfo($this->contactData);

        // parseEPPInfo() extracts first_name/last_name from int.name (single word →
        // first_name only; last_name falls back to org). organization comes from int.org.
        // Keys from addr are extracted into $data first, so they appear before epp_id/
        // password/etc. which come from $info (commonRequest result).
        // fixStatuses() is only called in DomainModule, so statuses pass through as-is.
        $this->assertSame($result, [
            'first_name'   => 'WhoisProtectService.net',
            'last_name'    => 'PROTECTSERVICE, LTD.',
            'organization' => 'PROTECTSERVICE, LTD.',
            'country'      => 'CY',
            'city'         => 'Limassol',
            'postal_code'  => '3025',
            'street1'      => $street,
            'epp_id'       => $this->eppId,
            'password'     => 'rQ4&lP7*rZ',
            'fax_phone'    => '+357.95713635',
            'voice_phone'  => '+357.95713635',
            'statuses'     => [
                'ok'     => 'ok',
                'linked' => 'linked',
            ],
            'int'          => [
                'name' => 'WhoisProtectService.net',
                'org'  => 'PROTECTSERVICE, LTD.',
                'addr' => [
                    'city'    => 'Limassol',
                    'pc'      => '3025',
                    'cc'      => 'CY',
                    'street1' => $street,
                ],
            ],
            'email'        => 'silverfires21.me@whoisprotectservice.net',
            'name'         => 'WhoisProtectService.net',
            'org'          => 'PROTECTSERVICE, LTD.',
            'roid'         => 'C2865751-AGRS',
            'result_msg'   => 'Command completed successfully',
            'result_code'  => '1000',
            'result_lang'  => 'en-US',
            'server_trid'  => 'SRW-425500000011746893',
            'client_trid'  => 'AA-00',
        ]);
    }
}
