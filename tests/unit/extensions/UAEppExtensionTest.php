<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\UAEppExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UAEppExtensionTest extends TestCase
{
    private function makeExtension(): UAEppExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new UAEppExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain_hm:create → applicable'  => ['domain_hm:create', true],
            'domain_hm:update → applicable'  => ['domain_hm:update', true],
            'host_hm:delete → applicable'    => ['host_hm:delete',   true],
            'domain:create → not applicable' => ['domain:create',     false],
            'host:delete → not applicable'   => ['host:delete',       false],
            'contact:create → not applicable'=> ['contact:create',    false],
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

    public function testAddExtensionAddsDomainCreateExtension(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'licence' => 'LIC-001', 'license' => 'LIC-001'];

        $result = $ext->addExtension('domain_hm:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('uaepp:create', $added['command']);
        $this->assertSame('LIC-001',      $added['license']);
    }

    public function testAddExtensionAddsDomainUpdateExtension(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'licence' => 'LIC-002', 'license' => 'LIC-002'];

        $result = $ext->addExtension('domain_hm:update', $data);

        $this->assertSame('uaepp:update', $result['extensions'][0]['command']);
    }

    public function testAddExtensionAddsHostDeleteExtension(): void
    {
        // host_hm:delete has no 'licence' check — it always adds the extension
        $ext  = $this->makeExtension();
        $data = ['extensions' => []];

        $result = $ext->addExtension('host_hm:delete', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('uaepp:delete', $result['extensions'][0]['command']);
    }

    public function testAddExtensionSkipsDomainCommandWhenNoLicence(): void
    {
        // 'licence' key is the gate; 'license' is the value stored in the extension
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'license' => 'LIC-001']; // 'licence' key missing

        $result = $ext->addExtension('domain_hm:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionCommandReplacesObjectPrefix(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'licence' => 'L', 'license' => 'L'];

        $result = $ext->addExtension('domain_hm:create', $data);

        $this->assertSame('uaepp:create', $result['extensions'][0]['command']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsDomainExtensionWhenLicencePresent(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'licence' => 'LIC', 'license' => 'LIC'];

        $result = $ext->apply('domain_hm:create', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('uaepp:create', $result['extensions'][0]['command']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => [], 'licence' => 'LIC', 'license' => 'LIC'];

        $result = $ext->apply('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testApplyAddsHostExtensionWithoutLicence(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => []];

        $result = $ext->apply('host_hm:delete', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('uaepp:delete', $result['extensions'][0]['command']);
    }
}
