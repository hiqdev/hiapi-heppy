<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\exceptions\EppErrorException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * HQD-577: when heppy answers with an error instead of an EPP reply, its reason
 * must reach the exception (and Sentry), not be replaced by "no answer".
 */
#[AllowMockObjectsWithoutExpectations]
class HeppyToolErrorReplyTest extends TestCase
{
    public function testHeppyErrorIsKept(): void
    {
        $error = 'EPP: no reply from ctldepp.verisign-grs.com in 15.0s to clTRID AA-1, connection dropped';
        $tool = $this->createTool([], ['_error' => $error]);

        $this->expectException(EppErrorException::class);
        $this->expectExceptionMessage("failed heppy request: $error");

        $tool->commonRequest('domain:check', ['names' => ['example.com']]);
    }

    public function testEmptyReplyIsNoAnswer(): void
    {
        $tool = $this->createTool([], []);

        $this->expectException(EppErrorException::class);
        $this->expectExceptionMessage('failed heppy request: no answer');

        $tool->commonRequest('domain:check', ['names' => ['example.com']]);
    }
}
