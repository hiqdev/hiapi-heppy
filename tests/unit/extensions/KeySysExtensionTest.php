<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\KeySysExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class KeySysExtensionTest extends TestCase
{
    private function makeExtension(): KeySysExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new KeySysExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'contact:create → applicable'                      => ['contact:create',   [],                   true],
            'contact:update → applicable'                      => ['contact:update',   [],                   true],
            'domain:create → applicable'                       => ['domain:create',    [],                   true],
            'domain:update → applicable'                       => ['domain:update',    [],                   true],
            'domain:delete → applicable'                       => ['domain:delete',    [],                   true],
            'domain:transfer op=request → applicable'          => ['domain:transfer',  ['op' => 'request'],  true],
            'domain:transfer no op → not applicable'           => ['domain:transfer',  [],                   false],
            'domain:check → not applicable'                    => ['domain:check',     [],                   false],
            'host:create → not applicable'                     => ['host:create',      [],                   false],
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

    public function testAddExtensionAppendsKeysysData(): void
    {
        $ext     = $this->makeExtension();
        $keysys  = ['command' => 'keysys:domain-create', 'accept-premiumprice' => '1'];
        $data    = ['extensions' => [], 'keysys' => $keysys];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame($keysys, $result['extensions'][0]);
    }

    public function testAddExtensionSkipsWhenKeysysEmpty(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionPreservesExistingExtensions(): void
    {
        $ext      = $this->makeExtension();
        $existing = ['command' => 'other:ext'];
        $keysys   = ['command' => 'keysys:domain-create'];
        $data     = ['extensions' => [$existing], 'keysys' => $keysys];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(2, $result['extensions']);
        $this->assertSame($existing, $result['extensions'][0]);
        $this->assertSame($keysys,   $result['extensions'][1]);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForApplicableCommand(): void
    {
        $ext    = $this->makeExtension();
        $keysys = ['command' => 'keysys:domain-create'];
        $data   = ['extensions' => [], 'keysys' => $keysys];

        $result = $ext->apply('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame($keysys, $result['extensions'][0]);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext    = $this->makeExtension();
        $keysys = ['command' => 'keysys:check'];
        $data   = ['extensions' => [], 'keysys' => $keysys];

        $result = $ext->apply('domain:check', $data);

        $this->assertEmpty($result['extensions']);
    }
}
