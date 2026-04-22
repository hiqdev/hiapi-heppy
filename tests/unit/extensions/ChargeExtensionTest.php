<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\ChargeExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\TestCase;

class ChargeExtensionTest extends TestCase
{
    private function makeExtension(): ChargeExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new ChargeExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:create → applicable'                        => ['domain:create', [],                  true],
            'domain:renew → applicable'                         => ['domain:renew',  [],                  true],
            'domain:restore → applicable'                       => ['domain:restore', [],                 true],
            'domain:transfer op=request → applicable'           => ['domain:transfer', ['op' => 'request'], true],
            'domain:transfer op=query → applicable'             => ['domain:transfer', ['op' => 'query'],   true],
            'domain:update op=restore → applicable'             => ['domain:update',  ['op' => 'restore'],  true],
            'domain:transfer no op → not applicable'            => ['domain:transfer', [],                false],
            'domain:update no op → not applicable'              => ['domain:update',  [],                 false],
            'domain:check → not applicable'                     => ['domain:check',  [],                  false],
            'contact:create → not applicable'                   => ['contact:create', [],                 false],
        ];
    }

    /** @dataProvider isApplicableProvider */
    public function testIsApplicable(string $command, array $data, bool $expected): void
    {
        $ext = $this->makeExtension();
        $this->assertSame($expected, $ext->isApplicable($command, $data));
    }

    // -------------------------------------------------------------------
    // addExtension
    // -------------------------------------------------------------------

    public function testAddExtensionAddsChargeData(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'extensions'    => [],
            'fee'           => '10.00',
            'period'        => 1,
            'category_name' => 'PREMIUM_DOMAIN_CREATE',
        ];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('charge:create', $added['command']);
        $this->assertSame('10.00', $added['amount']);
        $this->assertSame(1, $added['period']);
        $this->assertSame('PREMIUM_DOMAIN_CREATE', $added['category_name']);
    }

    public function testAddExtensionCommandSuffixFromFullCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'fee' => '5.00', 'category_name' => 'CAT'];

        $result = $ext->addExtension('domain:renew', $data);

        $this->assertSame('charge:renew', $result['extensions'][0]['command']);
    }

    public function testAddExtensionSkipsWhenNoFee(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'category_name' => 'CAT'];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionSkipsWhenWithoutExtIsTrue(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'fee' => '10.00', 'withoutExt' => true];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionFallsBackToAmountForPeriod(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'fee' => '10.00', 'amount' => 2, 'category_name' => null];

        $result = $ext->addExtension('domain:create', $data);

        // No 'period' key → falls back to $data['amount']
        $this->assertSame(2, $result['extensions'][0]['period']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'fee' => '10.00', 'period' => 1, 'category_name' => 'CAT'];

        $result = $ext->apply('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('charge:create', $result['extensions'][0]['command']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'fee' => '10.00'];

        $result = $ext->apply('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }
}
