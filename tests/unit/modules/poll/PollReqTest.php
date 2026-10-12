<?php

namespace hiapi\heppy\tests\unit\modules\poll;

use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class PollReqTest extends TestCase
{
    public function testPollReqReturnsMessageText()
    {
        // reply shape as produced by heppy v2026.07 for a transfer-rejected poll message
        $tool = $this->createTool([], [
            'clTRID'      => 'XXXX-11',
            'svTRID'      => 'SRW-425500000011746893',
            'result_code' => '1301',
            'result_msg'  => 'Transfer Rejected.',
            'name'        => 'example.com',
            'trStatus'    => 'clientRejected',
            'reID'        => '1418',
            'reDate'      => '2019-12-24T09:52:32Z',
            'msgCount'    => '1',
            'msgID'       => '80076487',
        ]);

        $result = $tool->pollReq();

        $this->assertSame('Transfer Rejected.', $result['message']);
        $this->assertSame('80076487', $result['id']);
        $this->assertSame('clientRejected', $result['type']);
    }
}
