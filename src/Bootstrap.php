<?php
declare(strict_types=1);

namespace Bet;

/**
 * One-line include for every entrypoint. Registers the autoloader (works with
 * or without `composer install`), loads config, sets the timezone, and exposes
 * a shared PDO + Config. Keeps endpoints thin — they just `require Bootstrap`.
 */
final class Bootstrap
{
    private static bool $done = false;

    public static function init(): void
    {
        if (self::$done) {
            return;
        }

        // Prefer composer's autoloader; fall back to a minimal PSR-4 loader so the
        // app runs on a plain server where `composer install` hasn't been run.
        $vendor = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($vendor)) {
            require_once $vendor;
        } else {
            spl_autoload_register(static function (string $class): void {
                if (!str_starts_with($class, 'Bet\\')) {
                    return;
                }
                $rel  = str_replace('\\', '/', substr($class, strlen('Bet\\')));
                $file = __DIR__ . '/' . $rel . '.php';
                if (is_file($file)) {
                    require $file;
                }
            });
        }

        Config::load(dirname(__DIR__) . '/config/config.php');

        // Overlay dashboard-editable settings from the DB (if the table exists).
        // Wrapped so a missing DB/table never blocks boot — file config stands alone.
        try {
            Config::applyOverlay(Settings::nested());
        } catch (\Throwable) {
            // ignore — settings are optional
        }

        date_default_timezone_set(Config::get('app.timezone', 'UTC'));

        self::$done = true;
    }
}
