<?php
declare(strict_types=1);

/**
 * Betting control panel — sidebar + header layout, EN/IT, DB-backed settings.
 * Thin controller: handles auth + POST actions, then includes a per-page view
 * from /views. Domain logic (odds, events, bets, automation) is not wired up
 * yet — those tabs render placeholder views on purpose.
 */
require __DIR__ . '/src/Bootstrap.php';

use Bet\Auth;
use Bet\Bootstrap;
use Bet\Config;
use Bet\Settings;

Bootstrap::init();
Auth::ensureSeed(); // create default admin/admin on first run

session_set_cookie_params(31536000, '/', '', false, true);
session_start();

// ---- language ----
$avail = ['en', 'it'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $avail, true)) {
    setcookie('bet_ui_lang', $_GET['lang'], time() + 31536000, '/');
    $_COOKIE['bet_ui_lang'] = $_GET['lang'];
}
$default = (string)Config::get('app.default_lang', 'en');
$lang = in_array($_COOKIE['bet_ui_lang'] ?? '', $avail, true)
    ? $_COOKIE['bet_ui_lang']
    : (in_array($default, $avail, true) ? $default : 'en');
$UI = require __DIR__ . '/lang/ui.' . $lang . '.php';
$t = fn(string $k): string => $UI[$k] ?? $k;
$h = fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// ---- auth ----
if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: ?');
    exit;
}

$flash = null;
$flashType = 'ok';
// flash left by a previous redirect (post/redirect/get)
if (!empty($_SESSION['dash_flash'])) {
    [$flash, $flashType] = $_SESSION['dash_flash'];
    unset($_SESSION['dash_flash']);
}

if (!isset($_SESSION['bet_auth'])) {
    $err = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $username = trim((string)($_POST['username'] ?? ''));
        $user     = Auth::verify($username, (string)$_POST['password']);
        $masterPw = (string)Config::get('dashboard.password', '');
        if (!$user && $masterPw !== '' && hash_equals($masterPw, (string)$_POST['password'])) {
            $user = ['id' => 0, 'username' => ($username ?: 'admin'), 'role' => 'admin'];
        }
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['bet_auth'] = true;
            $_SESSION['bet_user'] = $user;
            header('Location: ?');
            exit;
        }
        $err = $t('login_err');
    }
    render_login($t, $h, $lang, $err);
    exit;
}

$me      = $_SESSION['bet_user'] ?? ['username' => '', 'role' => 'admin'];
$isAdmin = ($me['role'] ?? '') === 'admin';

/** Redirect back to a tab with a flash message (post/redirect/get). */
function redirect_flash(string $tab, string $msg, string $type = 'ok'): never
{
    $_SESSION['dash_flash'] = [$msg, $type];
    header('Location: ?tab=' . urlencode($tab));
    exit;
}

// ---- tabs ----
$tabs = ['overview', 'odds', 'events', 'bets', 'rules', 'books', 'logs', 'users', 'settings'];
$tab  = in_array($_GET['tab'] ?? '', $tabs, true) ? $_GET['tab'] : 'overview';

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do'])) {
    if (!$isAdmin) {
        redirect_flash($tab, $t('not_allowed'), 'err');
    }
    $do = (string)$_POST['do'];

    if ($do === 'user_create') {
        try {
            $id = Auth::create(
                (string)($_POST['username'] ?? ''),
                (string)($_POST['password'] ?? ''),
                (string)($_POST['role'] ?? 'admin')
            );
            Auth::updateProfile($id, [
                'full_name' => trim((string)($_POST['full_name'] ?? '')),
                'email'     => trim((string)($_POST['email'] ?? '')),
            ]);
            redirect_flash('users', $t('u_created'));
        } catch (PDOException $e) {
            redirect_flash('users', $e->getCode() === '23000' ? $t('u_exists') : $e->getMessage(), 'err');
        } catch (Throwable $e) {
            redirect_flash('users', $e->getMessage(), 'err');
        }
    }

    if ($do === 'user_password') {
        try {
            Auth::setPassword((int)($_POST['id'] ?? 0), (string)($_POST['password'] ?? ''));
            redirect_flash('users', $t('u_pw_changed'));
        } catch (Throwable $e) {
            redirect_flash('users', $e->getMessage(), 'err');
        }
    }

    if ($do === 'user_toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $on = (int)($_POST['active'] ?? 0) === 1;
        // Disabling the last active admin would lock everyone out.
        if (!$on && Auth::activeAdminCount() <= 1) {
            redirect_flash('users', $t('u_last_admin'), 'err');
        }
        Auth::setActive($id, $on);
        redirect_flash('users', $t('u_updated'));
    }

    if ($do === 'user_delete') {
        try {
            Auth::delete((int)($_POST['id'] ?? 0));
            redirect_flash('users', $t('u_deleted'));
        } catch (Throwable) {
            redirect_flash('users', $t('u_last_admin'), 'err');
        }
    }

    if ($do === 'settings_save') {
        $allowed = ['app.company_name', 'app.base_url', 'app.default_lang', 'app.timezone'];
        $n = Settings::setMany((array)($_POST['s'] ?? []), $allowed);
        redirect_flash('settings', $n . ' ' . $t('settings_saved_n'));
    }
}

