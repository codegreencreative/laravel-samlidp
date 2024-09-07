<?php

namespace CodeGreenCreative\SamlIdp\Exceptions;

use Throwable;
use InvalidArgumentException;

class DestinationMissingException extends InvalidArgumentException
{
    /**
     * [__construct description]
     *
     * @param  string|null  $message  [description]
     * @param  Throwable|null  $previous  [description]
     * @param  array  $headers  [description]
     * @param  int|int  $code  [description]
     */
    public function __construct(?string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
