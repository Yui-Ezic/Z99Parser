<?php


namespace Z99Parser\Streams;

use Z99Parser\Token;
use OutOfRangeException;

class ArrayStream implements TokenStreamInterface
{
    /**
     * @var Token[]
     */
    private $tokens;

    /**
     * @var int
     */
    private $position;

    /**
     * @var int
     */
    private $length;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->length = count($tokens);
        $this->position = -1;
    }

    public static function fromJsonFile($filename): self
    {
        $file = file_get_contents($filename);
        $objects = json_decode($file, true);
        $tokens = [];
        foreach ($objects as $object) {
            $token = new Token($object['line'], $object['string'], $object['type'], $object['index']);
            $tokens[] = $token;
        }

        return new static($tokens);
    }

    /**
     * @inheritDoc
     */
    public function next(): Token
    {
        $this->position++;
        return $this->get($this->position);
    }

    /**
     * @inheritDoc
     */
    public function lookAhead(): Token
    {
        return $this->get($this->position + 1);
    }

    /**
     * @inheritDoc
     */
    public function back(): void
    {
        if ($this->position > 0)
        {
            $this->position--;
        }
    }

    /**
     * @inheritDoc
     */
    public function current(): Token
    {
        return $this->get($this->position);
    }

    /**
     * @inheritDoc
     */
    public function remember(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function goTo($id) : void
    {
        $this->position = $id;
    }


    private function get($position) : Token
    {
        if ($position === $this->length) {
            return $this->getEmptyToken();
        }

        if ($position > $this->length) {
            throw new OutOfRangeException('Token stream already ended.');
        }

        return $this->tokens[$position];
    }


    private function getEmptyToken() : Token
    {
        return new Token(0, TokenStreamInterface::EOF, 'EOF');
    }
}