<?php


namespace Z99Parser\Exceptions;


use RuntimeException;
use Throwable;
use Z99Parser\Token;


class ParserException extends RuntimeException
{
    /**
     * @var Token
     */
    private $token;

    public function __construct(string $message, Token $token, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->token = $token;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}