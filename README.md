# betting.ispeldger.com

Automated betting platform — odds aggregation and rule-driven placement.
This stage is the **shell only**: landing page, login, dashboard chrome, users
and settings. No odds feed, no bet placement, no scraping logic yet.

Structure and house style follow the existing `bitrix` project (same layout,
EN/IT translation layer, DB-backed settings), under the `Bet\` namespace.

## Layout

```
public/       docroot — index.php (landing), dashboard.php (panel), .htaccess
src/          Bootstrap, Config, Db, Auth, Settings  (namespace Bet\)
views/        one file per dashboard tab
lang/         ui.en.php / ui.it.php — keys mirror each other exactly
config/       config.sample.php (committed) · config.php (git-ignored)
db/           schema.sql
deploy/       Apache vhost
```

Only `public/` is served. Everything else sits above the docroot.

## Setup

1. **Database** — as a MySQL superuser:
   ```
   mysql -u root -p < db/schema.sql
   ```
   Creates the `betting` database, the `betting` user, and the
   `users` + `settings` tables.

2. **Config**
   ```
   cp config/config.sample.php config/config.php
   ```
   Fill in the `db` block. `config.php` is git-ignored — real credentials
   never enter the repo.

3. **Apache**
   ```
   cp deploy/betting.ispeldger.com.conf /etc/apache2/sites-available/
   a2ensite betting.ispeldger.com && a2enmod rewrite headers
   apache2ctl configtest && systemctl reload apache2
   certbot --apache -d betting.ispeldger.com
   ```

4. **PHP** — needs `pdo_mysql` enabled (8.1+).

## First login

The `users` table seeds `admin` / `admin` on first boot.
**Change it immediately** from the Users tab.

`dashboard.password` in config is an optional master fallback so an operator
can't be locked out; leave it empty to disable.

## Dashboard tabs

`Overview` · `Users` · `Settings` are functional.
`Odds` · `Events` · `Bets` · `Automation rules` · `Bookmakers` · `Activity log`
render placeholders — they are the slots the real logic drops into.

Settings written from the dashboard are stored as dot-path keys in `settings`
and overlaid onto `config/config.php` at boot, so they win over file defaults.

## Translations

`lang/ui.en.php` and `lang/ui.it.php` must keep identical keys. Language is
chosen by the `bet_ui_lang` cookie, falling back to `app.default_lang`.

## Note on the odds feed

Scraping bookmaker sites directly is prohibited by most sportsbooks' terms and
is actively blocked by several. Before the aggregation layer is built, decide
between a licensed odds API and per-bookmaker agreements — that choice shapes
the whole ingestion design.
