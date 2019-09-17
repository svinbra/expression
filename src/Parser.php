<?php declare(strict_types=1);

namespace Expression;

use Expression\AST\ASTDeclaration;
use Expression\AST\ASTIdentifier;
use Expression\AST\ASTLiteral;
use Expression\AST\ASTNode;
use Expression\AST\ASTBinaryOperator;

class Parser
{
    /** @var \Expression\Lexer */
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
     * Parser constructor.
     *
     * @param string $expression
     *
     * @throws \Expression\CompilerException
     */
    public function __construct(string $expression)
    {
        $this->lexer = new Lexer($expression);

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
     * @throws \Expression\CompilerException
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
     * @throws \Expression\CompilerException
     */
    private function compile(): array
    {
        // n needs to be defined, but since we only parse the expression here we don't care about the value
        // itself, the correct value is set when the user runs the eval function.
        $this->variables['n'] = 0;
        $root = [];

        while ($this->lexer->haveTokens()) {
            if ($this->lexer->expect(TokenType::VARIABLE)) {
                $identifier = $this->lexer->eatToken();
                if ($this->lexer->expect(TokenType::ASSIGNMENT)) {
                    $this->lexer->eatToken();
                    $rightNode = $this->parseExpression();
                    if ($this->lexer->expect(TokenType::END_OF_STATEMENT)) {
                        $this->lexer->eatToken();
                    } else {
                        $this->error("End of statement missing");
                    }
                    $leftNode = new ASTIdentifier($identifier->value);

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
     * @throws \Expression\CompilerException
     */
    private function parseExpression(): ASTNode
    {
        $unknownToken = false;

        while ($this->lexer->haveTokens() && !$unknownToken) {
            $currentToken = $this->lexer->peekToken();

            switch ($currentToken->type()) {
                case TokenType::ASSIGNMENT:
                    $this->error("Assignment not allowed here");
                    break;

                case TokenType::NUMBER:
                    $node = new ASTLiteral($this->lexer->eatToken()->value);
                    $this->expressionStack->push($node);
                    break;

                case TokenType::VARIABLE:
                    $token = $this->lexer->eatToken();
                    $node = new ASTIdentifier($token->value);
                    $this->expressionStack->push($node);
                    break;

                case TokenType::CLOSE_BRACKET:
                    // A closing bracket triggers a chain of pushes to the expression stack
                    // because whatever is inside the bracket overrides the precedence order of
                    // what surrounds it.
                    $this->lexer->eatToken();
                    $operator = null;
                    $pushedToStack = false;
                    while (
                        $this->operatorStack->size() > 0 &&
                        !($operator = $this->operatorStack->peek())->ofType(TokenType::OPEN_BRACKET)
                    ) {
                        $this->pushBinaryOperatorOnExpressionStack();
                        $pushedToStack = true;
                    }
                    if (!$operator || !$operator->ofType(TokenType::OPEN_BRACKET)) {
                        $this->error("Expected open bracket before closing bracket");
                    }
                    if (!$pushedToStack) {
                        $this->error("Empty bracket not allowed");
                    }
                    $this->operatorStack->pop(); // OPEN_PARENTHESES
                    break;

                case TokenType::OPEN_BRACKET:
                case TokenType::MULTIPLICATION:
                case TokenType::DIVISION:
                case TokenType::MODULUS:
                case TokenType::ADDITION:
                case TokenType::SUBTRACTION:
                case TokenType::GREATER_THAN:
                case TokenType::LESS_THAN:
                case TokenType::GREATER_THAN_OR_EQUAL:
                case TokenType::LESS_THAN_OR_EQUAL:
                case TokenType::LOGIC_AND:
                case TokenType::LOGIC_OR:
                case TokenType::TERNARY:
                case TokenType::TERNARY_SEPARATOR:
                case TokenType::EQUAL:
                case TokenType::NOT_EQUAL:
                    $currentOperator = $this->lexer->eatToken();
                    $stackOperator = $this->operatorStack->peek();

                    // If what is on top of the stack has higher (lower number) or equal precedence compared to
                    // the current operator AND it's not right associative or open bracket then push that
                    // operator and it's operands to the expression stack first.
                    while ($this->operatorStack->size() > 0 &&
                        ($stackOperator->precedence() < $currentOperator->precedence() ||
                            $stackOperator->precedence() == $currentOperator->precedence()) &&
                        !$stackOperator->isRightAssociative() &&
                        !$stackOperator->ofType(TokenType::OPEN_BRACKET)
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
     * @throws \Expression\CompilerException
     */
    private function pushBinaryOperatorOnExpressionStack()
    {
        $operator = $this->operatorStack->pop();

        // NOTE(Johan): PHP has left associative ternaries for some strange reason, so lets
        // do this complicated dance to be compatible with it.
        if ($operator->ofType(TokenType::TERNARY_SEPARATOR)) {
            if ($this->operatorStack->size() >= 1) {
                $operator2 = $this->operatorStack->pop();
                if ($operator2->ofType(TokenType::TERNARY)) {
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
     * @throws \Expression\CompilerException
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
        } elseif ($node->ofType(TokenType::TERNARY)) {
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
                case TokenType::ADDITION:
                    $value = $leftNode->value + $rightNode->value;
                    break;
                case TokenType::SUBTRACTION:
                    $value = $leftNode->value - $rightNode->value;
                    break;
                case TokenType::MULTIPLICATION:
                    $value = $leftNode->value * $rightNode->value;
                    break;
                case TokenType::DIVISION:
                    $value = (int)$leftNode->value / $rightNode->value;
                    break;
                case TokenType::MODULUS:
                    $value = $leftNode->value % $rightNode->value;
                    break;
                case TokenType::GREATER_THAN:
                    $value = (int)($leftNode->value > $rightNode->value);
                    break;
                case TokenType::LESS_THAN:
                    $value = (int)($leftNode->value < $rightNode->value);
                    break;
                case TokenType::GREATER_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value >= $rightNode->value);
                    break;
                case TokenType::LESS_THAN_OR_EQUAL:
                    $value = (int)($leftNode->value <= $rightNode->value);
                    break;
                case TokenType::EQUAL:
                    $value = (int)($leftNode->value == $rightNode->value);
                    break;
                case TokenType::NOT_EQUAL:
                    $value = (int)($leftNode->value != $rightNode->value);
                    break;
                case TokenType::LOGIC_AND:
                    $value = (int)($leftNode->value && $rightNode->value);
                    break;
                case TokenType::LOGIC_OR:
                    $value = (int)($leftNode->value || $rightNode->value);
                    break;
                default:
                    $this->error("{$node->value} not allowed here");
            }

            $result = new ASTLiteral($value);

        }

        return $result;
    }

    private function printNode(ASTNode $node)
    {
        echo "<div style=\"margin-left: 50px;\">";
        if ($node->type === TokenType::NUMBER) {
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
     * @throws \Expression\CompilerException
     */
    private function error(string $message)
    {
        //throw new CompilerException(CompilerException::ERROR, "Error on column {$this->lexer->column}: $message" . substr($this->lexer->source, $this->lexer->column));
    }
}
