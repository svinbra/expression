<?php declare(strict_types=1);

namespace Expression;

use Expression\AST\ASTDeclaration;
use Expression\AST\ASTIdentifier;
use Expression\AST\ASTLiteral;
use Expression\AST\ASTNode;
use Expression\AST\ASTOperator;

//define('DEBUG', 1);

class Expression
{
    /** @var string $expression */
    private $expression;
    /** @var int */
    private $column;
    /** @var int */
    private $expressionLength;
    /** @var \Expression\Stack */
    public $expressionStack;
    /** @var \Expression\Stack */
    public $operatorStack;
    /** @var array */
    private $variables = [];
    /** @var array */
    private $declarations = [];

    /**
     * PoExpressionEngine constructor.
     *
     * @param string $expression
     *
     * @throws \Expression\CompilerError
     */
    public function __construct(string $expression)
    {
        $this->expression = trim($expression);
        $this->expressionLength = strlen($this->expression);

        $this->column = 0;
        $this->expressionStack = new Stack();
        $this->operatorStack = new Stack();
        $this->variables = [];

        $tokens = token_get_all($this->expression);
        $this->declarations = $this->compile();
    }

    /**
     * Evaluate the expression with the supplied variable n.
     *
     * @param int $n
     *
     * @return array
     * @throws \Expression\CompilerError
     */
    public function eval(int $n): array
    {
        $this->variables['n'] = $n;

        /** @var \Expression\AST\ASTNode $declaration */
        foreach ($this->declarations as $declaration) {
            $this->variables[$declaration->leftNode->name] = $this->solveExpression($declaration->rightNode)->value;
        }

        return $this->variables;
    }

    /**
     * Compiles multiple expressions and stores them in an abstract syntax tree.
     *
     * @return array
     * @throws \Expression\CompilerError
     */
    private function compile(): array
    {
        // n needs to be defined, but since we only parse the expression here we don't care about the value
        // itself, the correct value is set when the user runs the eval function.
        $this->variables['n'] = 0;
        $root = [];

        while (!$this->isEmpty()) {
            if ($this->expect(Token::VARIABLE)) {
                $identifier = $this->eatToken();
                if ($this->expect(Token::ASSIGNMENT)) {
                    $this->eatToken();
                    $rightNode = $this->parseExpression();
                    if ($this->expect(Token::END_OF_STATEMENT)) {
                        $this->eatToken();
                    } else {
                        $this->error("End of statement missing");
                    }
                    $leftNode = new ASTIdentifier($identifier->string);

                    $node = new ASTDeclaration($leftNode, $rightNode);
                    $root[] = $node;
                }
            } else {
                $this->error('Expected identifier');
            }
        }
        return $root;
    }

    /**
     * Parse one expression, bail on any token that isn't supported here. We assume
     * those tokens are handled elsewhere.
     *
     * @return \Expression\AST\ASTNode
     * @throws \Expression\CompilerError
     */
    private function parseExpression(): ASTNode
    {
        $unknownToken = false;

        while (!$this->isEmpty() && !$unknownToken) {
            $currentToken = $this->peekToken();

            switch ($currentToken->type) {
                case Token::ASSIGNMENT:
                    $this->error("Assignment not allowed here");
                    break;

                case Token::LITERAL:
                    $node = new ASTLiteral($this->eatToken()->value);
                    $this->expressionStack->push($node);
                    break;

                case Token::VARIABLE:
                    $token = $this->eatToken();
                    $node = new ASTIdentifier($token->string);
                    $this->expressionStack->push($node);
                    break;

                case Token::CLOSE_BRACKET:
                    // A closing bracket triggers a chain of pushes to the expression stack
                    // because whatever is inside the bracket overrides the precedence order of
                    // what surrounds it.
                    $this->eatToken();
                    $operator = null;
                    $pushedToStack = false;
                    while (
                        $this->operatorStack->size() > 0 &&
                        !($operator = $this->operatorStack->peek())->ofType(Token::OPEN_BRACKET)
                    ) {
                        $this->pushOperatorOnExpressionStack();
                        $pushedToStack = true;
                    }
                    if (!$operator || !$operator->ofType(Token::OPEN_BRACKET)) {
                        $this->error("Expected open bracket before closing bracket");
                    }
                    if (!$pushedToStack) {
                        $this->error("Empty bracket not allowed");
                    }
                    $this->operatorStack->pop(); // OPEN_PARENTHESES
                    break;

                case Token::OPEN_BRACKET:
                case Token::MULTIPLICATION:
                case Token::DIVISION:
                case Token::MODULUS:
                case Token::ADDITION:
                case Token::SUBTRACTION:
                case Token::GREATER_THAN:
                case Token::LESS_THAN:
                case Token::GREATER_THAN_OR_EQUAL:
                case Token::LESS_THAN_OR_EQUAL:
                case Token::LOGIC_AND:
                case Token::LOGIC_OR:
                case Token::TERNARY:
                case Token::TERNARY_SEPARATOR:
                case Token::EQUAL:
                case Token::NOT_EQUAL:
                    $currentOperator = $this->eatToken();
                    $stackOperator = $this->operatorStack->peek();

                    // If what is on top of the stack has higher (lower number) or equal precedence compared to
                    // the current operator AND it's not right associative or open bracket then push that
                    // operator and it's operands to the expression stack first.
                    while ($this->operatorStack->size() > 0 &&
                        ($stackOperator->precedence() < $currentOperator->precedence() ||
                            $stackOperator->precedence() == $currentOperator->precedence()) &&
                        !$stackOperator->isRightAssociative() &&
                        !$stackOperator->ofType(Token::OPEN_BRACKET)
                    ) {
                        $this->pushOperatorOnExpressionStack();
                        $stackOperator = $this->operatorStack->peek();
                    }

                    $this->operatorStack->push($currentOperator);
                    break;

                default:
                    $unknownToken = true;
            }
        }

        while ($this->operatorStack->size() > 0) {
            $this->pushOperatorOnExpressionStack();
        }

        if (defined('DEBUG')) $this->printNode($this->expressionStack->peek());

        return $this->expressionStack->pop();
    }

