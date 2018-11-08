<?php

namespace hiapi\heppy\exceptions;

/**
 * Thrown when method call in a wrong way
 */
class InvalidCallException  extends \BadMethodCallException implements ExceptionInterface
{
}
