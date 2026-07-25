<?php
declare(strict_types=1);

/**
 * Public landing page for betting.ispeldger.com. Static marketing copy — the
 * only live link is through to the dashboard login.
 */
require __DIR__ . '/../src/Bootstrap.php';

use Bet\Bootstrap;
use Bet\Config;

Bootstrap::init();

$avail = ['en', 'it'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $avail, true)) {
    setcookie('bet_ui_lang', $_GET['lang'], time() + 31536000, '/');
    $_COOKIE['bet_ui_lang'] = $_GET['lang'];
}
$default = (string)Config::get('app.default_lang', 'en');
$lang = in_array($_COOKIE['bet_ui_lang'] ?? '', $avail, true)
    ? $_COOKIE['bet_ui_lang']
    : (in_array($default, $avail, true) ? $default : 'en');

$h = fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$brand = (string)Config::get('app.company_name', 'Ispeldger Bet');

$copy = [
    'en' => [
        'tagline'  => 'Find the best odds. Never place a bet manually again.',
        'sub'      => 'We compare prices across bookmakers in real time and surface the '
                    . 'strongest line on every market — so your stake always goes where it is worth most.',
        'cta'      => 'Open dashboard',
        'f1_t'     => 'Real-time odds comparison',
        'f1_b'     => 'Continuously aggregated pricing across books, ranked so the best available line is always on top.',
        'f2_t'     => 'Automated placement',
        'f2_b'     => 'Define your rules once — stake, market, threshold — and let them run without watching a screen.',
        'f3_t'     => 'Tracked performance',
        'f3_b'     => 'Every position logged with the price you got versus the market, so returns are measured, not guessed.',
        'note'     => 'Platform in development — the odds feed and automation are not live yet.',
        'footer'   => 'Please gamble responsibly. 18+.',
    ],
    'it' => [
        'tagline'  => 'Trova le quote migliori. Mai più una scommessa piazzata a mano.',
        'sub'      => 'Confrontiamo i prezzi tra i bookmaker in tempo reale e mostriamo la '
                    . 'quota più alta su ogni mercato — così la tua puntata vale sempre il massimo.',
        'cta'      => 'Apri il pannello',
        'f1_t'     => 'Confronto quote in tempo reale',
        'f1_b'     => 'Prezzi aggregati di continuo tra i bookmaker, ordinati con la quota migliore sempre in cima.',
        'f2_t'     => 'Piazzamento automatico',
        'f2_b'     => 'Definisci le regole una volta — puntata, mercato, soglia — e lasciale lavorare da sole.',
        'f3_t'     => 'Rendimento tracciato',
        'f3_b'     => 'Ogni posizione registrata con il prezzo ottenuto rispetto al mercato: risultati misurati, non stimati.',
        'note'     => 'Piattaforma in sviluppo — il feed delle quote e l\'automazione non sono ancora attivi.',
        'footer'   => 'Gioca responsabilmente. 18+.',
    ],
];
$c = $copy[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $h($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($brand) ?></title>
<style>
:root{
  --bg:#0e131c;--surface:#161c28;--surface2:#1c2533;--line:#28303f;--line2:#39435a;
  --txt:#e7ecf4;--muted:#8b95a7;--accent:#3fb868;--amber:#d9a40a;--amber-bg:rgba(217,164,10,.13);
  --radius:12px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',system-ui,sans-serif;color:var(--txt);font-size:14px;line-height:1.5;
  background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;}
a{color:inherit;text-decoration:none;}
.muted{color:var(--muted);} .small{font-size:12px;}
.wrap{max-width:1000px;margin:0 auto;padding:0 24px;}
.logo{width:38px;height:38px;border-radius:10px;background:var(--accent);display:flex;align-items:center;
  justify-content:center;font-weight:800;color:#fff;font-size:17px;flex:0 0 auto;}
header.site{display:flex;align-items:center;justify-content:space-between;padding:18px 0;
  border-bottom:1px solid var(--line);}
.brand{display:flex;gap:11px;align-items:center;}
.brand strong{font-size:15px;}
.actions{display:flex;gap:12px;align-items:center;}
.langsw{display:inline-flex;background:var(--surface2);border:1px solid var(--line);border-radius:8px;padding:2px;}
.langsw a{padding:4px 9px;border-radius:6px;color:var(--muted);font-weight:600;font-size:12px;}
.langsw a.on{background:var(--accent);color:#fff;}
.btn{padding:10px 16px;border:none;border-radius:8px;background:var(--accent);color:#fff;font-weight:600;
  cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:7px;transition:filter .12s;}
.btn:hover{filter:brightness(1.08);}
.btn.ghost{background:var(--surface2);border:1px solid var(--line);color:var(--txt);}
.hero{padding:86px 0 56px;text-align:center;}
.hero h1{font-size:clamp(28px,5vw,46px);line-height:1.15;letter-spacing:-.02em;margin-bottom:16px;}
.hero p{font-size:16px;color:var(--muted);max-width:640px;margin:0 auto 30px;line-height:1.65;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;padding-bottom:34px;}
.tile{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);
  padding:20px;transition:border-color .12s;}
.tile:hover{border-color:var(--line2);}
.tile h3{font-size:15px;margin-bottom:8px;}
.tile p{color:var(--muted);font-size:13.5px;line-height:1.6;}
.warn{background:var(--amber-bg);border:1px solid var(--amber);color:var(--amber);padding:12px 16px;
  border-radius:8px;margin-bottom:34px;font-size:13px;text-align:center;}
footer.site{border-top:1px solid var(--line);padding:24px 0;color:var(--muted);font-size:12.5px;text-align:center;}
@media(max-width:560px){.hero{padding:56px 0 40px;}.grid{grid-template-columns:1fr;}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrap">
  <header class="site">
    <div class="brand">
      <div class="logo"><?= $h(strtoupper(substr($brand, 0, 1)) ?: 'B') ?></div>
      <strong><?= $h($brand) ?></strong>
    </div>
    <div class="actions">
      <span class="langsw">
        <a class="<?= $lang === 'en' ? 'on' : '' ?>" href="?lang=en">EN</a>
        <a class="<?= $lang === 'it' ? 'on' : '' ?>" href="?lang=it">IT</a>
      </span>
      <a class="btn ghost" href="dashboard.php"><?= $h($c['cta']) ?></a>
    </div>
  </header>

  <section class="hero">
    <h1><?= $h($c['tagline']) ?></h1>
    <p><?= $h($c['sub']) ?></p>
    <a class="btn" href="dashboard.php"><?= $h($c['cta']) ?></a>
  </section>

  <div class="warn"><?= $h($c['note']) ?></div>

  <section class="grid">
    <div class="tile"><h3><?= $h($c['f1_t']) ?></h3><p><?= $h($c['f1_b']) ?></p></div>
    <div class="tile"><h3><?= $h($c['f2_t']) ?></h3><p><?= $h($c['f2_b']) ?></p></div>
    <div class="tile"><h3><?= $h($c['f3_t']) ?></h3><p><?= $h($c['f3_b']) ?></p></div>
  </section>

  <footer class="site">
    &copy; <?= date('Y') ?> <?= $h($brand) ?> — <?= $h($c['footer']) ?>
  </footer>
</div>
</body>
</html>
