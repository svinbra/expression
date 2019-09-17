<?php declare(strict_types=1);

namespace Expression;

use Expression\AST\ASTDeclaration;
use Expression\AST\ASTIdentifier;
use Expression\AST\ASTLiteral;
use Expression\AST\ASTNode;
use Expression\AST\ASTBinaryOperator;

class Expression
{
    /** @var \Expression\Lexer2 */
    private $lexer;
    /** @var \Expression\Stack */
    private $expressionStack;
    /** @var \Expression\Stack */
    private $operatorStack;
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
        $this->lexer = new Lexer2($expression);

        $this->expressionStack = new Stack();
        $this->operatorStack = new Stack();
        $this->variables = [];

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
        $this->variables['$n'] = $n;

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

        while (!$this->lexer->isEmpty()) {
            if ($this->lexer->expect(Lexer2::VARIABLE)) {
                $identifier = $this->lexer->eatToken();
                if ($this->lexer->expect(Lexer2::ASSIGNMENT)) {
                    $this->lexer->eatToken();
                    $rightNode = $this->parseExpression();
                    if ($this->lexer->expect(Lexer2::END_OF_STATEMENT)) {
                        $this->lexer->eatToken();
                    } else {
                        $this->error("End of statement missing");
                    }
                    $leftNode = new ASTIdentifier($identifier->string);

                    $node = new ASTDeclaration($leftNode, $rightNode);
                    $root[] = $node;
                } else {
                    $this->error('Expected assignment');
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

        while (!$this->lexer->isEmpty() && !$unknownToken) {
            $currentToken = $this->lexer->peekToken();

            switch ($currentToken->type) {
                case Lexer2::ASSIGNMENT:
                    $this->error("Assignment not allowed here");
                    break;

                case Lexer2::LITERAL:
                    $node = new ASTLiteral($this->lexer->eatToken()->value);
                    $this->expressionStack->push($node);
                    break;

                case Lexer2::VARIABLE:
                    $token = $this->lexer->eatToken();
                    $node = new ASTIdentifier($token->string);
                    $this->expressionStack->push($node);
                    break;

                case Lexer2::CLOSE_BRACKET:
                    // A closing bracket triggers a chain of pushes to the expression stack
                    // because whatever is inside the bracket overrides the precedence order of
                    // what surrounds it.
                    $this->lexer->eatToken();
                    $operator = null;
                    $pushedToStack = false;
                    while (
                        $this->operatorStack->size() > 0 &&
                        !($operator = $this->operatorStack->peek())->ofType(Lexer2::OPEN_BRACKET)
                    ) {
                        $this->pushBinaryOperatorOnExpressionStack();
                        $pushedToStack = true;
                    }
                    if (!$operator || !$operator->ofType(Lexer2::OPEN_BRACKET)) {
                        $this->error("Expected open bracket before closing bracket");
                    }
                    if (!$pushedToStack) {
                        $this->error("Empty bracket not allowed");
                    }
                    $this->operatorStack->pop(); // OPEN_PARENTHESES
                    break;

                case Lexer2::OPEN_BRACKET:
                case Lexer2::MULTIPLICATION:
                case Lexer2::DIVISION:
                case Lexer2::MODULUS:
                case Lexer2::ADDITION:
                case Lexer2::SUBTRACTION:
                case Lexer2::GREATER_THAN:
                case Lexer2::LESS_THAN:
                case Lexer2::GREATER_THAN_OR_EQUAL:
                case Lexer2::LESS_THAN_OR_EQUAL:
                case Lexer2::LOGIC_AND:
                case Lexer2::LOGIC_OR:
                case Lexer2::TERNARY:
                case Lexer2::TERNARY_SEPARATOR:
                case Lexer2::EQUAL:
                case Lexer2::NOT_EQUAL:
                    $currentOperator = $this->lexer->eatToken();
                    $stackOperator = $this->operatorStack->peek();

                    // If what is on top of the stack has higher (lower number) or equal precedence compared to
                    // the current operator AND it's not right associative or open bracket then push that
                    // operator and it's operands to the expression stack first.
                    while ($this->operatorStack->size() > 0 &&
                        ($stackOperator->precedence() < $currentOperator->precedence() ||
                            $stackOperator->precedence() == $currentOperator->precedence()) &&
                        !$stackOperator->isRightAssociative() &&
                        !$stackOperator->ofType(Lexer2::OPEN_BRACKET)
                    ) {
                        $this->pushBinaryOperatorOnExpressionStack();
                        $stackOperator = $this->operatorStack->peek();
                    }

                    $this->operatorStack->push($currentOperator);
                    break;

                default:
                    $unknownToken = true;
            }
        }

        while ($this->operatorStack->size() > 0) {
            $this->pushBinaryOperatorOnExpressionStack();
        }

        if (defined('DEBUG')) $this->printNode($this->expressionStack->peek());

        return $this->expressionStack->pop();
    }

    /**
     * @throws \Expression\CompilerError
     */
    private function pushBinaryOperatorOnExpressionStack()
    {
        $operator = $this->operatorStack->pop();

        // NOTE(Johan): PHP has left associative ternaries for some strange reason, so lets
        // do this complicated dance to be compatible with it.
        if ($operator->ofType(Lexer2::TERNARY_SEPARATOR)) {
            if ($this->operatorStack->size() >= 1) {
                $operator2 = $this->operatorStack->pop();
                if ($operator2->ofType(Lexer2::TERNARY)) {
                    if ($this->expressionStack->size() >= 3) {
                        $right = $this->expressionStack->pop();
                        $left = $this->expressionStack->pop();
                        $expression = $this->expressionStack->pop();
                        $ternarySeparator = new ASTBinaryOperator($operator, $left, $right);
                        $ternary = new ASTBinaryOperator($operator2, $expression, $ternarySeparator);
                        $this->expressionStack->push($ternary);
                    } else {
                        $this->error("Incorrectly defined ternary");
                    }
                } else {
                    $this->error("Expected ternary operator");
                }
            }
        } elseif ($this->expressionStack->size() >= 2) {
            $right = $this->expressionStack->pop();
            $left = $this->expressionStack->pop();
            $node = new ASTBinaryOperator($operator, $left, $right);
            $this->expressionStack->push($node);
        } else {
            $this->error("Expected two operands");
        }
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
        } elseif ($node->ofType(Lexer2::TERNARY)) {
            $leftNode = $this->solveExpression($node->leftNode);
            if ($leftNode->value > 0) {
                $result = $this->solveExpression($node->rightNode->leftNode);
            } else {
                $result = $this->solveExpression($node->rightNode->rightNode);
            }
        } elseif ($node instanceof ASTBinaryOperator) {
            $leftNode = $this->solveExpression($node->leftNode);
            $rightNode = $this->solveExpression($node->rightNode);

            $value = 0;
            switch ($node->type) {
                case Lexer2::ADDITION:
                    $value = $leftNode->value + $rightNode->value;
                    break;
                case Lexer2::SUBTRACTION:
                    $value = $leftNode->value - $rightNode->value;
                    break;
                case Lexer2::MULTIPLICATION:
                    $value = $leftNode->value * $rightNode->value;
                    break;
                case Lexer2::DIVISION:
                    $value = (int)$leftNode->value / $rightNode->value;
                    break;
                case Lexer2::MODULUS:
                    $value = $leftNode->value % $rightNode->value;
                    break;
                case Lexer2::GREATER_THAN:
                    $value = (int)($leftNode->value > $rightNode->value);
                    break;
                case Lexer2::LESS_THAN:
                    $value = (int)($leftNode->value < $rightNode->value);
                    break;
                case Lexer2::GREATER_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value >= $rightNode->value);
                    break;
                case Lexer2::LESS_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value <= $rightNode->value);
                    break;
                case Lexer2::EQUAL:
                    $value = (int)($leftNode->value == $rightNode->value);
                    break;
                case Lexer2::NOT_EQUAL:
                    $value = (int)($leftNode->value != $rightNode->value);
                    break;
                case Lexer2::LOGIC_AND:
                    $value = (int)($leftNode->value && $rightNode->value);
                    break;
                case Lexer2::LOGIC_OR:
                    $value = (int)($leftNode->value || $rightNode->value);
                    break;
                default:
                    $token = new Token2();
                    $token->type = $node->type;
                    $this->error("{$token} not allowed here");
            }

            $result = new ASTLiteral($value);

        }

        return $result;
    }

    private function printNode(ASTNode $node)
    {
        echo "<div style=\"margin-left: 50px;\">";
        if ($node->type === Lexer2::LITERAL) {
            echo $node->value;
        } else {
            $token = new Token2();
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
        throw new CompilerError("Error on column {$this->lexer->column}: $message" . substr($this->lexer->source, $this->lexer->column));
    }
}
