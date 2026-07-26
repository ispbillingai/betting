<?php
declare(strict_types=1);

namespace Bet\Arb;

use Bet\Db;

/**
 * Positions taken from detected surebets. A bet stores its legs with the price
 * and stake actually committed, so settlement maths never depends on live odds
 * that have since moved.
 */
final class Bets
{
    /**
     * Record a position from a surebet, scaled to the given bankroll.
     * Returns the new bet id.
     */
    public static function take(int $surebetId, float $bankroll, ?int $userId = null): int
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM surebets WHERE id = ?');
        $stmt->execute([$surebetId]);
        $sb = $stmt->fetch();
        if (!$sb) {
            throw new \RuntimeException('surebet not found');
        }

        $legs = json_decode((string)$sb['legs'], true) ?: [];
        if (!$legs) {
            throw new \RuntimeException('surebet has no legs');
        }

        $prices = [];
        foreach ($legs as $l) {
            $prices[(string)$l['selection']] = (float)$l['price'];
        }
        $split = Calculator::split($prices, $bankroll);

        // Freeze price + stake per leg at the moment of placing.
        $frozen = [];
        foreach ($legs as $l) {
            $sel = (string)$l['selection'];
            $frozen[] = [
                'selection' => $sel,
                'bookmaker' => $l['bookmaker'] ?? '',
                'slug'      => $l['slug'] ?? '',
                'price'     => (float)$l['price'],
                'stake'     => $split['stakes'][$sel] ?? 0.0,
            ];
        }

        Db::pdo()->prepare(
            'INSERT INTO bets (surebet_id, event_id, user_id, market, total_stake, expected_roi, legs, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, "open")'
        )->execute([
            $surebetId,
            (int)$sb['event_id'],
            $userId,
            (string)$sb['market'],
            round(array_sum($split['stakes']), 2),
            round((float)$sb['roi_pct'], 3),
            json_encode($frozen, JSON_UNESCAPED_UNICODE),
        ]);

        return (int)Db::pdo()->lastInsertId();
    }

    /**
     * Settle a position. $result is the winning selection, or 'void' to return
     * stakes. Profit = winning leg's return minus the total staked; for an
     * arbitrage that is positive whichever leg lands.
     */
    public static function settle(int $betId, string $result): void
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM bets WHERE id = ?');
        $stmt->execute([$betId]);
        $bet = $stmt->fetch();
        if (!$bet || $bet['status'] !== 'open') {
            return;
        }

        $legs  = json_decode((string)$bet['legs'], true) ?: [];
        $total = (float)$bet['total_stake'];

        if ($result === 'void') {
            Db::pdo()->prepare(
                'UPDATE bets SET status = "void", profit = 0, settled_at = NOW() WHERE id = ?'
            )->execute([$betId]);
            return;
        }

        $return = 0.0;
        $found  = false;
        foreach ($legs as $l) {
            if ((string)$l['selection'] === $result) {
                $return = (float)$l['stake'] * (float)$l['price'];
                $found  = true;
                break;
            }
        }
        if (!$found) {
            throw new \RuntimeException('unknown selection: ' . $result);
        }

        $profit = round($return - $total, 2);

        Db::pdo()->prepare(
            'UPDATE bets SET status = ?, profit = ?, settled_at = NOW() WHERE id = ?'
        )->execute([$profit >= 0 ? 'won' : 'lost', $profit, $betId]);
    }

    /** Headline numbers for the overview tiles. */
    public static function stats(): array
    {
        $row = Db::pdo()->query(
            "SELECT COUNT(*) AS n,
                    COALESCE(SUM(total_stake), 0) AS staked,
                    COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) AS open_n,
                    COALESCE(SUM(profit), 0) AS profit
               FROM bets"
        )->fetch() ?: [];

        $staked = (float)($row['staked'] ?? 0);
        return [
            'n'      => (int)($row['n'] ?? 0),
            'open_n' => (int)($row['open_n'] ?? 0),
            'staked' => $staked,
            'profit' => (float)($row['profit'] ?? 0),
            'roi'    => $staked > 0 ? ((float)$row['profit'] / $staked) * 100 : 0.0,
        ];
    }
}
