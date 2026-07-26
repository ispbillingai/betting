<?php
/**
 * Stringhe UI italiane per il pannello. Le chiavi rispecchiano ui.en.php.
 */
return [
    // chrome
    'app_title'    => 'Ispledger Bet',
    'app_subtitle' => 'Quote e scommesse automatiche',
    'logout'       => 'Esci',
    'public_site'  => 'Sito pubblico',

    // login
    'login_title'   => 'Ispledger Bet',
    'login_sub'     => 'Accedi al pannello di controllo',
    'login_user_ph' => 'Nome utente',
    'login_ph'      => 'Password',
    'login_btn'     => 'Accedi',
    'login_err'     => 'Nome utente o password errati',

    // nav
    'nav_overview' => 'Panoramica',
    'nav_odds'     => 'Surebet',
    'nav_events'   => 'Eventi',
    'nav_bets'     => 'Scommesse',
    'nav_rules'    => 'Regole di automazione',
    'nav_books'    => 'Bookmaker',
    'nav_logs'     => 'Registro attività',
    'nav_users'    => 'Utenti',
    'nav_settings' => 'Impostazioni',

    // common
    'save'           => 'Salva',
    'saved'          => 'Salvato.',
    'cancel'         => 'Annulla',
    'actions'        => 'Azioni',
    'none_yet'       => 'Ancora nulla.',
    'not_allowed'    => 'Non hai i permessi per questa azione.',
    'configured'     => 'Configurato',
    'not_configured' => 'Non configurato',
    'coming_soon'    => 'Non ancora collegato.',
    'settings_saved_n' => 'campi salvati',

    // overview
    'ov_title'    => 'Panoramica',
    'ov_lead'     => 'Account e struttura del pannello sono attivi. L\'aggregazione '
                   . 'delle quote e le scommesse automatiche non sono ancora collegate.',
    'ov_books'    => 'Bookmaker monitorati',
    'ov_events'   => 'Eventi di oggi',
    'ov_bets'     => 'Scommesse aperte',
    'ov_pnl'      => 'Profitti / perdite',
    'ov_activity' => 'Attività recente',
    'ov_active_sb' => 'Surebet attive',
    'ov_best'      => 'migliore',
    'ov_top'       => 'Migliori opportunità',
    'ov_all'       => 'Vedi tutte',

    // placeholder pages
    'ph_odds'   => 'Qui apparirà il confronto delle quote in tempo reale tra i bookmaker.',
    'ph_events' => 'Qui saranno elencati gli eventi e i mercati in programma.',
    'ph_bets'   => 'Qui saranno tracciate le scommesse piazzate e liquidate.',
    'ph_rules'  => 'Regole di puntata e di attivazione per le scommesse automatiche.',
    'ph_books'  => 'Account bookmaker e stato della connessione.',
    'ph_logs'   => 'Attività di sistema e di automazione.',

    // users
    'users_title'    => 'Utenti',
    'users_lead'     => 'Operatori del pannello.',
    'u_username'     => 'Nome utente',
    'u_fullname'     => 'Nome completo',
    'u_email'        => 'Email',
    'u_role'         => 'Ruolo',
    'u_password'     => 'Password',
    'u_active'       => 'Attivo',
    'u_add'          => 'Aggiungi utente',
    'u_created'      => 'Utente creato.',
    'u_updated'      => 'Utente aggiornato.',
    'u_deleted'      => 'Utente eliminato.',
    'u_pw_changed'   => 'Password modificata.',
    'u_delete'       => 'Elimina',
    'u_confirm_del'  => 'Eliminare questo utente?',
    'u_last_admin'   => 'Non puoi rimuovere l\'ultimo amministratore attivo.',
    'u_exists'       => 'Questo nome utente è già in uso.',
    'u_enable'       => 'Abilita',
    'u_disable'      => 'Disabilita',
    'u_yes'          => 'Sì',
    'u_no'           => 'No',

    // surebets
    'sb_title'     => 'Surebet',
    'sb_lead'      => 'Opportunità di arbitraggio attive, ROI più alto per primo. Le '
                    . 'puntate sono divise per ottenere lo stesso ritorno con qualsiasi esito.',
    'sb_none'      => 'Nessuna surebet attiva. Esegui lo scanner o abbassa il ROI minimo.',
    'sb_no_table'  => 'Tabelle arbitraggio mancanti — esegui db/migrations/002_arbitrage.sql.',
    'sb_min_roi'   => 'ROI minimo %',
    'sb_bankroll'  => 'Capitale',
    'sb_apply'     => 'Applica',
    'sb_selection' => 'Esito',
    'sb_book'      => 'Bookmaker',
    'sb_price'     => 'Quota',
    'sb_stake'     => 'Puntata',
    'sb_returns'   => 'Ritorno',
    'sb_total'     => 'Totale puntato',
    'sb_payout'    => 'Incasso',
    'sb_profit'    => 'Profitto',
    'sb_take'      => 'Piazza scommessa',
    'sb_market'    => 'Mercato',

    // events
    'ev_lead'   => 'Eventi con quote salvate e quanti bookmaker li quotano.',
    'ev_match'  => 'Partita',
    'ev_league' => 'Campionato',
    'ev_start'  => 'Inizio',
    'ev_books'  => 'Book',

    // bets
    'bt_lead'    => 'Posizioni prese dalle surebet. Quote e puntate sono congelate al piazzamento.',
    'bt_none'    => 'Nessuna scommessa registrata.',
    'bt_count'   => 'Scommesse piazzate',
    'bt_staked'  => 'Totale puntato',
    'bt_open'    => 'Puntate aperte',
    'bt_profit'  => 'Profitto',
    'bt_legs'    => 'Giocate',
    'bt_stake'   => 'Puntata',
    'bt_exp_roi' => 'ROI previsto',
    'bt_status'  => 'Stato',
    'bt_settle'  => 'Liquida',
    'bt_void'    => 'Annullata',
    'bt_open_s'  => 'Aperta',
    'bt_won'     => 'Vinta',
    'bt_lost'    => 'Persa',
    'bt_taken'   => 'Scommessa registrata.',
    'bt_settled' => 'Scommessa liquidata.',

    // settings
    'set_title'    => 'Impostazioni',
    'set_lead'     => 'I valori salvati qui hanno la precedenza su config/config.php.',
    'set_company'  => 'Nome azienda',
    'set_baseurl'  => 'URL pubblico di base',
    'set_lang'     => 'Lingua predefinita',
    'set_tz'       => 'Fuso orario',
];
