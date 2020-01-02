<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class Token
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Token
{
    const MAX_LENGTH = 10;

    /**
     * @var string
     */
    private $token;

    /**
     * Token constructor.
     *
     * @param string $token
     */
    public function __construct($token = null)
    {
        $this->token = $token ?: bin2hex(openssl_random_pseudo_bytes(floor(self::MAX_LENGTH / 2)));
    }

    /**
     * Return the status from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->token;
    }
}
