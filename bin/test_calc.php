<?php
declare(strict_types=1);

/**
 * Assertions for the arbitrage maths. No DB needed.
 *   php bin/test_calc.php
 */
require __DIR__ . '/../src/Arb/Calculator.php';

use Bet\Arb\Calculator;

$pass = 0;
$fail = 0;

function check(string $label, $got, $want, float $tol = 0.001): void
{
    global $pass, $fail;
    $ok = is_float($want) || is_int($want)
        ? (is_finite((float)$got) && abs((float)$got - (float)$want) <= $tol)
        : ($got === $want);
    if ($ok) {
        $pass++;
        printf("  ok   %-52s %s\n", $label, is_bool($got) ? var_export($got, true) : (string)$got);
    } else {
        $fail++;
        printf("  FAIL %-52s got=%s want=%s\n", $label, var_export($got, true), var_export($want, true));
    }
}

echo "-- margin ------------------------------------------------------------\n";
// Two-way at 2.0/2.0 is exactly break-even: 0.5 + 0.5 = 1.0
check('margin(2.0, 2.0) == 1.0', Calculator::margin([2.0, 2.0]), 1.0);
// A real arb: 2.1 and 2.1 -> 0.4762*2 = 0.9524
check('margin(2.1, 2.1)', Calculator::margin([2.1, 2.1]), 0.95238);
// Typical book overround, no arb
check('margin(1.9, 1.9) > 1', Calculator::margin([1.9, 1.9]), 1.05263);
// Junk price must not create a fake arb
check('margin with price 1.0 is INF', is_infinite(Calculator::margin([1.0, 5.0])), true);

echo "\n-- roi ---------------------------------------------------------------\n";
check('roi(0.95238) ~ 5%', Calculator::roiPct(0.95238), 5.0, 0.01);
check('roi(1.0) == 0%',    Calculator::roiPct(1.0), 0.0);
check('roi(1.05263) ~ -5%', Calculator::roiPct(1.05263), -5.0, 0.01);

echo "\n-- stake split -------------------------------------------------------\n";
// 2.1/2.1 with 1000 staked: 500 each, returns 1050 either way, profit 50.
$s = Calculator::split(['over' => 2.1, 'under' => 2.1], 1000.0);
check('equal prices -> equal stakes', $s['stakes']['over'], 500.0, 0.01);
check('payout 1050',                  $s['payout'], 1050.0, 0.01);
check('profit 50',                    $s['profit'], 50.0, 0.01);

// Asymmetric: 3.0 / 1.6 -> margin 0.9583, stakes must equalise the return.
$s2 = Calculator::split(['1' => 3.0, '2' => 1.6], 1000.0);
$ret1 = $s2['stakes']['1'] * 3.0;
$ret2 = $s2['stakes']['2'] * 1.6;
check('asymmetric returns equalise', round($ret1, 0), round($ret2, 0), 1.0);
check('asymmetric profit positive',  $s2['profit'] > 0, true);
check('stakes sum to total',         array_sum($s2['stakes']), 1000.0, 0.5);

echo "\n-- three-way 1x2 -----------------------------------------------------\n";
// Genuine 1X2 arb across books: 2.8 / 3.6 / 3.5
$p3 = ['1' => 2.8, 'X' => 3.6, '2' => 3.5];
$m3 = Calculator::margin($p3);
check('1x2 margin < 1', $m3 < 1.0, true);
$s3 = Calculator::split($p3, 1000.0);
check('1x2 all returns equal', round($s3['stakes']['1'] * 2.8), round($s3['stakes']['X'] * 3.6), 2.0);
check('1x2 profit positive',   $s3['profit'] > 0, true);

echo "\n-- skin exclusion ----------------------------------------------------\n";
// Sisal and Snai share a parent: the pair must NOT both be chosen. Best 'over'
// is Snai 2.20, but if 'under' can only come from Sisal the engine must fall
// back to a different operator for one leg.
$quotes = [
    ['selection' => 'over',  'price' => 2.20, 'slug' => 'snai',   'parent' => 'sisal', 'name' => 'Snai'],
    ['selection' => 'over',  'price' => 2.05, 'slug' => 'bet365', 'parent' => null,    'name' => 'Bet365'],
    ['selection' => 'under', 'price' => 2.10, 'slug' => 'sisal',  'parent' => null,    'name' => 'Sisal'],
];
$legs = Calculator::bestLegs($quotes, ['over', 'under']);
check('legs found', $legs !== null, true);
if ($legs) {
    $slugs = [$legs['over']['slug'], $legs['under']['slug']];
    check('did not pair snai with sisal', in_array('snai', $slugs, true) && in_array('sisal', $slugs, true), false);
    check('over fell back to bet365', $legs['over']['slug'], 'bet365');
}

// Missing outcome -> no arb
$none = Calculator::bestLegs(
    [['selection' => 'over', 'price' => 2.0, 'slug' => 'x', 'parent' => null, 'name' => 'X']],
    ['over', 'under']
);
check('incomplete market returns null', $none, null);

echo "\n-- signature ---------------------------------------------------------\n";
$sigA = Calculator::signature(7, '1x2', [
    '1' => ['slug' => 'bet365'], 'X' => ['slug' => 'sisal'], '2' => ['slug' => 'pinnacle'],
]);
$sigB = Calculator::signature(7, '1x2', [
    '2' => ['slug' => 'pinnacle'], '1' => ['slug' => 'bet365'], 'X' => ['slug' => 'sisal'],
]);
check('signature is order-independent', $sigA, $sigB);
check('market label 1x2', Calculator::marketLabel('1x2'), '1X2');
check('market label ou_2.5', Calculator::marketLabel('ou_2.5'), 'Over/Under 2.5');

printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);
