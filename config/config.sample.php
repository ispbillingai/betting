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
