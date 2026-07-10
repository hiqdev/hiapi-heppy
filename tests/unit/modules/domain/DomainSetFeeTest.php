<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use Exception;
use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use yii\caching\CacheInterface;

/**
 * Tests for DomainModule::_domainSetFee and the standard_price fee guard.
 *
 * All tests run without any real registry connection: the cache is stubbed
 * to return controlled domainCheck data; no RabbitMQ/EPP socket is opened.
 */
#[AllowMockObjectsWithoutExpectations]
class DomainSetFeeTest extends TestCase
{
    private string $domain = 'premium.me';

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /**
     * Build a HeppyTool stub that returns $cacheData from getCache()->getOrSet().
     *
     * @param string[] $extraMethods additional methods to stub on HeppyTool
     */
    private function mockTool(array $cacheData, array $extraMethods = []): HeppyTool
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('getOrSet')->willReturn($cacheData);

        /** @var HeppyTool $tool */
        $tool = $this->getMockBuilder(HeppyTool::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_unique(array_merge(
                ['getCache', 'getObjects', 'getExtensions', 'getBase'],
                $extraMethods
            )))
            ->getMock();
        $tool->method('getCache')->willReturn($cache);
        $tool->method('getObjects')->willReturn([]);
        $tool->method('getExtensions')->willReturn([]);
        $tool->method('getBase')->willReturn(null);

        return $tool;
    }

    private function makeModule(array $cacheData): DomainModule
    {
        return new DomainModule($this->mockTool($cacheData));
    }

    /** Call protected _domainSetFee via reflection. */
    private function callSetFee(DomainModule $module, array $row, string $op): array
    {
        $method = new ReflectionMethod(DomainModule::class, '_domainSetFee');
        $method->setAccessible(true);
        return $method->invoke($module, $row, $op);
    }

    private function premiumCheckData(string $fee): array
    {
        return [
            'reason'        => DomainModule::DOMAIN_PREMIUM_REASON,
            'fee'           => ['fee' => $fee],
            'category'      => DomainModule::DOMAIN_PREMIUM,
            'category_name' => 'Premium',
        ];
    }

    // -------------------------------------------------------------------
    // _domainSetFee unit tests
    // -------------------------------------------------------------------

    public function testNonPremiumDomainRowUnchanged(): void
    {
        $module = $this->makeModule(['reason' => null]);
        $row    = ['domain' => $this->domain, 'standard_price' => '100.00'];

        $result = $this->callSetFee($module, $row, 'renew');

        $this->assertSame($row, $result);
        $this->assertArrayNotHasKey('fee', $result);
    }

    public function testPremiumFeeWithinStandardPriceForRenew(): void
    {
        $module = $this->makeModule($this->premiumCheckData('50.00'));
        $row    = ['domain' => $this->domain, 'standard_price' => '100.00'];

        $result = $this->callSetFee($module, $row, 'renew');

        $this->assertSame('50.00', $result['fee']);
        $this->assertArrayNotHasKey('reason', $result, 'No premium reason when fee <= standard_price on renew');
    }

    public function testPremiumFeeExceedsStandardPrice(): void
    {
        $module = $this->makeModule($this->premiumCheckData('200.00'));
        $row    = ['domain' => $this->domain, 'standard_price' => '100.00'];

        $result = $this->callSetFee($module, $row, 'create');

        $this->assertSame('200.00', $result['fee']);
        $this->assertSame(DomainModule::DOMAIN_PREMIUM_REASON, $result['reason']);
    }

    // -------------------------------------------------------------------
    // domainRenew fee guard — verifies the standard_price key is read
    // -------------------------------------------------------------------

    public static function renewFeeProvider(): array
    {
        return [
            'fee below standard_price — no exception' => ['50.00',  '100.00', false],
            'fee equal standard_price — no exception'  => ['100.00', '100.00', false],
            'fee above standard_price — throws'        => ['200.00', '100.00', true],
        ];
    }

    #[DataProvider('renewFeeProvider')]
    public function testDomainRenewFeeGuard(string $fee, string $standardPrice, bool $expectException): void
    {
        if ($expectException) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage(DomainModule::DOMAIN_PREMIUM_REASON);
        }

        $tool = $this->mockTool($this->premiumCheckData($fee), ['getDateTime']);
        $tool->method('getDateTime')->willReturn(new \DateTimeImmutable('2026-01-01'));

        $module = $this->getMockBuilder(DomainModule::class)
            ->setConstructorArgs([$tool])
            ->onlyMethods(['_domainRenew'])
            ->getMock();
        $module->method('_domainRenew')->willReturn([
            'domain'          => $this->domain,
            'expiration_date' => '2027-01-01',
            'result_code'     => '1000',
        ]);

        $result = $module->domainRenew([
            'domain'         => $this->domain,
            'standard_price' => $standardPrice,
            'period'         => '1',
            'expires'        => '2026-01-01',
            'expires_time'   => '2026-01-01 00:00:00',
            'client_id'      => '1',
        ]);

        if (!$expectException) {
            $this->assertSame($this->domain, $result['domain']);
        }
    }
}
