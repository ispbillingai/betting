<?php
declare(strict_types=1);

/**
 * DEMO ONLY. Moves seeded prices the way a real market does, so surebets appear
 * and disappear between scans. Without this the dashboard shows the same frozen
 * opportunities forever, which demos badly and proves nothing.
 *
 *   php bin/tick.php
 *
 * Hard-gated on app.demo_mode. When a real odds feed is connected, set
 * demo_mode to 0 (Settings page, or config/config.php) and this exits without
 * touching a single price. That gate exists so simulated prices can never be
 * mistaken for live ones.
 */
require __DIR__ . '/../src/Bootstrap.php';

use Bet\Arb\Scanner;
use Bet\Bootstrap;
use Bet\Config;
use Bet\Db;

Bootstrap::init();

if ((string)Config::get('app.demo_mode', '0') !== '1') {
    // Silent by design: this runs from cron every couple of minutes and a noisy
    // "disabled" line every time would bury the real scanner output.
    exit(0);
}

$pdo = Db::pdo();

// Keep a healthy pool of future fixtures. Seeded match keys carry a date, so a
// top-up creates tomorrow's games as today's age out.
$future = (int)$pdo->query(
    "SELECT COUNT(*) FROM events WHERE status = 'prematch' AND starts_at > NOW()"
)->fetchColumn();

if ($future < 8) {
    passthru(escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/seed.php') . ' 12');
}

// Retire fixtures that have kicked off; their prices are stale and the scanner
// should stop offering them.
$done = $pdo->exec(
    "UPDATE events SET status = 'finished' WHERE status = 'prematch' AND starts_at <= NOW()"
);

// Drift every price a little. Real books move by small increments, and a book
// that shifts a fraction against the others is exactly what opens and closes an
// arbitrage, so this is the honest way to animate the demo.
$moved = $pdo->exec(
    "UPDATE odds o
       JOIN events e ON e.id = o.event_id
        SET o.price = GREATEST(1.02, ROUND(o.price * (1 + ((RAND() - 0.5) * 0.03)), 2))
      WHERE e.status = 'prematch' AND e.starts_at > NOW()"
);

$r = Scanner::run();

printf(
    "[%s] DEMO tick: moved=%d retired=%d | scanned=%d found=%d closed=%d\n",
    date('Y-m-d H:i:s'), (int)$moved, (int)$done, $r['scanned'], $r['found'], $r['closed']
);
