<?php
/**
 * English UI strings for the dashboard (sidebar, header, forms).
 * Keys mirror ui.it.php exactly.
 */
return [
    // chrome
    'app_title'    => 'Ispledger Bet',
    'app_subtitle' => 'Odds & automated betting',
    'logout'       => 'Log out',
    'public_site'  => 'Public site',

    // login
    'login_title'   => 'Ispledger Bet',
    'login_sub'     => 'Sign in to the control panel',
    'login_user_ph' => 'Username',
    'login_ph'      => 'Password',
    'login_btn'     => 'Sign in',
    'login_err'     => 'Wrong username or password',

    // nav
    'nav_overview' => 'Overview',
    'nav_odds'     => 'Surebets',
    'nav_events'   => 'Events',
    'nav_bets'     => 'Bets',
    'nav_rules'    => 'Automation rules',
    'nav_books'    => 'Bookmakers',
    'nav_logs'     => 'Activity log',
    'nav_users'    => 'Users',
    'nav_settings' => 'Settings',

    // common
    'save'           => 'Save',
    'saved'          => 'Saved.',
    'cancel'         => 'Cancel',
    'actions'        => 'Actions',
    'none_yet'       => 'Nothing yet.',
    'not_allowed'    => 'You do not have permission for that action.',
    'configured'     => 'Configured',
    'not_configured' => 'Not configured',
    'coming_soon'    => 'Not wired up yet.',
    'settings_saved_n' => 'fields saved',

    // overview
    'ov_title'    => 'Overview',
    'ov_lead'     => 'Account and dashboard shell are live. Odds aggregation and '
                   . 'automated placement are not connected yet.',
    'ov_books'    => 'Bookmakers tracked',
    'ov_events'   => 'Events today',
    'ov_bets'     => 'Open bets',
    'ov_pnl'      => 'Profit / loss',
    'ov_activity' => 'Recent activity',
    'ov_active_sb' => 'Active surebets',
    'ov_best'      => 'best',
    'ov_top'       => 'Top opportunities',
    'ov_all'       => 'View all',

    // placeholder pages
    'ph_odds'   => 'Live odds comparison across bookmakers will appear here.',
    'ph_events' => 'Upcoming fixtures and markets will be listed here.',
    'ph_bets'   => 'Placed and settled bets will be tracked here.',
    'ph_rules'  => 'Staking and trigger rules for automated placement.',
    'ph_books'  => 'Bookmaker accounts and connection status.',
    'ph_logs'   => 'System and automation activity.',

    // users
    'users_title'    => 'Users',
    'users_lead'     => 'Dashboard operators.',
    'u_username'     => 'Username',
    'u_fullname'     => 'Full name',
    'u_email'        => 'Email',
    'u_role'         => 'Role',
    'u_password'     => 'Password',
    'u_active'       => 'Active',
    'u_add'          => 'Add user',
    'u_created'      => 'User created.',
    'u_updated'      => 'User updated.',
    'u_deleted'      => 'User deleted.',
    'u_pw_changed'   => 'Password changed.',
    'u_delete'       => 'Delete',
    'u_confirm_del'  => 'Delete this user?',
    'u_last_admin'   => 'Cannot remove the last active admin.',
    'u_exists'       => 'That username is already taken.',
    'u_enable'       => 'Enable',
    'u_disable'      => 'Disable',
    'u_yes'          => 'Yes',
    'u_no'           => 'No',

    // surebets
    'sb_title'     => 'Surebets',
    'sb_lead'      => 'Active arbitrage opportunities, best ROI first. Stakes are '
                    . 'split so the return is the same whichever outcome wins.',
    'sb_none'      => 'No active surebets. Run the scanner, or lower the minimum ROI.',
    'sb_no_table'  => 'Arbitrage tables are missing — run db/migrations/002_arbitrage.sql.',
    'sb_min_roi'   => 'Minimum ROI %',
    'sb_bankroll'  => 'Bankroll',
    'sb_apply'     => 'Apply',
    'sb_selection' => 'Selection',
    'sb_book'      => 'Bookmaker',
    'sb_price'     => 'Odds',
    'sb_stake'     => 'Stake',
    'sb_returns'   => 'Returns',
    'sb_total'     => 'Total staked',
    'sb_payout'    => 'Payout',
    'sb_profit'    => 'Profit',
    'sb_take'      => 'Place bet',
    'sb_market'    => 'Market',

    // events
    'ev_lead'   => 'Fixtures with stored prices, and how many bookmakers quote each one.',
    'ev_match'  => 'Match',
    'ev_league' => 'League',
    'ev_start'  => 'Kick-off',
    'ev_books'  => 'Books',

    // bets
    'bt_lead'    => 'Positions taken from surebets. Prices and stakes are frozen at placement.',
    'bt_none'    => 'No bets recorded yet.',
    'bt_count'   => 'Bets placed',
    'bt_staked'  => 'Total staked',
    'bt_open'    => 'Open stake',
    'bt_profit'  => 'Profit',
    'bt_legs'    => 'Legs',
    'bt_stake'   => 'Stake',
    'bt_exp_roi' => 'Expected ROI',
    'bt_status'  => 'Status',
    'bt_settle'  => 'Settle',
    'bt_void'    => 'Void',
    'bt_open_s'  => 'Open',
    'bt_won'     => 'Won',
    'bt_lost'    => 'Lost',
    'bt_taken'   => 'Bet recorded.',
    'bt_settled' => 'Bet settled.',

    // settings
    'set_title'    => 'Settings',
    'set_lead'     => 'Values saved here override config/config.php.',
    'set_company'  => 'Company name',
    'set_baseurl'  => 'Public base URL',
    'set_lang'     => 'Default language',
    'set_tz'       => 'Timezone',
];
