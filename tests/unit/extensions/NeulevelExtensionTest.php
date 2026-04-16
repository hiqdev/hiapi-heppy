<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\NeulevelExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NeulevelExtensionTest extends TestCase
{
    private function makeExtension(string $version = '1.0'): NeulevelExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new NeulevelExtension(['version' => $version], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:create → applicable'   => ['domain:create',   true],
            'domain:update → applicable'   => ['domain:update',   true],
            'contact:create → applicable'  => ['contact:create',  true],
            'contact:update → applicable'  => ['contact:update',  true],
            'domain:check → not applicable'  => ['domain:check',  false],
            'domain:delete → not applicable' => ['domain:delete', false],
            'host:create → not applicable'   => ['host:create',   false],
        ];
    }

    #[DataProvider('isApplicableProvider')]
    public function testIsApplicable(string $command, bool $expected): void
    {
        $ext = $this->makeExtension();
        $this->assertSame($expected, $ext->isApplicable($command, []));
    }

    // -------------------------------------------------------------------
    // addExtension
    // -------------------------------------------------------------------

    public function testAddExtensionAppendsNeulevelData(): void
    {
        $ext  = $this->makeExtension('1.0');
        $data = [
            'extensions' => [],
            'neulevel'   => 'someValue',
        ];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('neulevel1.0:default', $added['command']);
        $this->assertSame('someValue', $added['neulevel']);
    }

    public function testAddExtensionUsesVersionInCommand(): void
    {
        $ext  = $this->makeExtension('2.0');
        $data = ['extensions' => [], 'neulevel' => 'data'];

        $result = $ext->addExtension('contact:create', $data);

        $this->assertSame('neulevel2.0:default', $result['extensions'][0]['command']);
    }

    public function testAddExtensionSkipsWhenNeulevelEmpty(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'neulevel' => 'val'];

        $result = $ext->apply('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertStringEndsWith(':default', $result['extensions'][0]['command']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'neulevel' => 'val'];

        $result = $ext->apply('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }
}
