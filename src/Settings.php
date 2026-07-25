<?php
declare(strict_types=1);

namespace Bet;

use Throwable;

/**
 * Dashboard-editable settings, stored flat as dot-path keys in the `settings`
 * table and overlaid onto file config at boot. Lets an operator change things
 * from the Settings page without touching config/config.php.
 */
final class Settings
{
    /** @return array<string,string> all stored key => value pairs */
    public static function all(): array
    {
        try {
            $rows = Db::pdo()->query('SELECT `key`, `value` FROM settings')->fetchAll();
        } catch (Throwable) {
            return []; // table not migrated yet
        }
        $out = [];
        foreach ($rows ?: [] as $r) {
            $out[(string)$r['key']] = (string)$r['value'];
        }
        return $out;
    }

    /** Stored settings expanded from dot-paths into a nested array for Config::applyOverlay(). */
    public static function nested(): array
    {
        $nested = [];
        foreach (self::all() as $key => $value) {
            $node = &$nested;
            foreach (explode('.', $key) as $part) {
                if (!isset($node[$part]) || !is_array($node[$part])) {
                    $node[$part] = [];
                }
                $node = &$node[$part];
            }
            $node = $value;
            unset($node);
        }
        return $nested;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Db::pdo()->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string)$v;
    }

    public static function set(string $key, string $value): void
    {
        Db::pdo()->prepare(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        )->execute([$key, $value]);
    }

    /**
     * Save many key => value pairs at once. Returns how many were written.
     * Keys not in $allowed are ignored so a crafted POST can't set arbitrary config.
     *
     * @param array<string,string> $pairs
     * @param array<int,string>    $allowed
     */
    public static function setMany(array $pairs, array $allowed): int
    {
        $n = 0;
        foreach ($pairs as $k => $v) {
            if (in_array($k, $allowed, true)) {
                self::set($k, (string)$v);
                $n++;
            }
        }
        return $n;
    }
}