// ---- render ----
render_head($t, $h, $lang, $tab, $flash, $flashType);

$viewFile = __DIR__ . '/views/' . $tab . '.php';
if (is_file($viewFile)) {
    include $viewFile;
} else {
    include __DIR__ . '/views/placeholder.php';
}

render_foot();


// ---------------------------------------------------------------- rendering

function render_login(callable $t, callable $h, string $lang, ?string $err): void { ?>
<!DOCTYPE html><html lang="<?= $h($lang) ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($t('login_title')) ?></title><?php css(); ?></head>
<body class="center">
  <form class="login" method="post">
    <div class="logo">B</div>
    <h1><?= $h($t('login_title')) ?></h1>
    <p class="muted"><?= $h($t('login_sub')) ?></p>
    <?php if ($err): ?><p class="err"><?= $h($err) ?></p><?php endif; ?>
    <input type="text" name="username" placeholder="<?= $h($t('login_user_ph')) ?>" autofocus>
    <input type="password" name="password" placeholder="<?= $h($t('login_ph')) ?>">
    <button type="submit"><?= $h($t('login_btn')) ?></button>
  </form>
</body></html>
<?php }

function render_head(callable $t, callable $h, string $lang, string $tab, ?string $flash, string $flashType): void {
    $brand = (string)\Bet\Config::get('app.company_name', '') ?: $t('app_title');
    $nav = [
        'overview' => 'nav_overview', 'odds' => 'nav_odds', 'events' => 'nav_events',
        'bets' => 'nav_bets', 'rules' => 'nav_rules', 'books' => 'nav_books',
        'logs' => 'nav_logs', 'users' => 'nav_users', 'settings' => 'nav_settings',
    ]; ?>
<!DOCTYPE html><html lang="<?= $h($lang) ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($brand) ?></title><?php css(); ?></head>
<body>
<div class="shell">
  <div class="nav-backdrop" id="navBackdrop" onclick="closeNav()"></div>
  <aside class="sidebar" id="sidebar">
    <div class="brand"><div class="logo"><?= $h(strtoupper(substr($brand, 0, 1)) ?: 'B') ?></div>
      <div><strong><?= $h($brand) ?></strong><span class="muted small"><?= $h($t('app_subtitle')) ?></span></div></div>
    <nav>
      <?php foreach ($nav as $key => $label): ?>
        <a class="<?= $tab === $key ? 'active' : '' ?>" href="?tab=<?= $h($key) ?>"><?= svg($key) ?><span><?= $h($t($label)) ?></span></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main>
    <header class="topbar">
      <button class="navtoggle" id="navToggle" onclick="openNav()" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="crumb"><?= $h($t('nav_' . $tab)) ?></div>
      <div class="actions">
        <a class="btn ghost tiny pubform" href="index.php" target="_blank"><?= svg('link') ?> <span><?= $h($t('public_site')) ?></span></a>
        <span class="langsw">
          <a class="<?= $lang === 'en' ? 'on' : '' ?>" href="?tab=<?= $h($tab) ?>&lang=en">EN</a>
          <a class="<?= $lang === 'it' ? 'on' : '' ?>" href="?tab=<?= $h($tab) ?>&lang=it">IT</a>
        </span>
        <span class="muted small who"><?= $h($_SESSION['bet_user']['username'] ?? '') ?></span>
        <a class="btn ghost" href="?action=logout"><?= $h($t('logout')) ?></a>
      </div>
    </header>
    <div class="content">
    <?php if ($flash): ?><div class="flash <?= $flashType === 'err' ? 'flash-err' : '' ?>"><?= $h($flash) ?></div><?php endif; ?>
<?php }

function render_foot(): void { ?>
</div></main></div>
<script>
// Mobile sidebar drawer: open/close + close on backdrop tap, Escape, or nav click.
function openNav(){document.getElementById('sidebar').classList.add('open');
  document.getElementById('navBackdrop').classList.add('show');}
function closeNav(){document.getElementById('sidebar').classList.remove('open');
  document.getElementById('navBackdrop').classList.remove('show');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeNav();});
