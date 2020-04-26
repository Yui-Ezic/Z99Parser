<?php


namespace Z99Parser\Streams;

use Z99Parser\Token;
use OutOfRangeException;

interface TokenStreamInterface
{
    public const EOF = "\u{0000}";

    /**
     * Goes to the next character from input stream.
     * Return EOF in the end of stream.
     *
     * @return Token
     * @throws OutOfRangeException
     */
    public function next() : Token;

    /**
     * Look next character from input stream.
     * Return EOF in the end of stream.
     *
     * @return Token
     * @throws OutOfRangeException
     */
    public function lookAhead() : Token;

    /**
     * Returns to previous position.
     */
    public function back() : void;

    /**
     * Returns current token
     */
    public function current() : Token;

    /**
     * Returns id of current position
     * @return int
     */
    public function remember() : int;

    /**
     * Go to position id (which can be received from remember() method)
     * @param $id
     * @return mixed
     */
    public function goTo($id) : void;
}