<?php


namespace Z99Parser;


use Z99Parser\Exceptions\ParserException;
use Z99Parser\Streams\TokenStreamInterface;


class Parser
{
    /**
     * @var TokenStreamInterface
     */
    private $stream;

    public function __construct(TokenStreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function program(): array
    {
        $root = 'program';
        $result[$root][] = $this->matchOrFail('Program');
        $result[$root][] = $this->matchOrFail('Ident');
        $result[$root][] = $this->matchOrFail('Var');
        $result[$root][] = $this->declareList();
        $result[$root][] = $this->matchOrFail('Semi');
        $result[$root][] = $this->matchOrFail('Begin');
        $result[$root][] = $this->statementList();
        $result[$root][] = $this->matchOrFail('Semi');
        $result[$root][] = $this->matchOrFail('End');
        $result[$root][] = $this->matchOrFail('EOF');

        return $result;
    }

    private function match($lexeme): ?array
    {
        if ($this->stream->lookAhead()->getType() === $lexeme) {
            $array[$lexeme] = $this->stream->next()->getString();
            return $array;
        }

        return null;
    }

    private function matchOrFail($lexeme): array
    {
        if ($result = $this->match($lexeme)) {
            return $result;
        }

        throw new ParserException("Expected $lexeme", $this->stream->lookAhead());
    }

    private function matchOneOfLexeme(array $lexemes): array
    {
        foreach ($lexemes as $lexeme) {
            if ($result = $this->match($lexeme)) {
                return $result;
            }
        }

        throw new ParserException('Expected one of this lexemes ' . implode(', ', $lexemes), $this->stream->lookAhead());
    }

    private function matchOneOfRules(array $rules): array
    {
        foreach ($rules as $rule) {
            if ($result = $this->matchRule($rule)) {
                return $result;
            }
        }

        throw new ParserException('Expected one of this rules ' . implode(', ', $rules), $this->stream->lookAhead());
    }

    private function matchRule(string $rule): ?array
    {
        $position = $this->stream->remember();
        try {
            return $this->$rule();
        } catch (ParserException $e) {
            $this->stream->goTo($position);
        }

        return null;
    }

    public function declareList(): array
    {
        $root = 'declareList';
        $result[$root][] = $this->declaration();

        $this->repeatedMatch(function () use (&$result, $root) {
            $tokens = [$this->matchOrFail('Semi'), $this->declaration()];
            $result[$root][] = $tokens[0];
            $result[$root][] = $tokens[1];
        });

        return $result;
    }

    public function declaration(): array
    {
        $root = 'declaration';
        $result[$root][] = $this->identList();
        $result[$root][] = $this->matchOrFail('Colon');
        $result[$root][] = $this->matchOrFail('Type');

        return $result;
    }

    public function identList(): array
    {
        $root = 'identList';
        $result[$root][] = $this->matchOrFail('Ident');

        $this->repeatedMatch(function () use (&$result, $root) {
            $tokens = [$this->matchOrFail('Comma'), $this->matchOrFail('Ident')];
            $result[$root][] = $tokens[0];
            $result[$root][] = $tokens[1];
        });

        return $result;
    }

    private function repeatedMatch(callable $function): void
    {
        while (true) {
            $position = $this->stream->remember();
            try {
                $function();
            } catch (ParserException $e) {
                $this->stream->goTo($position);
                break;
            }
        }
    }

    public function statementList(): array
    {
        $root = 'statementList';
        $result[$root][] = $this->statement();

        $this->repeatedMatch(function () use (&$result, $root) {
            $tokens = [$this->matchOrFail('Semi'), $this->statement()];
            $result[$root][] = $tokens[0];
            $result[$root][] = $tokens[1];
        });

        return $result;
    }

    public function statement(): array
    {
        // ['assign', 'input', 'output', 'branchStatement', 'repeatStatement']
        $result['statement'] = $this->matchOneOfRules(['assign', 'input', 'output', 'branchStatement', 'repeatStatement']);
        return $result;
    }

    public function input(): array
    {
        $root = 'input';
        $result[$root][] = $this->matchOrFail('Read');
        $result[$root][] = $this->matchOrFail('LBracket');
        $result[$root][] = $this->identList();
        $result[$root][] = $this->matchOrFail('RBracket');
        return $result;
    }

    public function output(): array
    {
        $root = 'output';
        $result[$root][] = $this->matchOrFail('Write');
        $result[$root][] = $this->matchOrFail('LBracket');
        $result[$root][] = $this->identList();
        $result[$root][] = $this->matchOrFail('RBracket');
        return $result;
    }

    public function branchStatement(): array
    {
        $root = 'branchStatement';
        $result[$root][] = $this->matchOrFail('If');
        $result[$root][] = $this->expression();
        $result[$root][] = $this->matchOrFail('Then');
        $result[$root][] = $this->statementList();
        $result[$root][] = $this->matchOrFail('Semi');
        $result[$root][] = $this->matchOrFail('Fi');
        return $result;
    }

    public function repeatStatement(): array
    {
        $root = 'repeatStatement';
        $result[$root][] = $this->matchOrFail('Repeat');
        $result[$root][] = $this->statementList();
        $result[$root][] = $this->matchOrFail('Semi');
        $result[$root][] = $this->matchOrFail('Until');
        $result[$root][] = $this->boolExpr();
        return $result;
    }

    public function assign(): array
    {
        $root = 'assign';
        $result[$root][] = $this->matchOrFail('Ident');
        $result[$root][] = $this->matchOrFail('AssignOp');
        $result[$root][] = $this->expression();
        return $result;
    }

    public function expression(): array
    {
        $root = 'expression';
        $result[$root][] = $this->matchOneOfRules(['arithmExpression', 'boolExpr']);
        return $result;
    }

    public function arithmExpression(): array
    {
        $root = 'arithmExpression';
        $position = $this->stream->remember();
        try {
            $result[$root][] = $this->term();
            $result[$root][] = $this->addOp();
            $result[$root][] = $this->arithmExpression();
        } catch (ParserException $e ) {
            $this->stream->goTo($position);
            $result[$root] = null;
            $result[$root][] = $this->term();
        }

        return $result;
    }

    public function boolExpr(): array
    {
        $root = 'boolExpr';
        $result[$root][] = $this->arithmExpression();
        $result[$root][] = $this->matchOrFail('RelOp');
        $result[$root][] = $this->arithmExpression();
        return $result;
    }

    public function term(): array
    {
        $root = 'term';
        $position = $this->stream->remember();
        try {
            $result[$root][] = $this->factor();
            $result[$root][] = $this->multOp();
            $result[$root][] = $this->term();
        } catch (ParserException $e) {
            $this->stream->goTo($position);
            $result[$root] = null;
            $result[$root][] = $this->factor();
        }

        return $result;
    }

    public function factor(): array
    {
        $root = 'factor';
        if ($match = $this->match('Ident')) {
            $result[$root][] = $match;
            return $result;
        }

        if ($match = $this->matchRule('constant')) {
            $result[$root][] = $match;
            return $result;
        }

        try {
            $result[$root][] = $this->matchOrFail('LBracket');
            $result[$root][] = $this->arithmExpression();
            $result[$root][] = $this->matchOrFail('RBracket');
        } catch (ParserException $e) {
            throw new ParserException('Expected Ident, constant or (arithmExpression)', $this->stream->lookAhead());
        }

        return $result;
    }

    public function addOp(): array
    {
        $root = 'addOp';
        $result[$root][] = $this->matchOneOfLexeme(['Plus', 'Minus']);
        return $result;
    }

    public function multOp(): array
    {
        $root = 'multOp';
        $result[$root][] = $this->matchOneOfLexeme(['Star', 'Slash']);
        return $result;
    }

    public function constant()
    {
        $root = 'constant';
        $result[$root][] = $this->matchOneOfLexeme(['IntNum', 'RealNum', 'BoolConst']);
        return $result;
    }
}