<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\modules\DomainModule;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class DomainInfoGoneReplyTest extends TestCase
{
    public static function goneReplies(): array
    {
        return [
            'being deleted at Verisign' => [
                ['result_code' => '2306', 'result_msg' => 'Parameter value policy error', 'result_reason' => 'Verisign Connection Policy'],
                'Parameter value policy error Verisign Connection Policy',
            ],
            'deleted' => [
                ['result_code' => '2303', 'result_msg' => 'Object does not exist'],
                DomainModule::OBJECT_DOES_NOT_EXIST,
            ],
            'at another registrar' => [
                ['result_code' => '2201', 'result_msg' => 'Authorization error'],
                DomainModule::AUTHORIZATION_ERROR,
            ],
        ];
    }

    #[DataProvider('goneReplies')]
    public function testGoneReplyIsReturnedAsAnErrorRow(array $reply, string $error): void
    {
        $tool = $this->createTool([], $this->withTrIds($reply));
        $row = ['domain' => 'example.com', 'id' => 1];

        $this->assertSame(array_merge($row, ['_error' => $error]), $tool->domainInfo($row));
    }

    #[DataProvider('goneReplies')]
    public function testCommandThatNeedsTheInfoStillFailsOnGoneReply(array $reply, string $error): void
    {
        $domain = 'silverfires1.me';
        $tool = $this->createTool([], $this->getCommonSuccessResponse(), [], [
            'domain:check' => $this->addCommonSuccessResponse(['avails' => [$domain => '1']]),
            'domain:info'  => $this->withTrIds($reply),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($error);

        $tool->domainRenew([
            'domain'        => $domain,
            'period'        => '1',
            'expires'       => '2019-11-09',
            'expires_time'  => '2019-11-09 10:43:04',
        ], true);
    }

    public function testOtherErrorStillThrows(): void
    {
        $tool = $this->createTool([], $this->withTrIds([
            'result_code'   => '2306',
            'result_msg'    => 'Parameter value policy error',
            'result_reason' => 'Connection Policy',
        ]));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parameter value policy error Connection Policy');

        $tool->domainInfo(['domain' => 'example.com', 'id' => 1]);
    }

    private function withTrIds(array $reply): array
    {
        return $reply + ['svTRID' => '17223417402-1795520101459', 'clTRID' => 'AA-01'];
    }
}
