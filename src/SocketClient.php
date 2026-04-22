<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy;

use RuntimeException;

/**
 * Unix socket client for direct hEPPy daemon communication.
 *
 * Implements the same 4-byte big-endian length-prefix framing protocol
 * used by heppy's Net.py. Each request opens a new connection.
 */
class SocketClient implements ClientInterface
{
    private const TIMEOUT = 20;

    public function __construct(private readonly string $socketPath)
    {
    }

    public function request(array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $socket = $this->connect();
        try {
            $this->write($socket, $json);
            $response = $this->read($socket);
        } finally {
            socket_close($socket);
        }

        return json_decode($response, true) ?? [];
    }

    private function connect(): \Socket
    {
        $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket === false) {
            throw new RuntimeException('Failed to create Unix socket');
        }
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => self::TIMEOUT, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => self::TIMEOUT, 'usec' => 0]);
        if (!socket_connect($socket, $this->socketPath)) {
            $err = socket_last_error($socket);
            throw new RuntimeException("Failed to connect to {$this->socketPath}: " . socket_strerror($err));
        }

        return $socket;
    }

    private function write(\Socket $socket, string $data): void
    {
        $data .= "\r\n";
        $frame = pack('N', strlen($data) + 4) . $data;
        $total = strlen($frame);
        $sent = 0;
        while ($sent < $total) {
            $n = socket_write($socket, substr($frame, $sent), $total - $sent);
            if ($n === false) {
                throw new RuntimeException('Socket write failed: ' . socket_strerror(socket_last_error($socket)));
            }
            $sent += $n;
        }
    }

    private function read(\Socket $socket): string
    {
        $header = $this->recv($socket, 4);
        [, $length] = unpack('N', $header);
        $buffer = $this->recv($socket, $length - 4);

        return rtrim($buffer, "\r\n");
    }

    private function recv(\Socket $socket, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = socket_read($socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Connection closed while reading from socket');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }
}
