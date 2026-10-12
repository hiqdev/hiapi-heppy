<?php

namespace hiapi\heppy\tests\unit\modules\domain;

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

    public function testDomainRenewAlreadyRenewedInRegistry()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createRenewMismatchTool($domain, '2020-11-09T10:43:04.0Z');

        $result = $tool->domainRenew($this->getRenewRow($domain));

        $this->assertSame('2020-11-09 10:43:04', $result['expiration_date']);
        $this->assertSame('1000', $result['result_code']);
    }

    public function testDomainRenewRegistryExpiryEarlierThanOurs()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createRenewMismatchTool($domain, '2018-11-09T10:43:04.0Z');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('registry expiry date 2018-11-09 is earlier than 2019-11-09');

        $tool->domainRenew($this->getRenewRow($domain));
    }

    public function testEppErrorWithoutMsgReturnsReadableMessage()
    {
        $tool = $this->createTool([], [
            'result_code'   => '2004',
            'result_msg'    => 'Parameter value range error',
            'result_reason' => 'Does not match expiration',
            'clTRID'        => 'AA-00',
            'svTRID'        => 'SRW-425500000011746893',
        ]);

        $this->expectException(\hiapi\heppy\exceptions\EppErrorException::class);
        $this->expectExceptionMessage('Parameter value range error Does not match expiration');

        $tool->commonRequest('domain:renew', ['name' => 'silverfires1.me']);
    }

    private function createRenewMismatchTool(string $domain, string $registryExDate)
    {
        return $this->createTool([], [
            'result_code'   => '2004',
            'result_msg'    => 'Parameter value range error',
            'curExpDate'    => '2019-11-09',
            'result_reason' => 'Does not match expiration',
            'clTRID'        => 'AA-00',
            'svTRID'        => 'SRW-425500000011746893',
        ], [], [
            'domain:check'  => $this->addCommonSuccessResponse([
                'avails' => [$domain => '1'],
            ]),
            'domain:info'   => $this->getStubDomainInfoEppResponse([
                'name'   => $domain,
                'exDate' => $registryExDate,
            ]),
        ]);
    }

    private function getRenewRow(string $domain): array
    {
        return [
            'domain'        => $domain,
            'period'        => '1',
            'expires'       => '2019-11-09',
            'expires_time'  => '2019-11-09 10:43:04',
        ];
    }
}
