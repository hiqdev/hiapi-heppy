<?php

namespace hiapi\heppy\exceptions;

final class EppReplyException extends \Exception implements ExceptionInterface
{
    private const MESSAGE_PREFIX = 'EPP reply: ';

    private const FIELDS = ['result_code', 'result_msg', 'result_reason', 'svTRID', 'clTRID'];

    private const EXTENSIONS = 'extensions';

    public static function fromResponse(array $response): self
    {
        $parts = [];
        foreach (self::FIELDS as $field) {
            if (isset($response[$field]) && is_scalar($response[$field])) {
                $parts[] = $field . '=' . $response[$field];
            }
        }
        if (!empty($response[self::EXTENSIONS]) && is_array($response[self::EXTENSIONS])) {
            $parts[] = self::EXTENSIONS . '=' . json_encode(
                $response[self::EXTENSIONS],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        }

        return new self(self::MESSAGE_PREFIX . implode(', ', $parts), (int) ($response['result_code'] ?? 0));
    }
}
