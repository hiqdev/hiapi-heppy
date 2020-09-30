<?php

namespace hiapi\heppy\exceptions;

use Throwable;

/**
 * Thrown when EPP result_code indicates error.
 */
class EppErrorException  extends \Exception implements ExceptionInterface
{
    public function __construct(string $message = "", int $code = null, array $data = null, Throwable $previous = NULL)
    {
        $message = $data['text'] ?? $message;

        parent::__construct($message, $code, $previous);
    }
}
