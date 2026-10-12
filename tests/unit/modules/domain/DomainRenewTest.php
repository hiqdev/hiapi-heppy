<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class DomainRenewTest extends TestCase
{
    public function testDomainRenew()
    {
        $domain = 'silverfires1.me';

        // domain:check is called by _domainSetFee(); return a non-premium result
        $domainCheckResponse = $this->addCommonSuccessResponse([
            'avails' => [$domain => '1'],
        ]);

        $tool = $this->createTool([
            'name'          => $domain,
            'curExpDate'    => '2019-11-09',
            'period'        => '1',
            'command'       => 'domain:renew',
        ], $this->addCommonSuccessResponse([
            'name'          => 'silverfires1.me',
            'exDate'        => '2020-11-09T10:43:04.0Z',
        ]), [], [
            'domain:check' => $domainCheckResponse,
        ]);

        $result = $tool->domainRenew([
            'domain'        => $domain,
            'amount'        => '1',
            'period'        => '1',
            'expires'       => '2019-11-09',
            'coupon'        => null,
            'id'            => 25844450,
            'type'          => 'drenewal',
            'object'        => 'domain',
            'client_id'     => '2024202',
            'seller_id'     => '1004697',
            'expires_time'  => '2019-11-09',   // must be a string, not null
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'domain'            => $domain,
            'expiration_date'   => '2020-11-09T10:43:04.0Z',
        ]));
    }

    /**
     * Contract with heppy: an EPP error reply carries the registry text in
     * `result_msg`. A renewal whose expiry date does not match the registry
     * must re-read the real date and renew again.
     */
    public function testDomainRenewRecoversFromExpiryDateMismatch()
    {
        $domain = 'silverfires1.me';
        $renewals = [];

        $client = $this->createMock(RabbitMQClient::class);
        $client->method('request')->willReturnCallback(function (array $data) use ($domain, &$renewals) {
            switch ($data['command']) {
                case 'domain:check':
                    return $this->addCommonSuccessResponse(['avails' => [$domain => '1']]);
                case 'domain:info':
                    return $this->addCommonSuccessResponse([
                        'name'   => $domain,
                        'exDate' => '2019-11-10T10:43:04.0Z',
                    ]);
                case 'domain:renew':
                    $renewals[] = $data;
                    if (count($renewals) === 1) {
                        return [
                            'result_code'   => '2004',
                            'result_msg'    => 'Parameter value range error',
                            'result_reason' => 'Does not match expiration',
                            'clTRID'        => 'AA-00',
                            'svTRID'        => 'SRW-425500000011746894',
                        ];
                    }
                    return $this->addCommonSuccessResponse([
                        'name'   => $domain,
                        'exDate' => '2020-11-10T10:43:04.0Z',
                    ]);
            }
            $this->fail("unexpected command {$data['command']}");
        });

        $tool = new HeppyToolStub($this->mockBase(), []);
        $tool->setClient($client);

        $result = $tool->domainRenew([
            'domain'         => $domain,
            'period'         => '1',
            'expires'        => '2019-11-09',
            'expires_time'   => '2019-11-09',
            'standard_price' => 0,
        ]);

        $this->assertCount(2, $renewals);
        $this->assertSame('2019-11-09', $renewals[0]['curExpDate']);
        $this->assertSame('2019-11-10', $renewals[1]['curExpDate']);
        $this->assertSame('2020-11-10T10:43:04.0Z', $result['expiration_date']);
    }
}
