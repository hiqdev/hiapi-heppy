<?php

namespace hiapi\heppy\tests\unit\modules\poll;

use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class PollModuleTest extends TestCase
{
    private const TRANSFER_MESSAGE = 'Transfer Requested.';

    /** @var string[] poll ops sent to the client, in order */
    private array $sentOps = [];

    /**
     * @param array[] $reqResponses returned one per `req` poll, in order; `ack` always succeeds
     */
    private function createPollTool(array $reqResponses): HeppyToolStub
    {
        $client = $this->createMock(RabbitMQClient::class);
        $client->method('request')->willReturnCallback(function (array $data) use (&$reqResponses) {
            $this->sentOps[] = $data['op'];
            if ($data['op'] === 'ack') {
                return $this->getCommonSuccessResponse();
            }

            return array_shift($reqResponses);
        });

        $tool = new HeppyToolStub($this->mockBase(), []);
        $tool->setClient($client);

        return $tool;
    }

    /**
     * A poll `req` response as heppy returns it: the queue's <msg> lands in result_msg.
     */
    private function getQueuedPollResponse(string $id, string $message): array
    {
        return [
            'result_code' => '1301',
            'result_msg'  => $message,
            'msgID'       => $id,
            'msgCount'    => '2',
            'qDate'       => '2026-10-13T10:57:00.0Z',
            'name'        => 'example.net',
        ];
    }

    public function testPollReqReturnsQueueMessage(): void
    {
        $tool = $this->createPollTool([$this->getQueuedPollResponse('101', self::TRANSFER_MESSAGE)]);

        $poll = $tool->pollReq();

        $this->assertSame(self::TRANSFER_MESSAGE, $poll['message']);
        $this->assertSame('101', $poll['id']);
    }

    public function testPollsDeleteUnsupportedAcksUnsupportedPollsUntilSupportedOne(): void
    {
        $tool = $this->createPollTool([
            $this->getQueuedPollResponse('101', 'Unused Objects Policy'),
            $this->getQueuedPollResponse('102', self::TRANSFER_MESSAGE),
        ]);

        $polls = $tool->pollsDeleteUnsupported();

        $this->assertSame(['101'], array_column($polls, 'id'));
        $this->assertSame(['req', 'ack', 'req'], $this->sentOps);
    }
}
