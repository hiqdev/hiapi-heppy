<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\HeppyTool;
use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionMethod;

/**
 * domainsCheck() now iterates domains one-by-one via domainCheck(), which itself
 * makes two _domainCheck() calls per domain.  The result is a map of
 * domain → per-domain check result, not the old combined avails/reasons array.
 */
#[AllowMockObjectsWithoutExpectations]
class DomainsCheckTest extends TestCase
{
    public function testDomainsCheck()
    {
        $domain1  = 'silverfires1.me';
        $domain42 = 'silverfires42.me';

        // Each _domainCheck() call sees this raw EPP response.
        // domain1 is taken (avail=0), domain42 is free (avail=1).
        $checkResponse = $this->addCommonSuccessResponse([
            'avails'  => [
                $domain1  => '0',
                $domain42 => '1',
            ],
            'reasons' => [
                $domain1 => 'In use',
            ],
        ]);

        $tool = $this->createTool([], $checkResponse);

        $result = $tool->domainsCheck([
            'domains' => [
                0 => $domain1,
                1 => $domain42,
            ],
        ]);

        // domain1 is taken: domainCheck returns early with avail+reason
        $this->assertSame(0,       $result[$domain1]['avail']);
        $this->assertSame('In use', $result[$domain1]['reason']);

        // domain42 is free: domainCheck calls _parseCheckFee which returns avail only
        $this->assertSame(1, $result[$domain42]['avail']);
    }

    public function testNotAccreditedReasonOfFirstCheckWinsOverFeeCheckReason()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => DomainModule::ZONE_NOT_ACCREDITED]],
            [
                'avails'  => [$domain => '0'],
                'reasons' => [$domain => 'In use'],
                'fee'     => [$domain => ['class' => DomainModule::DOMAIN_STANDART]],
            ],
        );

        $result = $tool->domainsCheck(['domains' => [$domain]]);

        $this->assertSame(['avail' => 0, 'reason' => DomainModule::ZONE_NOT_ACCREDITED], $result[$domain]);
    }

    public function testNotAccreditedReasonOfFirstCheckWinsOverPriceCheckReason()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => DomainModule::ZONE_NOT_ACCREDITED]],
            [
                'avails'  => [$domain => '0'],
                'reasons' => [$domain => 'In use'],
                'price'   => [$domain => ['createPrice' => '10.00', 'premium' => '0']],
            ],
        );

        $result = $tool->domainsCheck(['domains' => [$domain]]);

        $this->assertSame(0, $result[$domain]['avail']);
        $this->assertSame(DomainModule::ZONE_NOT_ACCREDITED, $result[$domain]['reason']);
    }

    public function testFeeCheckReasonWhenFirstCheckHasNone()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '1']],
            [
                'avails'  => [$domain => '0'],
                'reasons' => [$domain => 'In use'],
                'fee'     => [$domain => ['class' => DomainModule::DOMAIN_STANDART]],
            ],
        );

        $result = $tool->domainsCheck(['domains' => [$domain]]);

        $this->assertSame(['avail' => 0, 'reason' => 'In use'], $result[$domain]);
    }

    /**
     * The second check's reply has no avails (HQD-549): `_parseCheckCharge()` still
     * warns about it here; this test only pins the reason fallback.
     */
    public function testFeeCheckWithoutAvailsKeepsFirstCheckReason()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => 'In use']],
            [],
        );

        $result = $this->callDomainCheck($tool, $domain, 'renew');

        $this->assertSame(['avail' => 0, 'reason' => 'In use'], $result);
    }

    public function testPriceCheckKeepsRegistryReasonOfUnavailableDomain()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => 'In use']],
            [
                'avails' => [$domain => '0'],
                'price'  => [$domain => ['renewPrice' => '10.00', 'premium' => '0']],
            ],
        );

        $result = $this->callDomainCheck($tool, $domain, 'renew');

        $this->assertSame(0, $result['avail']);
        $this->assertSame('In use', $result['reason']);
        $this->assertNull($result['fee']);
    }

    public function testPremiumReasonWinsForRegisteredPremiumDomain()
    {
        $domain = 'premium.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => 'In use']],
            [
                'avails' => [$domain => '0'],
                'fee'    => [$domain => ['class' => DomainModule::DOMAIN_PREMIUM, 'renew' => '500.00']],
            ],
        );

        $result = $this->callDomainCheck($tool, $domain, 'renew');

        $this->assertSame(0, $result['avail']);
        $this->assertSame(DomainModule::DOMAIN_PREMIUM_REASON, $result['reason']);
    }

    public function testAvailableDomainHasNoReason()
    {
        $domain = 'silverfires42.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '1']],
            [
                'avails'  => [$domain => '1'],
                'reasons' => [$domain => 'Available'],
                'fee'     => [$domain => ['class' => DomainModule::DOMAIN_STANDART]],
            ],
        );

        $result = $tool->domainsCheck(['domains' => [$domain]]);

        $this->assertSame(['avail' => 1], $result[$domain]);
    }

    public function testCheckChargeReadsRegistryReason()
    {
        $domain = 'silverfires1.me';
        $tool = $this->createCheckTool(
            ['avails' => [$domain => '0'], 'reasons' => [$domain => 'In use']],
            [],
        );
        $module = $tool->getModule('domain');
        $method = new ReflectionMethod($module, '_domainCheck');

        $result = $method->invoke($module, $domain, true);

        $this->assertSame('In use', $result['reason']);
    }

    /**
     * Tool whose client answers the first domain check (no extension) with $firstCheck
     * and the second one (fee/price extension) with $secondCheck.
     */
    private function createCheckTool(array $firstCheck, array $secondCheck): HeppyTool
    {
        $client = $this->createMock(RabbitMQClient::class);
        $client->method('request')->willReturnCallback(
            fn (array $data) => $this->addCommonSuccessResponse(
                ($data['withoutExt'] ?? false) === true ? $firstCheck : $secondCheck
            )
        );

        $tool = new HeppyToolStub($this->mockBase(), []);
        $tool->setClient($client);

        return $tool;
    }

    private function callDomainCheck(HeppyTool $tool, string $domain, ?string $command = null): array
    {
        $module = $tool->getModule('domain');
        $method = new ReflectionMethod($module, 'domainCheck');

        return $method->invoke($module, $domain, $command);
    }
}
