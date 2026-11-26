<?php

namespace hiapi\heppy\tests\unit\modules\poll;

use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use hiapi\heppy\tests\unit\TestCase;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class PollsGetNewFailureTest extends TestCase
{
    private array $sentOps = [];

    private function createPollTool(array $replies): HeppyToolStub
    {
        $client = $this->createMock(RabbitMQClient::class);
        $client->method('request')->willReturnCallback(function (array $data) use (&$replies) {
            $this->sentOps[] = $data['op'];
            $reply = array_shift($replies);
            if ($reply instanceof \Throwable) {
                throw $reply;
            }

            return $reply;
        });

        $tool = new HeppyToolStub($this->mockBase(), []);
        $tool->setClient($client);

        return $tool;
    }

    private function queued(string $id): array
    {
        return [
            'result_code' => '1301',
            'result_msg'  => 'Transfer Requested.',
            'msgID'       => $id,
            'msgCount'    => '2',
            'qDate'       => '2026-10-13T10:57:00.0Z',
            'name'        => "domain$id.net",
        ];
    }

    private function acked(): array
    {
        return $this->getCommonSuccessResponse();
    }

    private function noReply(): array
    {
        return ['_error' => 'EPP: no reply', '_error_kind' => 'connection'];
    }

    private function refused(): array
    {
        return ['result_code' => '2400', 'result_msg' => 'Command failed'];
    }

    private function unparsable(): array
    {
        return ['_error' => "('unknown tag', 'urn:ietf:params:xml:ns:epp-1.0:foo')"];
    }

    public static function ackWithoutReply(): array
    {
        return [
            'registry gave no reply in time (heppy)' => [
                ['_error' => 'EPP: no reply from epp.example in 15s to clTRID AA-01, connection dropped', '_error_kind' => 'connection'],
            ],
            'registry closed the connection (heppy)' => [
                ['_error' => 'EPP: epp.example closed the connection without a reply, connection dropped', '_error_kind' => 'connection'],
            ],
            'reply to another request (heppy)' => [
                ['_error' => 'EPP reply clTRID AA-00 does not match request clTRID AA-01, connection dropped', '_error_kind' => 'connection'],
            ],
            'malformed reply (heppy)' => [
                ['_error' => 'EPP: malformed XML reply (not well-formed), connection dropped', '_error_kind' => 'connection'],
            ],
            'heppy gave no reply in time (RabbitMQ)' => [
                new AMQPTimeoutException('The connection timed out after 20 sec while awaiting incoming data'),
            ],
            'RabbitMQ connection closed' => [
                new AMQPConnectionClosedException('Broken pipe or closed connection'),
            ],
            'empty reply' => [
                [],
            ],
        ];
    }

    #[DataProvider('ackWithoutReply')]
    public function testAckWithoutReplyKeepsTheMessage(array|\Throwable $reply): void
    {
        $polls = $this->createPollTool([$this->queued('101'), $reply])->pollsGetNew();

        $this->assertSame(['101'], array_column($polls, 'id'));
        $this->assertSame(['req', 'ack'], $this->sentOps);
    }

    #[DataProvider('ackWithoutReply')]
    public function testAckWithoutReplyKeepsTheMessagesAcknowledgedBefore(array|\Throwable $reply): void
    {
        $polls = $this->createPollTool([
            $this->queued('101'), $this->acked(),
            $this->queued('102'), $reply,
        ])->pollsGetNew();

        $this->assertSame(['101', '102'], array_column($polls, 'id'));
        $this->assertSame(['req', 'ack', 'req', 'ack'], $this->sentOps);
    }

    public function testAckReplyHeppyCannotParseKeepsTheMessage(): void
    {
        $polls = $this->createPollTool([
            $this->queued('101'), $this->acked(),
            $this->queued('102'), $this->unparsable(),
        ])->pollsGetNew();

        $this->assertSame(['101', '102'], array_column($polls, 'id'));
    }

    public function testFirstAckReplyHeppyCannotParseKeepsTheMessage(): void
    {
        $polls = $this->createPollTool([$this->queued('101'), $this->unparsable()])->pollsGetNew();

        $this->assertSame(['101'], array_column($polls, 'id'));
    }

    public function testRefusedAckLeavesTheMessageQueuedAndKeepsTheOnesBefore(): void
    {
        $polls = $this->createPollTool([
            $this->queued('101'), $this->acked(),
            $this->queued('102'), $this->refused(),
        ])->pollsGetNew();

        $this->assertSame(['101'], array_column($polls, 'id'));
    }

    public function testRefusedFirstAckThrows(): void
    {
        $tool = $this->createPollTool([$this->queued('101'), $this->refused()]);

        $this->expectExceptionMessage('Command failed');

        $tool->pollsGetNew();
    }

    public function testReqWithoutReplyAfterAnAckKeepsTheMessage(): void
    {
        $polls = $this->createPollTool([$this->queued('101'), $this->acked(), $this->noReply()])->pollsGetNew();

        $this->assertSame(['101'], array_column($polls, 'id'));
        $this->assertSame(['req', 'ack', 'req'], $this->sentOps);
    }

    public function testRefusedReqAfterAnAckKeepsTheMessage(): void
    {
        $polls = $this->createPollTool([$this->queued('101'), $this->acked(), $this->refused()])->pollsGetNew();

        $this->assertSame(['101'], array_column($polls, 'id'));
    }

    public function testFirstReqWithoutReplyThrows(): void
    {
        $tool = $this->createPollTool([$this->noReply()]);

        $this->expectExceptionMessage('EPP: no reply');

        $tool->pollsGetNew();
    }
}
