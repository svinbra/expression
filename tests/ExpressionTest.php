<?php declare(strict_types=1);

namespace Test\Service\Expression;

use Expression\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testBasicArithmetic() {
        $n = 5;
        $poeExpression = new Expression('$x=$n + 1;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n + 1, $variables['$x']);

        $n = 5;
        $poeExpression = new Expression('$x=$n - 1;');
        $variables = $poeExpression->eval(5);
        $this->assertEquals($n - 1, $variables['$x']);

        $n = 6;
        $poeExpression = new Expression('$x=$n / 2;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n / 2, $variables['$x']);

        $n = 6;
        $poeExpression = new Expression('$x=$n * 2;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n * 2, $variables['$x']);

        $n = 16;
        $poeExpression = new Expression('$x=$n % 10;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n % 10, $variables['$x']);

        $poeExpression = new Expression('$x=1 - 2 - 3;');
        $variables = $poeExpression->eval(0);
        $this->assertEquals(1 -2 - 3, $variables['$x']);

        $poeExpression = new Expression('$x=(3 + 3) / 2;');
        $variables = $poeExpression->eval(0);
        $this->assertEquals((3 + 3) / 2 , $variables['$x']);
    }

    public function testPHPsCrappyLeftAssociativeTernary()
    {
        // NOTE(Johan): yeah the value is actually not 0, it's 33.
        $poeExpression = new Expression('$x=1 ? 0: 1 ? 22 : 33;');
        $variables = $poeExpression->eval(0);
        $this->assertEquals(1 ? 0 : 1 ? 22 : 33, $variables['$x']);
    }

    public function testSimpleTernary()
    {
        $n = 16;
        $poeExpression = new Expression('$x=$n > 6 ? 1 : 2;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n > 6 ? 1 : 2, $variables['$x']);

        $poeExpression = new Expression('$x=$n < 6 ? 1 : 2;');
        $variables = $poeExpression->eval($n);
        $this->assertEquals($n < 6 ? 1 : 2, $variables['$x']);
    }

    public function testBrackets()
    {
        $poeExpression = new Expression('$x=2 * (2 + 3);');
        $variables = $poeExpression->eval(0);
        $this->assertEquals(2 * (2 + 3), $variables['$x']);

        $poeExpression = new Expression('$x=((2 + 3) * (2 + 3));');
        $variables = $poeExpression->eval(0);
        $this->assertEquals(((2 + 3) * (2 + 3)), $variables['$x']);
    }

    public function testDoubleTernary()
    {
        $n = 2;
        $poeExpression = new Expression('$x=($n==1 ? 10 : $n > 2 ? 12 : 13);');
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 10 : $n > 2 ? 12 : 13), $variables['$x']);
    }

    public function testMoreComplicatedTernaries()
    {
        $poeExpression = new Expression('$x=($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2);'); $n = 0;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 1  ;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 2;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 3;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 4;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 5;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 6;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 7;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
        $n = 22;
        $variables = $poeExpression->eval($n);
        $this->assertEquals(($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<12 || $n%100>14) ? 1 : 2), $variables['$x']);
    }

    public function testSomething()
    {
        $poeExpression = new Expression('$x = 2 == 2;');
        $variables = $poeExpression->eval(0);
        $this->assertEquals(1, $variables['$x']);
    }
}
