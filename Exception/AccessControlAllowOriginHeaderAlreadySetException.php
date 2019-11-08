<?php

namespace TickTackk\RouteOnSubdomain\Exception;

use Throwable;

/**
 * Class AccessControlAllowOriginHeaderAlreadySetException
 *
 * @package TickTackk\RouteOnSubdomain\Exception
 */
class AccessControlAllowOriginHeaderAlreadySetException extends \RuntimeException
{
    /**
     * AccessControlAllowOriginHeaderAlreadySetException constructor.
     *
     * @param string         $existingValue
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $existingValue, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("'Access-Control-Allow-Origin' header has already been set to {$existingValue}", $code, $previous);
    }
}