<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\SecDNSExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\TestCase;

class SecDNSExtensionTest extends TestCase
{
    private function makeExtension(): SecDNSExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new SecDNSExtension([], $tool);
    }

    private function makeSecDNSData(array $override = []): array
    {
        return array_merge([
            'xmlns'       => 'urn:ietf:params:xml:ns:secDNS-1.1',
            'command'     => 'add',
            'key_tag'     => 12345,
            'key_alg'     => 5,
            'digest_alg'  => 1,
            'digest'      => 'AABBCCDDEEFF',
            'digest_type' => null,
            'max_sig_life' => null,
            'pub_key'     => null,
        ], $override);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'domain:update → applicable'        => ['domain:update',    true],
            'domain_hm:update → applicable'     => ['domain_hm:update', true],
            'domain:create → not applicable'    => ['domain:create',    false],
            'domain:delete → not applicable'    => ['domain:delete',    false],
            'contact:update → not applicable'   => ['contact:update',   false],
        ];
    }

    /** @dataProvider isApplicableProvider */
    public function testIsApplicable(string $command, bool $expected): void
    {
        $ext = $this->makeExtension();
        $this->assertSame($expected, $ext->isApplicable($command, []));
    }

    // -------------------------------------------------------------------
    // addExtension
    // -------------------------------------------------------------------

    public function testAddExtensionBuildsUpdateExtension(): void
    {
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData();
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        $result = $ext->addExtension('domain:update', $data);

        $this->assertCount(1, $result['extensions']);
        $added = $result['extensions'][0];
        $this->assertSame('urn:ietf:params:xml:ns:secDNS-1.1:update', $added['command']);
        $this->assertArrayHasKey('add', $added);
        $this->assertSame(12345,      $added['add']['keyTag']);
        $this->assertSame(5,          $added['add']['keyAlg']);
        $this->assertSame(1,          $added['add']['digestAlg']);
        $this->assertSame('AABBCCDDEEFF', $added['add']['digest']);
    }

    public function testAddExtensionFiltersNullFields(): void
    {
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData(['max_sig_life' => null, 'pub_key' => null]);
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        $result = $ext->addExtension('domain:update', $data);

        $added = $result['extensions'][0];
        $this->assertArrayNotHasKey('maxSigLife', $added['add']);
        $this->assertArrayNotHasKey('pubKey',     $added['add']);
    }

    public function testAddExtensionSkipsWhenSecDNSEmpty(): void
    {
        $ext  = $this->makeExtension();
        $data = ['extensions' => []];

        $result = $ext->addExtension('domain:update', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionSkipsWhenXmlnsEmpty(): void
    {
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData(['xmlns' => null]);
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        $result = $ext->addExtension('domain:update', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testAddExtensionUsesCreateSuffixWhenCommandContainsCreate(): void
    {
        // SecDNS isApplicable doesn't allow domain:create, but addExtension
        // uses the command string to pick :create vs :update suffix
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData();
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        // Call addExtension directly with a ':create' command to test the branch
        $result = $ext->addExtension('domain:create', $data);

        $added = $result['extensions'][0];
        $this->assertStringEndsWith(':create', $added['command']);
    }

    // -------------------------------------------------------------------
    // apply (integration)
    // -------------------------------------------------------------------

    public function testApplyAddsSecDNSExtensionForUpdate(): void
    {
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData();
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        $result = $ext->apply('domain:update', $data);

        $this->assertCount(1, $result['extensions']);
    }

    public function testApplyDoesNothingForNonApplicableCommand(): void
    {
        $ext    = $this->makeExtension();
        $secDNS = $this->makeSecDNSData();
        $data   = ['extensions' => [], 'secDNS' => $secDNS];

        $result = $ext->apply('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }
}
