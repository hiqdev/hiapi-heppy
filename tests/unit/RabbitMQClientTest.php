<?php

namespace hiapi\heppy\tests\unit;

use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\RabbitMQConnectionFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RabbitMQClientTest extends TestCase
{
    private const REPLY = ['result_code' => '1000'];

    private ?RabbitMQClient $client = null;

    /** @var AMQPMessage[] messages that reached RabbitMQ, in order */
    private array $published = [];

    private int $opened = 0;

    /**
     * @param \Throwable[] $publishErrors thrown by the first publishes, one per connection, in order
     */
    private function createClient(array $publishErrors = [], ?\Throwable $waitError = null): RabbitMQClient
    {
        $factory = $this->createMock(RabbitMQConnectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use (&$publishErrors, $waitError): AbstractConnection {
            $this->opened++;
            $error = array_shift($publishErrors);

            $channel = $this->createMock(AMQPChannel::class);
            $channel->method('queue_declare')->willReturn(['reply-' . $this->opened, 0, 0]);
            $channel->method('basic_publish')->willReturnCallback(function (AMQPMessage $message) use ($error): void {
                if ($error !== null) {
                    throw $error;
                }
                $this->published[] = $message;
            });
            $channel->method('wait')->willReturnCallback(function () use ($waitError): void {
                if ($waitError !== null) {
                    throw $waitError;
                }
                $this->client->onResponse(new AMQPMessage(json_encode(self::REPLY), [
                    'correlation_id' => end($this->published)->get('correlation_id'),
                ]));
            });

            $connection = $this->createMock(AbstractConnection::class);
            $connection->method('channel')->willReturn($channel);

            return $connection;
        });

        $this->client = new RabbitMQClient([[]], 'heppy', $factory);

        return $this->client;
    }

    public function testRequestReturnsTheReply(): void
    {
        $this->assertSame(self::REPLY, $this->createClient()->request(['command' => 'epp:hello']));
        $this->assertSame(1, $this->opened);
        $this->assertCount(1, $this->published);
    }

    public function testStaleConnectionIsReopenedAndTheRequestPublishedOnce(): void
    {
        $client = $this->createClient([new AMQPHeartbeatMissedException('Missed server heartbeat')]);

        $this->assertSame(self::REPLY, $client->request(['command' => 'domain:info', 'name' => 'example.com']));
        $this->assertSame(2, $this->opened);
        $this->assertCount(1, $this->published);
        $this->assertSame('reply-2', $this->published[0]->get('reply_to'));
        $this->assertSame('{"command":"domain:info","name":"example.com"}', $this->published[0]->getBody());
    }

    public function testMissedHeartbeatWhileWaitingForTheReplyIsNotRetried(): void
    {
        $client = $this->createClient([], new AMQPHeartbeatMissedException('Missed server heartbeat'));

        try {
            $client->request(['command' => 'domain:renew']);
            $this->fail('AMQPHeartbeatMissedException expected');
        } catch (AMQPHeartbeatMissedException $e) {
            $this->assertSame(1, $this->opened);
            $this->assertCount(1, $this->published);
        }
    }

    public function testStaleConnectionTwiceInARowFails(): void
    {
        $client = $this->createClient([
            new AMQPHeartbeatMissedException('Missed server heartbeat'),
            new AMQPHeartbeatMissedException('Missed server heartbeat'),
        ]);

        $this->expectException(AMQPHeartbeatMissedException::class);

        $client->request(['command' => 'domain:info']);
    }
}
