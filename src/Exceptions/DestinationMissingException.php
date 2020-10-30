<?php

namespace CodeGreenCreative\SamlIdp\Exceptions;

use InvalidArgumentException;
use Throwable;

class DestinationMissingException extends InvalidArgumentException
{
    /**
     * [__construct description]
     * @param string|null    $message  [description]
     * @param Throwable|null $previous [description]
     * @param array          $headers  [description]
     * @param int|integer    $code     [description]
     */
    public function __construct(string $message = null, Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
