<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\NamestoreExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NamestoreExtensionTest extends TestCase
{
    private function makeExtension(): NamestoreExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new NamestoreExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:create → applicable'   => ['domain:create',   true],
            'domain:check → applicable'    => ['domain:check',    true],
            'domain:info → applicable'     => ['domain:info',     true],
            'domain:update → applicable'   => ['domain:update',   true],
            'domain:delete → applicable'   => ['domain:delete',   true],
            'domain:renew → applicable'    => ['domain:renew',    true],
            'domain:restore → applicable'  => ['domain:restore',  true],
            'domain:transfer → applicable' => ['domain:transfer', true],
            'host:create → applicable'     => ['host:create',     true],
            'host:info → applicable'       => ['host:info',       true],
            'host:delete → applicable'     => ['host:delete',     true],
            'contact:create → applicable'  => ['contact:create',  true],
            'contact:info → applicable'    => ['contact:info',    true],
            'contact:delete → applicable'  => ['contact:delete',  true],
            'foo:bar → not applicable'     => ['foo:bar',         false],
            'session:login → not applicable' => ['session:login', false],
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

    public function testAddExtensionBuildsSubProductFromDomainName(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.me', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('namestoreExt', $added['command']);
        $this->assertSame('dotME', $added['subProduct']);
    }

    public function testAddExtensionUsesExplicitZone(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.com', 'zone' => 'NET', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        // explicit $data['zone'] takes precedence
        $this->assertSame('dotNET', $result['extensions'][0]['subProduct']);
    }

    public function testAddExtensionUppercasesZone(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'example.com', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertSame('dotCOM', $result['extensions'][0]['subProduct']);
    }

    public function testAddExtensionSkipsWhenNoZoneResolvable(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => []]; // no 'name', 'domain', 'names', or 'zone'

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionClearsContactsWhenFlagSet(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'name'         => 'example.com',
            'extensions'   => [],
            'clearContact' => true,
            'registrant'   => 'REG001',
            'admin'        => 'ADM001',
            'tech'         => 'TECH001',
            'billing'      => 'BILL001',
        ];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertArrayNotHasKey('registrant', $result);
        $this->assertArrayNotHasKey('admin',      $result);
        $this->assertArrayNotHasKey('tech',       $result);
        $this->assertArrayNotHasKey('billing',    $result);
        $this->assertCount(1, $result['extensions']);
    }

    public function testAddExtensionDoesNotClearContactsWhenFlagNotSet(): void
    {
        $ext  = $this->makeExtension();
        $data = [
            'name'       => 'example.com',
            'extensions' => [],
            'registrant' => 'REG001',
        ];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertArrayHasKey('registrant', $result);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForDomainCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'test.net', 'extensions' => []];

        $result = $ext->apply('domain:check', $data);

        $this->assertCount(1, $result['extensions']);
        $this->assertSame('dotNET', $result['extensions'][0]['subProduct']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'test.net', 'extensions' => []];

        $result = $ext->apply('session:login', $data);

        $this->assertEmpty($result['extensions']);
    }
}
