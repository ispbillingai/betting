<?php
declare(strict_types=1);

/**
 * Seed demo events and odds so the scanner and dashboard can be tested before a
 * real feed exists. Prices are generated around a fair line with per-book
 * variation, which produces a realistic mix — most events show no arbitrage,
 * a few do.
 *
 *   php bin/seed.php [events]
 *
 * Safe to re-run: events are keyed by match_key and odds by quote, so it
 * refreshes prices instead of duplicating.
 */
require __DIR__ . '/../src/Bootstrap.php';

use Bet\Bootstrap;
use Bet\Db;

Bootstrap::init();

$count = max(1, (int)($argv[1] ?? 12));
$pdo   = Db::pdo();

$teams = [
    ['Inter', 'Milan'], ['Juventus', 'Napoli'], ['Roma', 'Lazio'],
    ['Atalanta', 'Fiorentina'], ['Bologna', 'Torino'], ['Udinese', 'Genoa'],
    ['Monza', 'Lecce'], ['Cagliari', 'Verona'], ['Empoli', 'Sassuolo'],
    ['Parma', 'Como'], ['Venezia', 'Pisa'], ['Cremonese', 'Spezia'],
];

// Books that actually quote in this demo. Mixed operator groups on purpose so
// the skin-exclusion logic in Calculator::bestLegs has something to reject.
$books = $pdo->query(
    "SELECT id, slug, parent FROM bookmakers
      WHERE slug IN ('bet365','eurobet','sisal','snai','lottomatica','goldbet',
                     'pinnacle','williamhill','betsson','marathonbet','unibet','888')"
)->fetchAll();

if (!$books) {
    fwrite(STDERR, "No bookmakers found — run db/migrations/002_arbitrage.sql first.\n");
    exit(1);
}

$evStmt = $pdo->prepare(
    'INSERT INTO events (match_key, sport, league, home, away, starts_at, status)
          VALUES (?, "football", ?, ?, ?, ?, "prematch")
     ON DUPLICATE KEY UPDATE starts_at = VALUES(starts_at), status = "prematch"'
);

$odStmt = $pdo->prepare(
    'INSERT INTO odds (event_id, bookmaker_id, market, selection, price)
          VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE price = VALUES(price)'
);

/** Decimal odds from a probability, with a bookmaker margin applied. */
function priceFrom(float $prob, float $overround): float
{
    $p = $prob * $overround;
    return round(max(1.01, 1.0 / $p), 2);
}

$madeEvents = 0;
$madeOdds   = 0;

for ($i = 0; $i < $count; $i++) {
    [$home, $away] = $teams[$i % count($teams)];
    $key    = strtolower($home . '-' . $away . '-' . date('Y-m-d', strtotime("+" . (1 + intdiv($i, 4)) . " day")));
    $starts = date('Y-m-d H:i:s', strtotime('+' . (1 + intdiv($i, 4)) . ' day ' . (12 + ($i % 8)) . ' hour'));

    $evStmt->execute([$key, 'Serie A', $home, $away, $starts]);
    $eventId = (int)$pdo->lastInsertId();
    if ($eventId === 0) {
        $q = $pdo->prepare('SELECT id FROM events WHERE match_key = ?');
        $q->execute([$key]);
        $eventId = (int)$q->fetchColumn();
    }
    $madeEvents++;

    // Fair probabilities for this fixture.
    $pHome = 0.35 + (mt_rand(0, 25) / 100);
    $pDraw = 0.22 + (mt_rand(0, 8) / 100);
    $pAway = max(0.10, 1.0 - $pHome - $pDraw);
    $sum   = $pHome + $pDraw + $pAway;
    $pHome /= $sum; $pDraw /= $sum; $pAway /= $sum;

    $pOver = 0.45 + (mt_rand(0, 20) / 100);

    foreach ($books as $b) {
        // Overround > 1 is the book's cut. Every few events one book is made
        // generous (below 1) on a single outcome, which is what creates a
        // cross-book arbitrage — exactly the real-world cause.
        $base = 1.02 + (mt_rand(0, 6) / 100);

        $sharp = ($i % 3 === 0) && ($b['slug'] === 'pinnacle');
        $soft  = ($i % 4 === 0) && in_array($b['slug'], ['betsson', 'marathonbet'], true);

        $ohome = $sharp ? 0.985 : ($soft ? 0.99 : $base);
        $odraw = $base + 0.01;
        $oaway = $soft ? 0.98 : $base;

        $odStmt->execute([$eventId, $b['id'], '1x2', '1', priceFrom($pHome, $ohome)]);
        $odStmt->execute([$eventId, $b['id'], '1x2', 'X', priceFrom($pDraw, $odraw)]);
        $odStmt->execute([$eventId, $b['id'], '1x2', '2', priceFrom($pAway, $oaway)]);
        $madeOdds += 3;

        foreach (['ou_1.5' => 0.72, 'ou_2.5' => $pOver, 'ou_3.5' => 0.30] as $mkt => $po) {
            $oo = $soft ? 0.985 : $base;
            $ou = $sharp ? 0.985 : $base + 0.005;
            $odStmt->execute([$eventId, $b['id'], $mkt, 'over',  priceFrom($po, $oo)]);
            $odStmt->execute([$eventId, $b['id'], $mkt, 'under', priceFrom(1 - $po, $ou)]);
            $madeOdds += 2;
        }
    }
}

echo "Seeded {$madeEvents} events, {$madeOdds} quotes across " . count($books) . " bookmakers.\n";
