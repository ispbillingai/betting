<?php
declare(strict_types=1);

namespace Bet\Arb;

use Bet\Db;
use PDO;

/**
 * Walks stored odds and records arbitrage opportunities.
 *
 * Source-agnostic on purpose: it reads whatever is in `odds`, so the same
 * scanner serves an API feed, a scraper or seeded test data. Run it from cron
 * (bin/scan.php) as often as the feed updates.
 */
final class Scanner
{
    /** Minimum ROI worth recording, in percent. Below this the price moves first. */
    public const MIN_ROI = 0.5;

    /**
     * Scan every event with live prices.
     *
     * @return array{scanned:int,found:int,closed:int}
     */
    public static function run(float $minRoi = self::MIN_ROI): array
    {
        $pdo = Db::pdo();

        // Only fixtures that have not started; a kicked-off match has stale prices.
        $events = $pdo->query(
            "SELECT id FROM events WHERE status = 'prematch' AND starts_at > NOW()"
        )->fetchAll(PDO::FETCH_COLUMN);

        $seen  = [];
        $found = 0;

        foreach ($events as $eventId) {
            foreach (array_keys(Calculator::MARKETS) as $market) {
                $sig = self::scanMarket((int)$eventId, $market, $minRoi);
                if ($sig !== null) {
                    $seen[] = $sig;
                    $found++;
                }
            }
        }

        // Anything previously active but not seen this pass has gone — the price
        // moved. Mark inactive rather than delete, so history survives.
        $closed = self::deactivateMissing($seen);

        return ['scanned' => count($events), 'found' => $found, 'closed' => $closed];
    }

    /**
     * Check one event+market. Returns the signature if an arbitrage was stored.
     */
    private static function scanMarket(int $eventId, string $market, float $minRoi): ?string
    {
        $outcomes = Calculator::MARKETS[$market] ?? null;
        if ($outcomes === null) {
            return null;
        }

        $stmt = Db::pdo()->prepare(
            'SELECT o.selection, o.price, b.slug, b.parent, b.name
               FROM odds o
               JOIN bookmakers b ON b.id = o.bookmaker_id
              WHERE o.event_id = ? AND o.market = ? AND b.active = 1'
        );
        $stmt->execute([$eventId, $market]);
        $quotes = $stmt->fetchAll();

        if (count($quotes) < count($outcomes)) {
            return null; // cannot cover every outcome
        }

        // Normalise types — PDO hands back decimals as strings.
        foreach ($quotes as &$q) {
            $q['price'] = (float)$q['price'];
        }
        unset($q);

        $legs = Calculator::bestLegs($quotes, $outcomes);
        if ($legs === null) {
            return null;
        }

        $prices = [];
        foreach ($legs as $sel => $leg) {
            $prices[$sel] = $leg['price'];
        }

        $margin = Calculator::margin($prices);
        if (!is_finite($margin) || $margin >= 1.0) {
            return null; // no arbitrage
        }

        $roi = Calculator::roiPct($margin);
        if ($roi < $minRoi) {
            return null;
        }

        // Stake shares for a notional 100 unit bankroll — the UI rescales.
        $split = Calculator::split($prices, 100.0);

        $payload = [];
        foreach ($legs as $sel => $leg) {
            $payload[] = [
                'selection'   => $sel,
                'bookmaker'   => $leg['name'],
                'slug'        => $leg['slug'],
                'price'       => $leg['price'],
                'stake_pct'   => $split['stakes'][$sel] ?? 0,
            ];
        }

        $sig = Calculator::signature($eventId, $market, $legs);

        Db::pdo()->prepare(
            'INSERT INTO surebets (event_id, market, kind, margin, roi_pct, legs, signature, active)
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                  margin = VALUES(margin), roi_pct = VALUES(roi_pct),
                  legs = VALUES(legs), active = 1, last_seen = NOW()'
        )->execute([
            $eventId, $market, 'surebet',
            round($margin, 5), round($roi, 3),
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $sig,
        ]);

        return $sig;
    }

    /**
     * Deactivate active surebets whose signature did not appear in this pass.
     *
     * @param array<int,string> $seen
     */
    private static function deactivateMissing(array $seen): int
    {
        $pdo = Db::pdo();
        if (!$seen) {
            return (int)$pdo->exec('UPDATE surebets SET active = 0 WHERE active = 1');
        }
        $ph   = implode(',', array_fill(0, count($seen), '?'));
        $stmt = $pdo->prepare("UPDATE surebets SET active = 0 WHERE active = 1 AND signature NOT IN ($ph)");
        $stmt->execute($seen);
        return $stmt->rowCount();
    }

    /** Active opportunities, best ROI first, for the dashboard. */
    public static function active(int $limit = 100, float $minRoi = 0.0): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT s.*, e.home, e.away, e.league, e.starts_at
               FROM surebets s
               JOIN events e ON e.id = s.event_id
              WHERE s.active = 1 AND s.roi_pct >= ?
              ORDER BY s.roi_pct DESC
              LIMIT ' . (int)$limit
        );
        $stmt->execute([$minRoi]);
        return $stmt->fetchAll() ?: [];
    }
}
