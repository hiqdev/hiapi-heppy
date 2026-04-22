<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\FeeExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\TestCase;

class FeeExtensionTest extends TestCase
{
    private function makeExtension(string $version = '0.7'): FeeExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        $tool->method('getCurrency')->willReturn('USD');
        return new FeeExtension(['version' => $version], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:check → applicable'                      => ['domain:check',    [],                    true],
            'domain:create → applicable'                     => ['domain:create',   [],                    true],
            'domain:renew → applicable'                      => ['domain:renew',    [],                    true],
            'domain:transfer op=request → applicable'        => ['domain:transfer', ['op' => 'request'],   true],
            'domain:transfer op=query → applicable'          => ['domain:transfer', ['op' => 'query'],     true],
            'domain:transfer no op → not applicable'         => ['domain:transfer', [],                    false],
            'domain:update → not applicable'                 => ['domain:update',   [],                    false],
            'contact:create → not applicable'                => ['contact:create',  [],                    false],
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

    public function testAddExtensionAddsCheckExtension(): void
    {
        $ext  = $this->makeExtension('0.7');
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->addExtension('domain:check', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('fee0.7:check', $added['command']);
        $this->assertSame('USD', $added['currency']);
        $this->assertSame('create', $added['action']); // domain:check defaults to 'create' action
    }

    public function testAddExtensionAddsCreateExtension(): void
    {
        $ext  = $this->makeExtension('0.6');
        $data = ['name' => 'example.me', 'extensions' => [], 'fee' => '15.00', 'period' => 2];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('fee0.6:create', $added['command']);
        $this->assertSame('15.00', $added['fee']);
        $this->assertSame(2, $added['period']);
        $this->assertSame('create', $added['action']);
    }

    public function testAddExtensionSkipsWhenVersionEmpty(): void
    {
        $ext  = $this->makeExtension('');
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->addExtension('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionSkipsWhenWithoutExtIsTrue(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'withoutExt' => true];

        $result = $ext->addExtension('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionSkipsWhenPriceExtensionAlreadyPresent(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'name'       => 'example.me',
            'extensions' => [['command' => 'price:check', 'fee' => '10.00']],
        ];

        $result = $ext->addExtension('domain:check', $data);

        // No fee extension added — price already covers it
        $this->assertCount(1, $result['extensions']);
        $this->assertSame('price:check', $result['extensions'][0]['command']);
    }

    public function testAddExtensionSkipsNonCheckWhenNoFee(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionUsesCustomAction(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => [], 'fee' => '5.00', 'fee-action' => 'transfer'];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertSame('transfer', $result['extensions'][0]['action']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->apply('domain:check', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertStringStartsWith('fee', $result['extensions'][0]['command']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->apply('domain:update', $data);

        $this->assertEmpty($result['extensions']);
    }
}
