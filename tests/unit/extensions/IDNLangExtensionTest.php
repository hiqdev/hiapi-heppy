<?php

namespace hiapi\heppy\tests\unit\extensions;

use hiapi\heppy\extensions\IDNLangExtension;
use hiapi\heppy\HeppyTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IDNLangExtensionTest extends TestCase
{
    private function makeExtension(): IDNLangExtension
    {
        $tool = $this->createStub(HeppyTool::class);
        return new IDNLangExtension([], $tool);
    }

    // -------------------------------------------------------------------
    // isApplicable
    // -------------------------------------------------------------------

    public static function isApplicableProvider(): array
    {
        return [
            'IDN domain, domain:create → applicable'        => ['domain:create', 'тест.укр',    true],
            'ASCII domain, domain:create → not applicable'  => ['domain:create', 'google.com',  false],
            'IDN domain, domain:update → not applicable'    => ['domain:update', 'тест.укр',    false],
            'IDN punycode, domain:create → applicable'      => ['domain:create', 'xn--e1aybc.xn--j1amh', true],
        ];
    }

    #[DataProvider('isApplicableProvider')]
    public function testIsApplicable(string $command, string $domain, bool $expected): void
    {
        $ext = $this->makeExtension();
        $this->assertSame($expected, $ext->isApplicable($command, ['name' => $domain]));
    }

    // -------------------------------------------------------------------
    // addExtension
    // -------------------------------------------------------------------

    public static function addExtensionProvider(): array
    {
        return [
            'Ukrainian IDN → UKR'         => ['тест.укр',      'UKR'],
            'Russian IDN → RUS'           => ['рыба.рф',        'RUS'],
            'Chinese IDN → CHI'           => ['谷歌.中国',        'CHI'],
            'Japanese IDN → JPN'          => ['グーグル.jp',      'JPN'],
            'Arabic IDN → ARA'            => ['دبي.امارات',      'ARA'],
        ];
    }

    #[DataProvider('addExtensionProvider')]
    public function testAddExtensionDetectsLanguage(string $domain, string $expectedLang): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => $domain, 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertNotEmpty($result['extensions'], 'Extension must be added for IDN domain');
        $added = end($result['extensions']);
        $this->assertSame('idnLang', $added['command']);
        $this->assertSame($expectedLang, $added['language']);
    }

    public function testAddExtensionSkipsEnglishDomain(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'google.com', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $this->assertEmpty($result['extensions'], 'No extension for ASCII/ENG domain');
    }

    public function testAddExtensionRespectsExplicitLanguage(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'тест.укр', 'language' => 'RUS', 'extensions' => []];

        $result = $ext->addExtension('domain:create', $data);

        $added = end($result['extensions']);
        $this->assertSame('RUS', $added['language'], 'Explicit language overrides detection');
    }

    // -------------------------------------------------------------------
    // apply (integration: isApplicable + addExtension)
    // -------------------------------------------------------------------

    public function testApplyAddsExtensionForIdnDomainCreate(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'тест.укр', 'extensions' => []];

        $result = $ext->apply('domain:create', $data);

        $this->assertNotEmpty($result['extensions']);
        $this->assertSame('UKR', end($result['extensions'])['language']);
    }

    public function testApplySkipsNonIdnDomain(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'google.com', 'extensions' => []];

        $result = $ext->apply('domain:create', $data);

        $this->assertEmpty($result['extensions']);
    }

    public function testApplySkipsNonCreateCommand(): void
    {
        $ext  = $this->makeExtension();
        $data = ['name' => 'тест.укр', 'extensions' => []];

        $result = $ext->apply('domain:update', $data);

        $this->assertEmpty($result['extensions']);
    }
}
