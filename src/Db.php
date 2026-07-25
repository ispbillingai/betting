<?php
declare(strict_types=1);

namespace Bet;

use PDO;

/** Shared PDO, connection settings read from Config. Endpoints just call Db::pdo(). */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $cfg = Config::section('db');
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $cfg['host'] ?? '127.0.0.1',
                $cfg['name'] ?? 'betting',
                $cfg['charset'] ?? 'utf8mb4'
            );
            self::$pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}
