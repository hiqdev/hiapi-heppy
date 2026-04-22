<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\RGPExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RGPExtensionTest extends TestCase
{
    private function makeExtension(): RGPExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new RGPExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:restore → applicable'                      => ['domain:restore',    [],                   true],
            'domain_hm:restore → applicable'                   => ['domain_hm:restore', [],                   true],
            'domain:update op=restore → applicable'            => ['domain:update',     ['op' => 'restore'],  true],
            'domain_hm:update op=restore → applicable'         => ['domain_hm:update',  ['op' => 'restore'],  true],
            'domain:update no op → not applicable'             => ['domain:update',     [],                   false],
            'domain:create → not applicable'                   => ['domain:create',     [],                   false],
            'domain:delete → not applicable'                   => ['domain:delete',     [],                   false],
            'contact:create → not applicable'                  => ['contact:create',    [],                   false],
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

    public function testAddExtensionMovesRgpToExtensions(): void
    {
        $ext  = $this->makeExtension();
        $rgp  = ['command' => 'rgp:restore', 'op' => 'request'];
        $data = ['extensions' => [], 'rgp' => $rgp];

        $result = $ext->addExtension('domain:restore', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame($rgp, $result['extensions'][0]);
        $this->assertArrayNotHasKey('rgp', $result);
    }

    public function testAddExtensionFiltersEmptyRgpFields(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'extensions' => [],
            'rgp'        => ['command' => 'rgp:restore', 'op' => null, 'reason' => ''],
        ];

        $result = $ext->addExtension('domain:restore', $data);

        $added = $result['extensions'][0];
        $this->assertArrayHasKey('command', $added);
        // array_filter removes null/empty values
        $this->assertArrayNotHasKey('op',     $added);
        $this->assertArrayNotHasKey('reason', $added);
    }

    public function testAddExtensionPreservesExistingExtensions(): void
    {
        $ext      = $this->makeExtension();
        $existing = ['command' => 'other:ext'];
        $data     = ['extensions' => [$existing], 'rgp' => ['command' => 'rgp:restore']];

        $result = $ext->addExtension('domain:restore', $data);

        $this->assertCount(2, $result['extensions']);
        $this->assertSame($existing, $result['extensions'][0]);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyMovesRgpForApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'rgp' => ['command' => 'rgp:restore', 'op' => 'request']];

        $result = $ext->apply('domain:restore', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertArrayNotHasKey('rgp', $result);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'rgp' => ['command' => 'rgp:restore']];

        $result = $ext->apply('domain:create', $data);

        $this->assertEmpty($result['extensions']);
        $this->assertArrayHasKey('rgp', $result);
    }
}
