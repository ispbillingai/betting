<?php
declare(strict_types=1);

/**
 * Run the arbitrage scanner over stored odds.
 *
 *   php bin/scan.php [minRoi]
 *
 * From cron, as often as the odds feed refreshes:
 *   * * * * * php /var/www/html/betting/bin/scan.php >> /var/log/betting-scan.log 2>&1
 */
require __DIR__ . '/../src/Bootstrap.php';

use Bet\Arb\Scanner;
use Bet\Bootstrap;

Bootstrap::init();

$minRoi = isset($argv[1]) ? (float)$argv[1] : Scanner::MIN_ROI;

$t0 = microtime(true);
$r  = Scanner::run($minRoi);
$ms = round((microtime(true) - $t0) * 1000);

printf(
    "[%s] scanned=%d found=%d closed=%d minRoi=%.2f%% in %dms\n",
    date('Y-m-d H:i:s'), $r['scanned'], $r['found'], $r['closed'], $minRoi, $ms
);
