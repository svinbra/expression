<?php declare(strict_types=1);

use Expression\Expression;

require_once __DIR__ . "/../vendor/autoload.php";

$start = microtime(true);
for($i=0; $i < 1000; $i++) {
    $exp = new Expression("plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);");
}
$end = microtime(true) - $start;
echo "Parsing expression: " . $end . "\n";

$start = microtime(true);
for($i=0; $i < 1000; $i++) {
    $exp->eval(22);
}

echo "Evaluating expression: " . (microtime(true) - $start) . "\n";