// Double-submit guard: once a form is actually submitting, disable its submit
// button so a second click can't fire the same POST twice.
document.addEventListener('submit',function(e){
  var b=e.target.querySelector('button[type=submit],button:not([type]),input[type=submit]');
  if(b){setTimeout(function(){b.disabled=true;b.style.opacity='0.6';},0);}
});
// Make every table horizontally scrollable on small screens without editing each view.
document.querySelectorAll('main table').forEach(function(tb){
  if(!tb.parentElement.classList.contains('table-wrap')){
    var w=document.createElement('div');w.className='table-wrap';
    tb.parentNode.insertBefore(w,tb);w.appendChild(tb);
  }
});
</script>
</body></html>
<?php }

function svg(string $name): string {
    $p = [
        'overview' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'odds'     => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'events'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'bets'     => '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M3 11v3"/><line x1="7" y1="10" x2="7" y2="15"/>',
        'rules'    => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'books'    => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>',
        'logs'     => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'link'     => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'trophy'   => '<path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4z"/><path d="M5 4H3v2a3 3 0 0 0 3 3M19 4h2v2a3 3 0 0 1-3 3"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
    ];
    $body = $p[$name] ?? $p['overview'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function css(): void { ?>
<style>
:root{
  --bg:#0e131c;--surface:#161c28;--surface2:#1c2533;--line:#28303f;--line2:#39435a;
  --txt:#e7ecf4;--muted:#8b95a7;--accent:#3fb868;--accent-soft:rgba(63,184,104,.14);
  --green:#3fb868;--green-bg:rgba(63,184,104,.13);--red:#e5616e;--red-bg:rgba(229,97,110,.13);
  --amber:#d9a40a;--amber-bg:rgba(217,164,10,.13);--radius:12px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',system-ui,sans-serif;color:var(--txt);font-size:14px;line-height:1.5;
  background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;}
.center{display:flex;align-items:center;justify-content:center;min-height:100vh;}
.muted{color:var(--muted);} .small{font-size:12px;} .big{font-size:30px;font-weight:700;letter-spacing:-.02em;}
a{color:inherit;text-decoration:none;}
.logo{width:40px;height:40px;border-radius:10px;background:var(--accent);display:flex;align-items:center;
  justify-content:center;font-weight:800;color:#fff;font-size:18px;flex:0 0 auto;}
.shell{display:flex;min-height:100vh;}
.sidebar{width:236px;background:var(--surface);border-right:1px solid var(--line);
  padding:18px 14px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;flex:0 0 auto;}
.brand{display:flex;gap:11px;align-items:center;margin:4px 6px 22px;}
.brand strong{display:block;font-size:15px;} .brand span{display:block;line-height:1.3;margin-top:2px;}
nav{display:flex;flex-direction:column;gap:2px;overflow-y:auto;}
nav a{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:8px;color:var(--muted);
  font-weight:500;transition:background .12s,color .12s;}
nav a svg{width:18px;height:18px;flex:0 0 auto;}
nav a:hover{background:var(--surface2);color:var(--txt);}
nav a.active{background:var(--accent);color:#fff;}
main{flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:13px 28px;
  border-bottom:1px solid var(--line);background:var(--surface);position:sticky;top:0;z-index:5;}
.crumb{font-weight:700;font-size:17px;}
.actions{display:flex;gap:12px;align-items:center;}
.actions .btn.tiny svg{width:14px;height:14px;}
.langsw{display:inline-flex;background:var(--surface2);border:1px solid var(--line);border-radius:8px;padding:2px;}
.langsw a{padding:4px 9px;border-radius:6px;color:var(--muted);font-weight:600;font-size:12px;}
.langsw a.on{background:var(--accent);color:#fff;}
.content{padding:24px 28px;width:100%;}
h2{font-size:21px;margin-bottom:18px;letter-spacing:-.01em;} h3{font-size:15px;margin:16px 0 12px;}
.lead{font-size:15px;color:var(--muted);margin-bottom:20px;line-height:1.65;max-width:820px;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:16px;}
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:20px;margin-bottom:16px;}
.tile{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px;transition:border-color .12s;}
.tile:hover{border-color:var(--line2);}
.tile-top{display:flex;align-items:center;gap:9px;margin-bottom:10px;color:var(--muted);}
.tile-top svg{width:17px;height:17px;}
.tile .big{display:block;margin-top:6px;} .tile .sub{font-size:12px;color:var(--muted);margin-top:4px;}
.badge{display:inline-flex;align-items:center;gap:7px;padding:5px 11px;border-radius:7px;font-size:12.5px;font-weight:600;}
.badge .dot{width:7px;height:7px;border-radius:50%;}
.badge.ok{background:var(--green-bg);color:var(--green);} .badge.ok .dot{background:var(--green);}
.badge.no{background:var(--red-bg);color:var(--red);} .badge.no .dot{background:var(--red);}
.panel{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;}
.panel-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.panel-h h3{margin:0;display:flex;align-items:center;gap:9px;} .panel-h h3 svg{width:17px;height:17px;color:var(--muted);}
.empty{color:var(--muted);text-align:center;padding:26px 0;font-size:13px;}
.fld{display:block;margin-bottom:16px;} .fld span{display:block;margin-bottom:7px;color:var(--muted);font-size:13px;font-weight:500;}
input,select,textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--bg);
  color:var(--txt);font-size:14px;outline:none;font-family:inherit;transition:border-color .12s;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
.fld small{display:block;margin-top:6px;font-size:12px;line-height:1.5;}
.row{display:flex;gap:14px;flex-wrap:wrap;} .row .fld{flex:1;min-width:150px;}
.btn{padding:10px 16px;border:none;border-radius:8px;background:var(--accent);color:#fff;font-weight:600;
  cursor:pointer;font-size:14px;transition:filter .12s;display:inline-flex;align-items:center;gap:7px;} .btn:hover{filter:brightness(1.08);}
.btn svg{width:15px;height:15px;}
.btn.ghost{background:var(--surface2);border:1px solid var(--line);color:var(--txt);}
.btn.ghost:hover{border-color:var(--line2);filter:none;background:var(--surface);}
.btn.danger{background:var(--red-bg);border:1px solid var(--red);color:var(--red);}
.btn.danger:hover{background:var(--red);color:#fff;filter:none;}
.btn.tiny{padding:6px 12px;font-size:12.5px;}
.inline{display:inline-flex;gap:8px;align-items:center;margin:0 10px 8px 0;}
.inline input,.inline select{width:auto;}
table{width:100%;border-collapse:separate;border-spacing:0;background:var(--surface);
  border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;}
th,td{text-align:left;padding:11px 14px;border-bottom:1px solid var(--line);vertical-align:middle;}
th{color:var(--muted);font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;font-weight:600;background:var(--surface2);}
tbody tr:hover{background:var(--surface2);} tr:last-child td{border-bottom:none;}
.pill{display:inline-block;padding:4px 10px;border-radius:7px;background:var(--surface2);font-size:12px;font-weight:600;border:1px solid var(--line);text-transform:capitalize;}
.pill-yes{color:var(--green);background:var(--green-bg);border-color:transparent;}
.pill-no{color:var(--red);background:var(--red-bg);border-color:transparent;}
.flash{background:var(--green-bg);border:1px solid var(--green);color:var(--green);padding:12px 16px;
  border-radius:8px;margin-bottom:18px;word-break:break-word;font-weight:500;}
.flash-err{background:var(--red-bg);border-color:var(--red);color:var(--red);}
.warn{background:var(--amber-bg);border:1px solid var(--amber);color:var(--amber);padding:12px 16px;
  border-radius:8px;margin-bottom:18px;font-size:13px;line-height:1.55;}
.step{background:var(--surface);border:1px solid var(--line);border-left:3px solid var(--accent);
  border-radius:10px;padding:17px 21px;margin-bottom:14px;}
.step p{line-height:1.65;color:var(--muted);} .step b{color:var(--txt);font-weight:600;}
.login{background:var(--surface);padding:38px 36px;border-radius:14px;width:360px;text-align:center;border:1px solid var(--line);}
.login .logo{margin:0 auto 18px;width:50px;height:50px;font-size:22px;} .login h1{font-size:21px;margin-bottom:5px;}
.login input{margin:9px 0;} .login button{width:100%;margin-top:10px;}
.err{color:var(--red);font-size:13px;margin-bottom:8px;}
.navtoggle{display:none;background:var(--surface2);border:1px solid var(--line);color:var(--txt);
  border-radius:8px;padding:7px;cursor:pointer;align-items:center;justify-content:center;margin-right:4px;}
.navtoggle svg{width:20px;height:20px;display:block;}
.nav-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;}
@media(max-width:900px){
  .sidebar{position:fixed;top:0;left:0;height:100dvh;width:248px;z-index:50;
    transform:translateX(-100%);transition:transform .22s ease;box-shadow:0 0 40px rgba(0,0,0,.4);}
  .sidebar.open{transform:translateX(0);}
  .nav-backdrop.show{display:block;}
  .navtoggle{display:inline-flex;}
  .topbar{padding:11px 16px;gap:10px;}
  .crumb{font-size:16px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .content{padding:16px;}
  .actions{gap:8px;} .actions .btn.tiny span{display:none;}
  .topbar .pubform,.topbar .who{display:none;}
}
@media(max-width:560px){
  .row{flex-direction:column;gap:0;} .row .fld{min-width:0;}
  .grid{grid-template-columns:1fr;}
  .langsw a{padding:4px 8px;}
  .login{width:100%;max-width:360px;padding:30px 22px;}
  th,td{padding:9px 11px;}
}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;margin-bottom:16px;}
.table-wrap table{margin-bottom:0;}
@media(max-width:560px){.table-wrap table{min-width:520px;}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php }
