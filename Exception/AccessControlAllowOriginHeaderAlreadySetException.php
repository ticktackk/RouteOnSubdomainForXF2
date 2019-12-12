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
     * @var string
     */
    protected $existingValue;

    /**
     * AccessControlAllowOriginHeaderAlreadySetException constructor.
     *
     * @param string         $existingValue
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $existingValue, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("'Access-Control-Allow-Origin' header has already been set.", $code, $previous);

        $this->existingValue = $existingValue;
    }

    /**
     * Retrieves the existing value
     *
     * @return string
     */
    public function getExistingValue()
    {
        return $this->existingValue;
    }
}