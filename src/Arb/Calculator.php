<?php
declare(strict_types=1);

namespace Bet\Arb;

/**
 * Arbitrage maths. Pure functions, no DB — so it is unit-testable and the
 * scanner, the UI and any future bet-slip prefill all share one implementation.
 *
 * An arbitrage exists when the implied probabilities of a set of mutually
 * exclusive, collectively exhaustive outcomes sum to less than 1:
 *
 *     margin = sum(1 / price_i)      margin < 1  =>  guaranteed profit
 *     roi    = (1 / margin - 1)
 *
 * Stake split that equalises the return whichever outcome lands:
 *
 *     stake_i = total * (1 / price_i) / margin
 *     payout  = total / margin        (identical for every outcome)
 */
final class Calculator
{
    /** Outcome sets that fully cover a market. */
    public const MARKETS = [
        '1x2'    => ['1', 'X', '2'],
        'ou_1.5' => ['over', 'under'],
        'ou_2.5' => ['over', 'under'],
        'ou_3.5' => ['over', 'under'],
    ];

    /** Human label for a market key. */
    public static function marketLabel(string $market): string
    {
        if ($market === '1x2') {
            return '1X2';
        }
        if (str_starts_with($market, 'ou_')) {
            return 'Over/Under ' . substr($market, 3);
        }
        return $market;
    }

    /**
     * Sum of implied probabilities. Below 1.0 means arbitrage.
     *
     * @param array<int|string,float> $prices decimal odds, one per outcome
     */
    public static function margin(array $prices): float
    {
        $m = 0.0;
        foreach ($prices as $p) {
            if ($p <= 1.0) {
                // A price of 1.0 or less pays nothing; treat the set as unplayable
                // rather than letting it divide into a fake arbitrage.
                return INF;
            }
            $m += 1.0 / $p;
        }
        return $m;
    }

    /** Return on investment as a percentage. Positive means profitable. */
    public static function roiPct(float $margin): float
    {
        if ($margin <= 0 || !is_finite($margin)) {
            return -100.0;
        }
        return (1.0 / $margin - 1.0) * 100.0;
    }

    /**
     * Split a bankroll across outcomes so every result returns the same amount.
     *
     * @param  array<string,float> $prices selection => decimal odds
     * @return array{margin:float,roi_pct:float,payout:float,profit:float,stakes:array<string,float>}
     */
    public static function split(array $prices, float $total): array
    {
        $margin = self::margin($prices);
        if (!is_finite($margin) || $margin <= 0) {
            return ['margin' => INF, 'roi_pct' => -100.0, 'payout' => 0.0, 'profit' => 0.0, 'stakes' => []];
        }

        $stakes = [];
        foreach ($prices as $sel => $p) {
            $stakes[$sel] = round($total * (1.0 / $p) / $margin, 2);
        }

        $payout = $total / $margin;

        return [
            'margin'  => $margin,
            'roi_pct' => self::roiPct($margin),
            'payout'  => round($payout, 2),
            'profit'  => round($payout - $total, 2),
            'stakes'  => $stakes,
        ];
    }

    /**
     * Best price per outcome across books, skipping books that share a parent
     * with an already-chosen leg — two skins of one operator are the same book,
     * so pairing them is not a real arbitrage and the stake would sit on one
     * account.
     *
     * @param  array<int,array{selection:string,price:float,slug:string,parent:?string,name:string}> $quotes
     * @param  array<int,string> $outcomes the selections that must all be covered
     * @return array<string,array>|null    selection => winning quote, or null if incomplete
     */
    public static function bestLegs(array $quotes, array $outcomes): ?array
    {
        // Highest price first, so the first acceptable quote per outcome wins.
        usort($quotes, fn($a, $b) => $b['price'] <=> $a['price']);

        $legs   = [];
        // Greedily taking the top price per outcome can strand a later outcome:
        // if Snai (parent sisal) wins 'over', reserving the sisal group would
        // block Sisal from 'under' even when another book could cover 'over'.
        // So recurse and backtrack, keeping the highest total value.
        $best = self::search($quotes, $outcomes, 0, [], []);
        return $best === [] ? null : $best;
    }

    /**
     * Depth-first search over outcome assignments, one operator group per leg.
     * Quotes are pre-sorted by price, so the first complete solution found is
     * the best-priced one and we can return immediately.
     *
     * @param  array<int,array>   $quotes   sorted, highest price first
     * @param  array<int,string>  $outcomes selections still to cover
     * @param  array<string,array> $legs    chosen so far
     * @param  array<string,bool>  $groups  operator groups already used
     * @return array<string,array>          complete assignment, or [] if none
     */
    private static function search(array $quotes, array $outcomes, int $i, array $legs, array $groups): array
    {
        if ($i >= count($outcomes)) {
            return $legs; // every outcome covered
        }

        $sel = $outcomes[$i];
        foreach ($quotes as $q) {
            if ($q['selection'] !== $sel) {
                continue;
            }
            $group = $q['parent'] ?? $q['slug'];
            if (isset($groups[$group])) {
                continue; // that operator already covers another leg
            }
            $tryLegs         = $legs;
            $tryLegs[$sel]   = $q;
            $tryGroups       = $groups;
            $tryGroups[$group] = true;

            $result = self::search($quotes, $outcomes, $i + 1, $tryLegs, $tryGroups);
            if ($result !== []) {
                return $result;
            }
            // else: this choice stranded a later outcome — try the next price.
        }

        return []; // no usable price for this outcome
    }

    /**
     * Stable dedupe handle for an opportunity. Same event + market + books +
     * selections yields the same signature, so a surebet that persists across
     * scans updates one row instead of piling up duplicates.
     *
     * @param array<string,array> $legs selection => quote
     */
    public static function signature(int $eventId, string $market, array $legs): string
    {
        $parts = [];
        foreach ($legs as $sel => $leg) {
            $parts[] = $sel . ':' . $leg['slug'];
        }
        sort($parts);
        return $eventId . '|' . $market . '|' . implode(',', $parts);
    }
}
