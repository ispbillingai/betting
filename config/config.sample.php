<?php
/**
 * Copy to config/config.php and fill in. config.php is git-ignored — real
 * credentials never land in the repo. Anything here can be overridden from the
 * dashboard Settings page (stored in the `settings` table).
 */
return [
    'app' => [
        'company_name' => 'Ispledger Bet',
        'base_url'     => 'https://betting.ispledger.com',
        'default_lang' => 'en',
        'timezone'     => 'Africa/Nairobi',

        // '1' lets bin/tick.php generate and move simulated prices so the
        // dashboard is demonstrable before a real odds feed exists.
        // Set to '0' the moment a live feed is connected, so simulated prices
        // can never be mistaken for real ones.
        'demo_mode'    => '0',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'betting',
        'user'    => 'betting',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'dashboard' => [
        // Master fallback password so an operator can never be locked out.
        // Leave empty to disable the fallback entirely.
        'password' => '',
    ],
];
