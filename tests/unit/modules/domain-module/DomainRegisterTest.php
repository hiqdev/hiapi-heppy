<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\tests\unit\TestCase;

class DomainRegisterTest extends TestCase
{
    private $domain = 'silverfires21.me';

    private $apiData = [
        'domain'     => 'silverfires21.me',
        'password'   => 'adf-AA01',
        'period'     => 1,
        'registrant' => '2024202',
        'admin'      => '2024202',
        'tech'       => '2024202',
        'billing'    => '2024202',
        'coupon'     => null,
        'product'    => null,
        'product_id' => null,
        'client_id'  => 2024202,
        'object'     => 'domain',
        'license'    => null,
        'nss'        => [
            'ns1.silverfires1.me',
            'ns2.silverfires1.me',
        ],
        'client'     => 'solex',
        'zone_id'    => 1000219,
        'type'       => 'register',
        'wait'       => 100,
        'id'         => 25844511,
        '_uuid'      => '5bf58698dd9b3',
    ];

    public function testDomainRegister()
    {
        $domainModule = $this->mockModule(DomainModule::class, [
            [
                'methodName' => 'domainPrepareContacts',
                'inputData'  => $this->apiData,
                'outputData' => array_merge($this->apiData, [
                    'registrant_remote_id' => 'MR_25844511',
                    'admin_remote_id'      => 'MR_25844511',
                    'tech_remote_id'       => 'MR_25844511',
                    'billing_remote_id'    => 'MR_25844511',
                ]),
            ],
        ]);

        $tool = $this->createTool([
            'command'    => 'domain:create',
            'name'       => $this->domain,
            'period'     => 1,
            'registrant' => 'MR_25844511',
            'admin'      => 'MR_25844511',
            'tech'       => 'MR_25844511',
            'billing'    => 'MR_25844511',
            'pw'         => 'adf-AA01',
            'nss'        => [
                0 => 'ns1.silverfires1.me',
                1 => 'ns2.silverfires1.me',
            ],
        ], $this->addCommonSuccessResponse([
            'name'   => $this->domain,
            'crDate' => '2018-11-21T17:08:00.0Z',
            'exDate' => '2019-11-21T17:08:00.0Z',
        ]));

        $domainModule->setTool($tool);
        $tool->setModule('domain', $domainModule);

        $result = $tool->domainRegister($this->apiData);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'domain'          => $this->domain,
            'created_date'    => '2018-11-21T17:08:00.0Z',
            'expiration_date' => '2019-11-21T17:08:00.0Z',
        ]));
    }
}