    private function pushOperatorOnExpressionStack()
    {
        $operator = $this->operatorStack->pop();

        $right = $this->expressionStack->pop();
        $left = $this->expressionStack->pop();
        $node = new ASTOperator($operator, $left, $right);
        $this->expressionStack->push($node);
    }

    /**
     * @param \Expression\AST\ASTNode $node
     *
     * @return \Expression\AST\ASTNode
     * @throws \Expression\CompilerError
     */
    private function solveExpression(ASTNode $node): ASTNode
    {
        $result = null;

        if ($node instanceof ASTLiteral) {
            $result = $node;
        } elseif ($node instanceof ASTIdentifier) {
            if (isset($this->variables[$node->name])) {
                $result = new ASTLiteral($this->variables[$node->name]);
            } else {
                $this->error("Identifier '{$node->name}' not declared");
            }
        } elseif ($node->ofType(Token::TERNARY)) {
            $leftNode = $this->solveExpression($node->leftNode);
            if ($leftNode->value > 0) {
                $result = $this->solveExpression($node->rightNode->leftNode);
            } else {
                $result = $this->solveExpression($node->rightNode->rightNode);
            }
        } elseif ($node instanceof ASTOperator) {
            $leftNode = $this->solveExpression($node->leftNode);
            $rightNode = $this->solveExpression($node->rightNode);

            $value = 0;
            switch ($node->type) {
                case Token::ADDITION:
                    $value = $leftNode->value + $rightNode->value;
                    break;
                case Token::SUBTRACTION:
                    $value = $leftNode->value - $rightNode->value;
                    break;
                case Token::MULTIPLICATION:
                    $value = $leftNode->value * $rightNode->value;
                    break;
                case Token::DIVISION:
                    $value = (int)$leftNode->value / $rightNode->value;
                    break;
                case Token::MODULUS:
                    $value = $leftNode->value % $rightNode->value;
                    break;
                case Token::GREATER_THAN:
                    $value = (int)($leftNode->value > $rightNode->value);
                    break;
                case Token::LESS_THAN:
                    $value = (int)($leftNode->value < $rightNode->value);
                    break;
                case Token::GREATER_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value >= $rightNode->value);
                    break;
                case Token::LESS_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value <= $rightNode->value);
                    break;
                case Token::EQUAL:
                    $value = (int)($leftNode->value === $rightNode->value);
                    break;
                case Token::NOT_EQUAL:
                    $value = (int)($leftNode->value !== $rightNode->value);
                    break;
                case Token::LOGIC_AND:
                    $value = (int)($leftNode->value && $rightNode->value);
                    break;
                case Token::LOGIC_OR:
                    $value = (int)($leftNode->value || $rightNode->value);
                    break;
                default:
                    $token = new Token();
                    $token->type = $node->type;
                    $this->error("{$token} not allowed here");
            }

