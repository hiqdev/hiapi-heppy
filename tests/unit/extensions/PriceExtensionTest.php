<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\PriceExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PriceExtensionTest extends TestCase
{
    private function makeExtension(string $currency = 'USD'): PriceExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        $tool->method('getCurrency')->willReturn($currency);
        return new PriceExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:check → applicable'                    => ['domain:check',    [],                   true],
            'domain:create → applicable'                   => ['domain:create',   [],                   true],
            'domain:renew → applicable'                    => ['domain:renew',    [],                   true],
            'domain:transfer op=request → applicable'      => ['domain:transfer', ['op' => 'request'],  true],
            'domain:transfer op=query → applicable'        => ['domain:transfer', ['op' => 'query'],    true],
            'domain:transfer no op → not applicable'       => ['domain:transfer', [],                   false],
            'domain:update → not applicable'               => ['domain:update',   [],                   false],
            'contact:create → not applicable'              => ['contact:create',  [],                   false],
        ];
    }

    #[DataProvider('isApplicableProvider')]
    public function testIsApplicable(string $command, array $data, bool $expected): void
    {
        $ext = $this->makeExtension();
        $this->assertSame($expected, $ext->isApplicable($command, $data));
    }

    // -------------------------------------------------------------------
    // addExtension
    // -------------------------------------------------------------------

    public function testAddExtensionAddsPriceExtension(): void
    {
        $ext  = $this->makeExtension('EUR');
        $data = [
            'name'       => 'example.me',
            'extensions' => [],
            'withoutExt' => false,
            'fee'        => '20.00',
            'period'     => 1,
        ];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('price:create', $added['command']);
        $this->assertSame('example.me',  $added['name']);
        $this->assertSame('EUR',         $added['currency']);
        $this->assertSame('20.00',       $added['fee']);
        $this->assertSame(1,             $added['period']);
    }

    public function testAddExtensionReplacesExistingFeeExtensions(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'name'       => 'example.me',
            'withoutExt' => false,
            'extensions' => [
                ['command' => 'fee0.7:check', 'fee' => '10.00'],
                ['command' => 'other:ext'],
            ],
        ];

        $result = $ext->addExtension('domain:check', $data);

        // fee0.7:check is removed; other:ext stays; price:check is appended
        $commands = array_column($result['extensions'], 'command');
        $this->assertNotContains('fee0.7:check', $commands);
        $this->assertContains('other:ext',        $commands);
        $this->assertContains('price:check',      $commands);
    }

    public function testAddExtensionSkipsWhenWithoutExtIsTrue(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'name'       => 'example.me',
            'extensions' => [],
            'withoutExt' => true,
        ];

        $result = $ext->addExtension('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionCommandSuffixForCheck(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'withoutExt' => false];

        $result = $ext->addExtension('domain:check', $data);

        $this->assertSame('price:check', $result['extensions'][0]['command']);
    }

    public function testAddExtensionCommandSuffixForRenew(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'withoutExt' => false];

        $result = $ext->addExtension('domain:renew', $data);

        $this->assertSame('price:renew', $result['extensions'][0]['command']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsPriceForApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'withoutExt' => false];

        $result = $ext->apply('domain:check', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('price:check', $result['extensions'][0]['command']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'withoutExt' => false];

        $result = $ext->apply('domain:update', $data);

        $this->assertEmpty($result['extensions']);
    }
}