            $result = new ASTLiteral($value);

        }

        return $result;
    }

    /**
     * @param int $tokenType
     *
     * @return bool
     * @throws \Expression\CompilerError
     */
    private function expect(int $tokenType): bool
    {
        $token = $this->peekToken();
        return !is_null($token) && $token->type === $tokenType;
    }

    /**
     * @return \Expression\Token|null
     * @throws \Expression\CompilerError
     */
    private function eatToken(): ?Token
    {
        while (substr($this->expression, $this->column, 1) === ' ') {
            $this->column += 1;
        }
        if ($this->column >= $this->expressionLength) return null;

        $result = new Token();
        if ($this->equals('==')) {
            $result->type = Token::EQUAL;
        } elseif ($this->equals('!=')) {
            $result->type = Token::NOT_EQUAL;
        } elseif ($this->equals('>=')) {
            $result->type = Token::GREATER_THAN_OR_EQUAL;
        } elseif ($this->equals('<=')) {
            $result->type = Token::LESS_THAN_OR_EQUAL;
        } elseif ($this->equals('>')) {
            $result->type = Token::GREATER_THAN;
        } elseif ($this->equals('<')) {
            $result->type = Token::LESS_THAN;
        } elseif ($this->equals('=')) {
            $result->type = Token::ASSIGNMENT;
        } elseif ($this->equals('+')) {
            $result->type = Token::ADDITION;
        } elseif ($this->equals('-')) {
            $result->type = Token::SUBTRACTION;
        } elseif ($this->equals('*')) {
            $result->type = Token::MULTIPLICATION;
        } elseif ($this->equals('/')) {
            $result->type = Token::DIVISION;
        } elseif ($this->equals('%')) {
            $result->type = Token::MODULUS;
        } elseif ($this->equals('(')) {
            $result->type = Token::OPEN_BRACKET;
        } elseif ($this->equals(')')) {
            $result->type = Token::CLOSE_BRACKET;
        } elseif ($this->equals('?')) {
            $result->type = Token::TERNARY;
        } elseif ($this->equals(':')) {
            $result->type = Token::TERNARY_SEPARATOR;
        } elseif ($this->equals('&&')) {
            $result->type = Token::LOGIC_AND;
        } elseif ($this->equals('||')) {
            $result->type = Token::LOGIC_OR;
        } elseif ($this->equals(';')) {
            $result->type = Token::END_OF_STATEMENT;
        } elseif (is_numeric(substr($this->expression, $this->column, 1))) {
            $start = $this->column;
            while (is_numeric(substr($this->expression, $this->column, 1))) {
                $this->column += 1;
            }
            $result->type = Token::LITERAL;
            $result->value = (int)substr($this->expression, $start, $this->column - $start);
        } elseif (ctype_alpha(substr($this->expression, $this->column, 1))) {
            $start = $this->column;
            while (ctype_alnum(substr($this->expression, $this->column, 1))) {
                $this->column += 1;
            }
            $result->type = Token::VARIABLE;
            $result->string = substr($this->expression, $start, $this->column - $start);
        } else {
            $this->error("Syntax error, unknown symbol");
        }

        return $result;
    }

    /**
     * @return \Expression\Token|null
     * @throws \Expression\CompilerError
     */
    private function peekToken(): ?Token
    {
        $storeColumn = $this->column;
        $token = $this->eatToken();
        $this->column = $storeColumn;
        return $token;
    }

    private function equals($needle)
    {
        $result = false;
        if (substr($this->expression, $this->column, strlen($needle)) === $needle) {
            $this->column += strlen($needle);
            $result = true;
        }

        return $result;
    }

    private function isEmpty(): bool
    {
        return $this->expressionLength === 0 || $this->column >= $this->expressionLength;
    }

    private function printNode(ASTNode $node)
    {
        echo "<div style=\"margin-left: 50px;\">";
        if ($node->type === Token::LITERAL) {
            echo $node->value;
        } else {
            $token = new Token();
            $token->type = $node->type;
            echo $token;
            if ($node->leftNode) $this->printNode($node->leftNode);
        }
        if ($node->rightNode) $this->printNode($node->rightNode);
        echo "</div>";
    }

    /**
     * @param string $message
     *
     * @throws \Expression\CompilerError
     */
    private function error(string $message)
    {
        throw new CompilerError("Error on column {$this->column}: $message<br>" . substr($this->expression, $this->column));
    }
}
