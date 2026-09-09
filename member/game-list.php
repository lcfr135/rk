<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../rtp/rtp_update.php';

if (!isset($_SESSION['login']) && !isset($_SESSION['id'])) {
    header('Location: /casino/auth/login.php');
    exit;
}

$user_id = $_SESSION['id'] ?? null;
if (!$user_id && isset($_SESSION['login']) && is_numeric($_SESSION['login'])) {
    $user_id = (int) $_SESSION['login'];
}

$user = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id, username, fullname, avatar, balance, level, points FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if (!$user) {
    session_destroy();
    header('Location: /casino/auth/login.php');
    exit;
}

$_SESSION['id'] = $user['id'];
$_SESSION['login'] = true;

$username = $user['username'];
$balance  = (float) $user['balance'];
$level    = $user['level'] ?: 'Bronze';
$avatar_initials = strtoupper(mb_substr($username, 0, 2));
$avatar_file = $user['avatar'] ?? null;
$avatar_url  = $avatar_file ? '../uploads/avatars/' . $avatar_file : null;

$side     = isset($_GET['side']) ? strtolower(trim($_GET['side'])) : 'all';
$cat      = isset($_GET['cat']) ? strtolower(trim($_GET['cat'])) : 'all';
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort     = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$provider = isset($_GET['provider']) ? trim($_GET['provider']) : '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

if (!in_array($sort, ['newest', 'rtp', 'name', 'provider'], true)) $sort = 'newest';

$gameImages = [
    'Big Bass Bonanza'       => '../assets/game/big.png',
    'The Dog House Megaways' => '../assets/game/dog.jpg',
    'Fruit Party'            => '../assets/game/fruit.jpg',
    'Dragon Gold'            => '../assets/game/dragon.png',
    'Gates of Olympus'       => '../assets/game/gates.jpg',
    'Sweet Bonanza'          => '../assets/game/sweet.jpg',
    'Starlight Princess'     => '../assets/game/starlight.jpg',
    'Wild Bandito'           => '../assets/game/wild.jpg',
    'Wanted Dead or a Wild'  => '../assets/game/wanted.png',
    'Mahjong Ways 2'         => '../assets/game/mahjong.png',
    'Lucky Neko'             => '../assets/game/lucky.png',
    'Aviator'                => '../assets/game/aviator.jpg',
    'JetX'                   => '../assets/game/jetx.jpg',
    'Plinko'                 => '../assets/game/plinko.jpg',
    'Mines'                  => '../assets/game/mines.jpg',
    'Spaceman'               => '../assets/game/space.jpg',
    'Buffalo King'           => '../assets/game/bufallo.png',
    'Sugar Rush'             => '../assets/game/sugar.png',
];

/* ===== FAVORITES (DATABASE = SOURCE OF TRUTH) ===== */
$fav_ids = [];
$fav_stmt = $conn->prepare("SELECT game_id FROM user_favorites WHERE user_id = ?");
if ($fav_stmt) {
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $fav_ids[] = (int) $row['game_id'];
    }
    $fav_stmt->close();
}
$fav_count = count($fav_ids);

$sql = "SELECT * FROM rtp_live WHERE 1=1";
$types = '';
$params = [];

if ($side === 'popular') $sql .= " AND LOWER(status) IN ('hot','new')";
elseif ($side === 'rtp') $sql .= " AND rtp >= 96";
elseif ($side === 'new') $sql .= " AND LOWER(status) = 'new'";
elseif ($side === 'fav') {
    $sql .= $fav_count > 0
        ? " AND id IN (" . implode(',', $fav_ids) . ")"
        : " AND 1=0";
}

if ($cat !== 'all' && $cat !== 'hot' && $cat !== 'popular') {
    $sql .= " AND LOWER(category) = ?";
    $types .= 's';
    $params[] = $cat;
}
if ($cat === 'hot' || $cat === 'popular') $sql .= " AND LOWER(status) = 'hot'";
if ($provider !== '') {
    $sql .= " AND provider = ?";
    $types .= 's';
    $params[] = $provider;
}
if ($q !== '') {
    $sql .= " AND (game_name LIKE ? OR provider LIKE ?)";
    $types .= 'ss';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

$count_sql = preg_replace('/SELECT \* FROM/', 'SELECT COUNT(*) AS c FROM', $sql, 1);
if ($types !== '') {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_filtered = (int) $stmt->get_result()->fetch_assoc()['c'];
} else {
    $total_filtered = (int) $conn->query($count_sql)->fetch_assoc()['c'];
}

if ($sort === 'name') $sql .= " ORDER BY game_name ASC";
elseif ($sort === 'provider') $sql .= " ORDER BY provider ASC, rtp DESC";
elseif ($sort === 'rtp') $sql .= " ORDER BY rtp DESC";
else $sql .= " ORDER BY id DESC";

$offset = ($page - 1) * $per_page;
$sql .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $per_page;
$params[] = $offset;

$games = [];
if ($types !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $name = $row['game_name'];
    $badge = strtolower(trim($row['status'] ?? ''));
    if ($badge === '' || $badge === 'null') $badge = 'normal';
    $games[] = [
        'id'       => (int) $row['id'],
        'name'     => $name,
        'provider' => $row['provider'],
        'cat'      => strtolower($row['category'] ?? ''),
        'rtp'      => round((float) $row['rtp'], 2),
        'badge'    => $badge,
        'img'      => $gameImages[$name] ?? '../assets/game/default.jpg',
    ];
}

$providers = [];
$pr = $conn->query("SELECT provider, COUNT(*) AS c FROM rtp_live WHERE provider IS NOT NULL AND provider != '' GROUP BY provider ORDER BY c DESC, provider ASC");
while ($r = $pr->fetch_assoc()) {
    $providers[] = ['name' => $r['provider'], 'count' => (int) $r['c']];
}

/* Logo provider di sidebar (../assets/icons/) */
$providerIcons = [
    'pragmatic' => '../assets/icons/pragmatic.png',
    'pragmatic play' => '../assets/icons/pragmatic.png',
    'pg soft' => '../assets/icons/pgsoft.png',
    'pgsoft' => '../assets/icons/pgsoft.png',
    'pg' => '../assets/icons/pgsoft.png',
    'spribe' => '../assets/icons/spribe.png',
    'hacksaw' => '../assets/icons/hacksaw.png',
    'hacksaw gaming' => '../assets/icons/hacksaw.png',
    'smart' => '../assets/icons/smart.png',
    'smartsoft' => '../assets/icons/smart.png',
    'smart soft' => '../assets/icons/smart.png',
];
function providerIcon(string $name, array $map): ?string {
    $key = strtolower(trim($name));
    if (isset($map[$key])) return $map[$key];
    foreach ($map as $k => $path) {
        if ($k !== '' && str_contains($key, $k)) return $path;
    }
    return null;
}

$total_all = (int) $conn->query("SELECT COUNT(*) AS c FROM rtp_live")->fetch_assoc()['c'];
$total_games = $total_filtered;
$has_more = ($page * $per_page) < $total_filtered;

/* Semua game untuk search modal (client-side, sama seperti member.php) */
$searchGames = [];
$sg = $conn->query("SELECT id, game_name, provider, category, rtp, status FROM rtp_live ORDER BY rtp DESC");
while ($row = $sg->fetch_assoc()) {
    $name = $row['game_name'];
    $searchGames[] = [
        'id'       => (int) $row['id'],
        'name'     => $name,
        'provider' => $row['provider'],
        'cat'      => strtolower($row['category'] ?? ''),
        'rtp'      => round((float) $row['rtp'], 2),
        'badge'    => strtolower(trim($row['status'] ?? '')),
        'img'      => $gameImages[$name] ?? '../assets/game/default.jpg',
    ];
}

function qs(array $extra = []) {
    $base = [
        'side'     => $_GET['side'] ?? 'all',
        'cat'      => $_GET['cat'] ?? 'all',
        'q'        => $_GET['q'] ?? '',
        'sort'     => $_GET['sort'] ?? 'newest',
        'provider' => $_GET['provider'] ?? '',
        'page'     => $_GET['page'] ?? 1,
    ];
    $m = array_merge($base, $extra);
    foreach ($m as $k => $v) {
        if ($v === '' || $v === null) unset($m[$k]);
    }
    if (($m['side'] ?? 'all') === 'all') unset($m['side']);
    if (($m['cat'] ?? 'all') === 'all') unset($m['cat']);
    if (($m['sort'] ?? 'newest') === 'newest') unset($m['sort']);
    if (($m['page'] ?? 1) == 1) unset($m['page']);
    return 'game-list.php' . ($m ? '?' . http_build_query($m) : '');
}

function rtpColor($rtp) {
    if ($rtp >= 96.5) return '#2dff6b';
    if ($rtp >= 95)   return '#7CFF3B';
    if ($rtp >= 92)   return '#FFD700';
    if ($rtp >= 88)   return '#FFA726';
    return '#ff3b5c';
}

$chips = [
  'all'      => ['Semua',       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M8 10v4M12 9v6M16 10v4" stroke-linecap="round"/></svg>'],
  'hot'      => ['Populer',     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2c0 0 3 4 3 8a3 3 0 1 1-6 0c0-4 3-8 3-8z"/><path d="M8.5 14.5C7 16 6 18 6 20a6 6 0 0 0 12 0c0-2-1-4-2.5-5.5" stroke-linecap="round"/></svg>'],
  'slots'    => ['Slot',        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 8v8M12 6v12M16 9v6" stroke-linecap="round"/></svg>'],
  'live'     => ['Live Casino', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>'],
  'bonusbuy' => ['Bonus Buy',   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>'],
  'megaways' => ['Megaways',    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'],
  'jackpot'  => ['Jackpot',     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/><path d="M8 17h8" stroke-linecap="round"/></svg>'],
  'table'    => ['Tabel',       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16" stroke-linecap="round"/></svg>'],
  'lainnya'  => ['Lainnya',     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Game | Royal Knight's</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #FFD700;
  --gold-soft: rgba(255,215,0,.12);
  --gold-border: rgba(255,215,0,.22);
  --bg: #0a0a0c;
  --panel: #0e0e12;
  --card: #121216;
  --text: #f5f5f5;
  --muted: #8a8a96;
  --line: rgba(255,255,255,.07);
  --danger: #ff3b5c;
  --success: #2dff6b;
}
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body {
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  padding-top: 74px;
}

header {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30px;
  background: rgba(18,18,22,.55);
  backdrop-filter: blur(22px);
  -webkit-backdrop-filter: blur(22px);
  border-bottom: 1px solid rgba(255,255,255,.08);
  z-index: 9999;
}
header.scrolled {
  background: rgba(10,10,10,.72);
  border-bottom: 1px solid rgba(255,215,0,.18);
}
header::after {
  content: "";
  position: absolute; left: 0; bottom: 0; width: 100%; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,215,0,.45), transparent);
  opacity: 0; transition: .35s;
}
header.scrolled::after { opacity: 1; }

.logo {
  width: 138px;
  max-height: 88px;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(255,215,0,.15));
}
.logo img {
  width: 130px;
  display: block;
  position: relative;
  top: 4px;
}

.navbar {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}
.nav-menu {
  display: flex;
  align-items: center;
  gap: 14px;
  height: 100%;
}
.nav-menu a {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 42px;
  padding: 0 18px;
  border-radius: 20px;
  color: #e8e8e8;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: .3px;
  border: 1px solid transparent;
  transition: .25s ease;
}
.nav-menu a:hover {
  background: #202020;
  border-color: rgba(255,215,0,.25);
  color: #FFD700;
  transform: translateY(-2px);
}
.nav-menu a.active {
  background: linear-gradient(180deg, #FFD700, #d9a500);
  color: #111;
  border: 1px solid #FFD700;
  box-shadow:
    0 0 15px rgba(255,215,0,.35),
    inset 0 1px 0 rgba(255,255,255,.3);
}

.member-area {
  display: flex;
  align-items: center;
  justify-self: end;
  gap: 12px;
  height: 100%;
}

.balance-card {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 44px;
  padding: 0 6px 0 14px;
  background: rgba(255,255,255,.05);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 12px;
  transition: .25s;
}
.balance-card:hover {
  border-color: rgba(255,215,0,.35);
  box-shadow: 0 0 18px rgba(255,215,0,.12);
}
.balance-info {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}
.balance-info .label {
  font-size: 9px;
  color: #888;
  letter-spacing: 1px;
  text-transform: uppercase;
}
.balance-info .amount {
  font-size: 14px;
  font-weight: 700;
  color: #FFD700;
}

.btn-deposit {
  height: 34px;
  padding: 0 14px;
  border: none;
  border-radius: 9px;
  background: #FFD700;
  color: #111;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: .2s;
}
.btn-deposit:hover {
  background: #ffe24d;
  transform: translateY(-1px);
}

.user-dropdown { position: relative; }
.user-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  background: #141418;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 40px;
  padding: 5px 16px 5px 5px;
  transition: all .25s ease;
}
.user-btn:hover {
  background: rgba(255,255,255,.08);
  border-color: rgba(255,215,0,.3);
}
.avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(145deg, #FFD700, #e6a800);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #111;
  font-size: 14px;
  box-shadow: 0 0 0 2px rgba(255,215,0,.2);
  background-size: cover;
  background-position: center;
}
.user-meta { line-height: 1.25; }
.user-meta .name { font-size: 13px; font-weight: 600; }
.user-meta .level { font-size: 11px; color: var(--gold); font-weight: 500; }

.dropdown-menu {
  position: absolute;
  top: 56px;
  right: 0;
  width: 230px;
  background: #141418;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(0,0,0,.55);
  z-index: 100;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-12px) scale(.96);
  transition: opacity .25s ease, transform .25s ease, visibility .25s;
}
.dropdown-menu.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}
.dropdown-menu a {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  color: #e0e0e8;
  text-decoration: none;
  font-size: 13.5px;
  border-bottom: 1px solid rgba(255,255,255,.04);
  overflow: hidden;
  transition: color .25s, padding-left .25s, background .25s;
}
.dropdown-menu a svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
  opacity: .9;
}
.dropdown-menu a::before {
  content: "";
  position: absolute;
  left: 0; top: 0;
  width: 3px; height: 100%;
  background: #FFD700;
  transform: scaleY(0);
  transition: transform .25s;
}
.dropdown-menu a:last-child { border: none; color: var(--danger); }
.dropdown-menu a:last-child:hover {
  background: rgba(255,59,92,.08);
  color: #ff4a67;
}
.dropdown-menu a:last-child::before { background: #ff3b5c; }
.dropdown-menu a:hover {
  background: rgba(255,215,0,.06);
  color: #FFD700;
  padding-left: 28px;
}
.dropdown-menu a:hover svg { opacity: 1; }
.dropdown-menu a:hover::before { transform: scaleY(1); }

.layout {
  display: grid;
  grid-template-columns: 210px 1fr;
  min-height: calc(100vh - 74px);
  align-items: start;
}

.side-col {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 10px 16px;
  background: transparent;
  border-right: 1px solid var(--line);
  position: sticky;
  top: 74px;
  align-self: start;
  height: calc(100vh - 74px);
  max-height: calc(100vh - 74px);
  overflow: hidden;
  z-index: 20;
}

.sidebar {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 10px 6px 10px 8px;
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 14px;
}
.sidebar::-webkit-scrollbar,
.search-result::-webkit-scrollbar,
.history-body::-webkit-scrollbar,
.bonus-body::-webkit-scrollbar {
  width: 8px;
}
.sidebar::-webkit-scrollbar-track,
.search-result::-webkit-scrollbar-track,
.history-body::-webkit-scrollbar-track,
.bonus-body::-webkit-scrollbar-track {
  background: transparent;
}
.sidebar::-webkit-scrollbar-thumb,
.search-result::-webkit-scrollbar-thumb,
.history-body::-webkit-scrollbar-thumb,
.bonus-body::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #888888, #444444);
  border-radius: 20px;
  border: 2px solid transparent;
  background-clip: padding-box;
}
.sidebar::-webkit-scrollbar-thumb:hover,
.search-result::-webkit-scrollbar-thumb:hover,
.history-body::-webkit-scrollbar-thumb:hover,
.bonus-body::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #aaaaaa, #666666);
}
.sidebar,
.search-result,
.history-body,
.bonus-body {
  scrollbar-width: thin;
  scrollbar-color: #aaaaaa transparent;
}
.side-nav { list-style: none; }
.side-nav a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 11px;
  border-radius: 10px;
  color: #9a9aa4;
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 500;
  margin-bottom: 2px;
  transition: .22s;
}
.side-nav a svg { width: 16px; height: 16px; flex-shrink: 0; opacity: .8; }
.side-nav a:hover { background: rgba(255,255,255,.04); color: #eee; }
.side-nav a.active {
  background: linear-gradient(90deg, rgba(255,215,0,.16), rgba(255,215,0,.04));
  color: var(--gold);
  border: 1px solid rgba(255,215,0,.22);
}
.side-nav a.active svg { opacity: 1; }

.side-divider {
  height: 1px;
  margin: 10px 4px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.1), transparent);
}
.side-label {
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  color: #555;
  padding: 0 11px;
  margin-bottom: 6px;
}

.provider-list { list-style: none; }
.provider-list a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 11px;
  border-radius: 9px;
  color: #8e8e98;
  text-decoration: none;
  font-size: 12px;
  transition: .2s;
}
.provider-list a:hover { background: rgba(255,255,255,.04); color: #eee; }
.provider-list a.active { color: var(--gold); background: rgba(255,215,0,.08); }
.provider-list .prov-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
}
.provider-list .prov-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  object-fit: contain;
  opacity: .8;
  filter: grayscale(1) brightness(1.35);
}
.provider-list .prov-icon.white {
  filter: brightness(0) invert(1);
  opacity: .85;
}
.provider-list a:hover .prov-icon {
  opacity: 1;
  filter: grayscale(0) brightness(1);
}
.provider-list a:hover .prov-icon.white,
.provider-list a.active .prov-icon.white {
  filter: brightness(0) invert(1);
  opacity: 1;
}
.provider-list a.active .prov-icon {
  opacity: 1;
  filter: none;
}
.provider-list a.active .prov-icon.white {
  filter: brightness(0) invert(1);
}
.provider-list .prov-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.provider-list .cnt {
  font-size: 9.5px;
  color: #666;
  background: rgba(255,255,255,.05);
  padding: 2px 7px;
  border-radius: 999px;
  font-weight: 600;
  flex-shrink: 0;
  margin-left: auto;
}
.provider-list a.active .cnt {
  background: rgba(255,215,0,.15);
  color: var(--gold);
}

.bonus-side {
  flex: 0 0 auto;
  padding: 10px 10px 11px;
  border-radius: 12px;
  background: linear-gradient(160deg, rgba(255,215,0,.14) 0%, rgba(255,180,0,.04) 40%, rgba(18,18,22,.98) 100%);
  border: 1px solid rgba(255,215,0,.22);
  box-shadow: 0 4px 14px rgba(0,0,0,.25), inset 0 1px 0 rgba(255,255,255,.05);
  text-align: center;
  position: relative;
  overflow: hidden;
}
.bonus-side::before {
  content: "";
  position: absolute;
  width: 56px;
  height: 56px;
  right: -14px;
  top: -16px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,215,0,.18), transparent 70%);
  pointer-events: none;
}
.bonus-side-icon {
  width: 30px;
  height: 30px;
  margin: 0 auto 6px;
  border-radius: 9px;
  background: linear-gradient(145deg, #FFE566, #FFD700 45%, #E0A800);
  color: #111;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 10px rgba(255,215,0,.25);
  position: relative;
  z-index: 1;
}
.bonus-side-icon svg { width: 15px; height: 15px; }
.bonus-side h4 {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 1.1px;
  color: var(--gold);
  margin-bottom: 2px;
  position: relative;
  z-index: 1;
}
.bonus-side p {
  font-size: 10px;
  color: #8a8a96;
  margin-bottom: 8px;
  line-height: 1.3;
  position: relative;
  z-index: 1;
}
.bonus-side button {
  width: 100%;
  height: 28px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #FFE566, #FFD700 50%, #E8B000);
  color: #111;
  font-size: 10.5px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(255,215,0,.22);
  position: relative;
  z-index: 1;
  transition: .2s;
}
.bonus-side button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255,215,0,.32);
}

.main { padding: 22px 26px 50px; min-width: 0; }

.banner-slider {
  position: relative;
  border-radius: 22px;
  overflow: hidden;
  margin-bottom: 22px;
  width: 100%;
  aspect-ratio: 16 / 9;
  max-height: 245px;
  border: 1px solid rgba(255,215,0,.12);
  box-shadow: 0 12px 36px rgba(0,0,0,.35);
}
.banner-slide {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity .55s;
}
.banner-slide.active {
  opacity: 1;
  z-index: 1;
}
.banner-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  display: block;
}
.banner-slide::after {
  content: ""; position: absolute; inset: 0;
  background: linear-gradient(90deg, rgba(0,0,0,.8) 0%, rgba(0,0,0,.3) 50%, rgba(0,0,0,.1));
}
.banner-content {
  position: absolute;
  left: 70px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  max-width: 400px;
}
.banner-content span {
  color: var(--gold); font-size: 11px; font-weight: 700;
  letter-spacing: 2px; text-transform: uppercase;
}
.banner-content h2 { font-size: 28px; font-weight: 700; line-height: 1.15; margin: 8px 0 10px; }
.banner-content p { color: #c8c8d0; font-size: 13px; margin-bottom: 16px; }
.banner-content a {
  display: inline-flex; align-items: center; height: 40px; padding: 0 22px;
  border-radius: 999px; background: linear-gradient(135deg, #FFD700, #e6b800);
  color: #111; font-size: 13px; font-weight: 700; text-decoration: none;
  box-shadow: 0 4px 16px rgba(255,215,0,.3); transition: .2s;
}
.banner-content a:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(255,215,0,.4); }

.banner-nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 38px; height: 38px; border-radius: 50%;
  background: rgba(0,0,0,.45); border: 1px solid rgba(255,255,255,.12);
  color: #fff; display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 3; font-size: 20px; transition: .2s;
}
.banner-nav:hover { background: rgba(255,215,0,.2); border-color: rgba(255,215,0,.4); color: var(--gold); }
.banner-nav.prev { left: 14px; }
.banner-nav.next { right: 14px; }

.banner-dots {
  position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 7px; z-index: 3;
}
.banner-dots span {
  width: 7px; height: 7px; border-radius: 50%; background: #555; cursor: pointer; transition: .25s;
}
.banner-dots span.active { width: 20px; border-radius: 10px; background: var(--gold); }

.toolbar-wrap {
  margin-bottom: 16px;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 7px;
}

.filter-chip {
  min-width: 62px;
  padding: 7px 9px 6px;
  border-radius: 16px;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  background: rgba(255,255,255,.03);
  border: 1px solid var(--line);
  color: #9a9aa2;
  font-size: 10.5px;
  font-weight: 500;
  text-decoration: none;
  transition: background .25s ease, border-color .25s ease, color .25s ease, box-shadow .25s ease, transform .25s cubic-bezier(.22,.7,.3,1);
  text-align: center;
  line-height: 1.15;
}
.filter-chip svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  opacity: .75;
}
.filter-chip:hover {
  border-color: rgba(255,215,0,.3);
  color: var(--gold);
  background: rgba(255,215,0,.06);
}
.filter-chip:hover svg { opacity: 1; }
.filter-chip.active {
  background: linear-gradient(145deg, #FFD700, #e6b800);
  color: #111;
  border-color: transparent;
  font-weight: 600;
  box-shadow: 0 3px 12px rgba(255,215,0,.22);
  transform: translateY(-1px);
}
.filter-chip.active svg {
  opacity: 1;
  stroke: #111;
}
.filter-chip:active {
  transform: scale(.96);
}

#gamesPanel {
  transition: opacity .28s ease, transform .28s cubic-bezier(.22,.7,.3,1);
  will-change: opacity, transform;
}
#gamesPanel.is-loading {
  opacity: .28;
  transform: none;
  pointer-events: none;
}
#gamesPanel.is-loading .games-grid .game-card {
  animation: none;
}
.games-grid.anim-in .game-card {
  animation: gameCardIn .42s cubic-bezier(.22,.7,.3,1) backwards;
}
@keyframes gameCardIn {
  from {
    opacity: 0;
    transform: translateY(16px) scale(.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.section-label {
  transition: opacity .25s ease;
}
.side-nav a,
.provider-list a {
  transition: background .22s ease, color .22s ease, border-color .22s ease, transform .2s ease;
}

.toolbar-right {
  display: flex;
  gap: 7px;
  align-items: center;
  flex-shrink: 0;
  margin-left: auto;
}

.search-inline input {
  height: 36px;
  width: 138px;
  padding: 0 12px;
  background: rgba(0,0,0,.35);
  border: 1px solid var(--line);
  border-radius: 16px;
  color: #fff;
  font-size: 12px;
  outline: none;
  transition: .2s;
}
.search-inline input:focus {
  border-color: rgba(255,215,0,.4);
  box-shadow: 0 0 0 3px rgba(255,215,0,.08);
  width: 155px;
}
.search-inline input::placeholder { color: #666; }

.btn-filter {
  height: 36px;
  padding: 0 12px;
  border-radius: 16px;
  background: rgba(255,255,255,.03);
  border: 1px solid var(--line);
  color: #9a9aa2;
  font-size: 11.5px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: .2s;
  white-space: nowrap;
}
.btn-filter svg {
  width: 13px;
  height: 13px;
  opacity: .8;
}
.btn-filter:hover {
  border-color: rgba(255,215,0,.3);
  color: var(--gold);
}
.btn-filter:hover svg { opacity: 1; }

.sort-wrap {
  position: relative;
  min-width: 130px;
}
.sort-btn {
  height: 36px;
  width: 100%;
  padding: 0 30px 0 12px;
  background: rgba(0,0,0,.35);
  border: 1px solid var(--line);
  border-radius: 16px;
  color: #ccc;
  font-size: 11.5px;
  font-family: inherit;
  font-weight: 500;
  outline: none;
  cursor: pointer;
  text-align: left;
  position: relative;
  transition: .2s;
}
.sort-btn::after {
  content: "";
  position: absolute;
  right: 11px;
  top: 50%;
  width: 10px;
  height: 10px;
  transform: translateY(-50%);
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat center;
  transition: transform .2s;
}
.sort-wrap.open .sort-btn::after {
  transform: translateY(-50%) rotate(180deg);
}
.sort-btn:hover,
.sort-wrap.open .sort-btn {
  border-color: rgba(255,215,0,.35);
  color: #eee;
  background: rgba(255,215,0,.06);
}
.sort-menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  min-width: 100%;
  background: #141418;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 14px;
  padding: 6px;
  box-shadow: 0 16px 40px rgba(0,0,0,.55);
  z-index: 50;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-6px) scale(.98);
  transition: .2s ease;
  overflow: hidden;
}
.sort-wrap.open .sort-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}
.sort-option {
  display: block;
  width: 100%;
  padding: 10px 12px;
  border: none;
  border-radius: 10px;
  background: transparent;
  color: #c8c8d0;
  font-size: 12px;
  font-family: inherit;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: .15s;
}
.sort-option:hover {
  background: rgba(255,215,0,.08);
  color: #FFD700;
}
.sort-option.active {
  background: rgba(255,215,0,.12);
  color: #FFD700;
  font-weight: 600;
}

.section-label {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;
}
.section-label h2 {
  font-size: 15px; font-weight: 700; letter-spacing: .3px;
  text-transform: uppercase; color: #e0e0e8;
  display: flex; align-items: center; gap: 8px;
}
.section-label h2::before {
  content: ""; width: 3px; height: 16px; border-radius: 2px;
  background: linear-gradient(180deg, #FFD700, #d9a500);
}
.section-label .meta { font-size: 12.5px; color: #666; }
.section-label .meta strong { color: var(--gold); }

.games-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
}
.game-card {
  background: #181818;
  border: 1px solid rgba(255,255,255,.05);
  border-radius: 16px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: .35s ease;
  display: flex;
  flex-direction: column;
  position: relative;
}
.game-card:hover {
  transform: translateY(-8px) scale(1.02);
  border-color: rgba(255,215,0,.35);
  box-shadow:
    0 18px 40px rgba(0,0,0,.45),
    0 0 25px rgba(255,215,0,.12);
}

.game-thumb {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  background: #1a1a1e;
}
.game-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  filter: brightness(.82) saturate(1.05);
  transition: transform .5s ease, filter .5s ease;
}
.game-card:hover .game-thumb img {
  transform: scale(1.12);
  filter: brightness(.45) saturate(.9);
}

/* ===== FAVORITE ICON ===== */
.favorite {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 5;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,.1);
    transition: background .2s, transform .2s, border-color .2s, box-shadow .2s;
}
.favorite svg,
.favorite .ico {
    width: 14px;
    height: 14px;
    display: block;
    pointer-events: none;
}
.favorite:hover {
    background: rgba(0,0,0,.7);
    border-color: rgba(255,59,92,.45);
    transform: scale(1.08);
}
.favorite.active {
    background: rgba(255,59,92,.2);
    border-color: rgba(255,59,92,.5);
    box-shadow: 0 0 10px rgba(255,59,92,.25);
}
.favorite.active:hover {
    background: rgba(255,59,92,.3);
}

.game-thumb::before {
  content: "";
  position: absolute;
  top: 0;
  left: -120%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    120deg,
    transparent,
    rgba(255,255,255,.18),
    transparent
  );
  transform: skewX(-25deg);
  transition: left .7s ease;
  z-index: 3;
  pointer-events: none;
}
.game-card:hover .game-thumb::before {
  left: 140%;
}

.game-thumb::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0,0,0,.55) 0%,
    rgba(0,0,0,.15) 45%,
    rgba(0,0,0,.05) 100%
  );
  opacity: .7;
  transition: .35s ease;
  z-index: 2;
  pointer-events: none;
}
.game-card:hover .game-thumb::after {
  background: linear-gradient(
    to top,
    rgba(0,0,0,.75),
    rgba(0,0,0,.4)
  );
}

.play-overlay {
  position: absolute;
  inset: 0;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity .3s ease;
  pointer-events: none;
}
.game-card:hover .play-overlay { opacity: 1; }
.play-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 86px;
  height: 34px;
  padding: 0 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, #FFD700, #f0c000);
  color: #111;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .5px;
  box-shadow:
    0 6px 18px rgba(255,215,0,.4),
    inset 0 1px 0 rgba(255,255,255,.4);
  transform: scale(.86) translateY(8px);
  transition: transform .3s cubic-bezier(.34,1.4,.64,1);
}
.play-btn svg { width: 12px; height: 12px; flex-shrink: 0; }
.game-card:hover .play-btn { transform: scale(1) translateY(0); }

.badge {
  position: absolute; top: 8px; left: 8px; z-index: 5;
  padding: 3px 8px; border-radius: 7px;
  font-size: 8.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase;
  backdrop-filter: blur(6px);
}
.badge.hot { background: linear-gradient(135deg, #ff3b5c, #ff1744); color: #fff; }
.badge.new { background: linear-gradient(135deg, #00e676, #00c853); color: #06210f; }
.badge.cold { background: linear-gradient(135deg, #42a5f5, #1e88e5); color: #fff; }
.badge.normal { background: linear-gradient(135deg, #7e57c2, #5e35b1); color: #fff; }

.game-body { padding: 9px 10px 11px; }
.game-body h3 {
  font-size: 12px; font-weight: 600; color: #eee;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;
}
.game-body .prov { font-size: 10px; color: #777; margin-bottom: 5px; letter-spacing: .3px; }
.game-body .rtp-val { font-size: 11px; font-weight: 700; }
.game-body .rtp-bar {
  margin-top: 5px; height: 3px; background: #2a2a30; border-radius: 10px; overflow: hidden;
}
.game-body .rtp-fill { height: 100%; width: 0; border-radius: 10px; transition: width 1s ease; }

.empty {
  grid-column: 1 / -1; text-align: center; padding: 56px 20px;
  color: #666; border: 1px dashed var(--line); border-radius: 18px;
}
.empty a { color: var(--gold); text-decoration: none; font-weight: 600; }
.empty a:hover { text-decoration: underline; }

.load-more-wrap { display: flex; justify-content: center; margin-top: 32px; }
.btn-load-more {
  height: 46px; padding: 0 36px; border-radius: 14px;
  background: rgba(255,255,255,.03); border: 1px solid rgba(255,215,0,.25);
  color: var(--gold); font-size: 13.5px; font-weight: 600;
  cursor: pointer; transition: .25s; text-decoration: none;
  display: inline-flex; align-items: center; gap: 8px;
}
.btn-load-more:hover {
  background: rgba(255,215,0,.1);
  box-shadow: 0 6px 20px rgba(255,215,0,.15);
  transform: translateY(-2px);
}

.logout-modal {
  position: fixed; inset: 0; display: flex; justify-content: center; align-items: center;
  background: rgba(0,0,0,.5); backdrop-filter: blur(10px);
  opacity: 0; visibility: hidden; transition: .25s; z-index: 2000;
}
.logout-modal.show { opacity: 1; visibility: visible; }
.logout-box {
  width: 360px; max-width: 92%; background: #17171c;
  border: 1px solid rgba(255,215,0,.22); border-radius: 20px; padding: 28px; text-align: center;
}
.logout-box h3 { color: var(--gold); margin-bottom: 10px; font-size: 18px; }
.logout-box p { color: #aaa; margin-bottom: 22px; font-size: 14px; }
.logout-actions { display: flex; gap: 12px; }
.logout-actions button {
  flex: 1; height: 42px; border: none; border-radius: 11px;
  cursor: pointer; font-weight: 600; font-size: 13.5px; transition: .2s;
}
.logout-cancel { background: #333; color: #fff; }
.logout-cancel:hover { background: #444; }
.logout-confirm { background: var(--danger); color: #fff; }
.logout-confirm:hover { background: #ff2147; }

footer {
  text-align: center;
  padding: 24px;
  color: #555;
  font-size: 12.5px;
  border-top: 1px solid var(--line);
  background: #0c0c0e;
}

@media (max-width: 1400px) {
  .games-grid { grid-template-columns: repeat(5, 1fr); }
}
@media (max-width: 1200px) {
  .games-grid { grid-template-columns: repeat(4, 1fr); gap: 11px; }
}
@media (max-width: 960px) {
  header { padding: 0 16px; }
  .nav-menu { display: none; }
  .layout { grid-template-columns: 1fr; }
  .side-col {
    position: relative; top: auto; max-height: none; height: auto;
    border-right: none; border-bottom: 1px solid var(--line);
    flex-direction: column; gap: 12px; padding: 14px;
  }
  .sidebar { width: 100%; max-height: none; }
  .side-nav { display: flex; flex-wrap: wrap; gap: 4px; }
  .side-nav a { margin: 0; }
  .side-label, .provider-list, .side-divider { display: none; }
  .bonus-side { width: 100%; }
.banner-slider {
  max-height: 180px;
  border-radius: 16px;
}
  .banner-content { left: 36px; }
  .banner-content h2 { font-size: 22px; }
  .games-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .balance-card { padding: 6px 6px 6px 14px; }
  .balance-info .amount { font-size: 14.5px; }
  .toolbar-right {
  width: 100%;
  margin-left: 0;
  margin-top: 6px;
  flex-wrap: wrap;
}
.search-inline { flex: 1; min-width: 120px; }
.search-inline input,
.search-inline input:focus { width: 100%; }
  .filter-chip {
    min-width: 64px;
    padding: 8px 10px;
  }
}
@media (max-width: 600px) {
  .games-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .main { padding: 16px; }
}

/* ===== NAVBAR SEARCH (icon only, tanpa outline) ===== */
.nav-menu a.search-btn {
  width: 42px;
  height: 42px;
  padding: 0;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
  border: 1px solid transparent;
  color: #aaa;
  box-shadow: none;
  outline: none;
}
.nav-menu a.search-btn svg {
  width: 18px;
  height: 18px;
  opacity: 1;
}
.nav-menu a.search-btn:hover {
  background: #FFD700;
  border-color: transparent;
  color: #111;
  transform: translateY(-2px);
  box-shadow: none;
}
.nav-menu a.search-btn:hover svg {
  stroke: #111;
}
.nav-menu a.search-btn.active,
.nav-menu a.search-btn:focus {
  background: rgba(255,255,255,.05);
  color: #aaa;
  border: 1px solid transparent;
  box-shadow: none;
  outline: none;
}

/* ===== SEARCH MODAL ===== */
.search-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: flex;
  justify-content: center;
  align-items: center;
  padding-top: 25px;
  opacity: 0;
  visibility: hidden;
  transition: .25s;
  z-index: 999999;
}
.search-modal.show {
  opacity: 1;
  visibility: visible;
}
.search-box {
  width: 900px;
  max-width: 95vw;
  height: 75vh;
  max-height: 850px;
  display: flex;
  flex-direction: column;
  background: #16161c;
  border-radius: 22px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,.08);
}
.search-result {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}
.search-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
}
.search-card {
  background: #1a1a22;
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  transition: opacity .35s, transform .35s, filter .35s;
  opacity: 0;
  transform: translateY(20px) scale(.96);
  filter: blur(6px);
}
.search-card.show {
  opacity: 1;
  transform: translateY(0) scale(1);
  filter: blur(0);
}
.search-card:hover {
  transform: translateY(-5px) scale(1.02);
  border-color: #FFD700;
  box-shadow: 0 10px 25px rgba(255,215,0,.18);
}
.search-card img {
  width: 100%;
  height: 140px;
  object-fit: cover;
  display: block;
}
.search-card-info { padding: 12px; }
.search-card-info h4 {
  color: #fff;
  font-size: 14px;
  margin-bottom: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.search-card-provider { color: #999; font-size: 11px; }
.search-card-rtp {
  margin-top: 6px;
  color: #2dff6b;
  font-size: 12px;
  font-weight: 600;
}
.search-header {
  display: flex;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.search-header input {
  flex: 1;
  height: 58px;
  background: none;
  border: none;
  padding: 0 20px;
  color: white;
  font-size: 16px;
  font-family: inherit;
  outline: none;
}
.search-header input::placeholder { color: #666; }
.search-header button {
  width: 58px;
  height: 58px;
  border: none;
  background: none;
  color: white;
  cursor: pointer;
  font-size: 22px;
}
.search-empty {
  padding: 50px;
  text-align: center;
  color: #777;
}
.search-empty h3 { color: #aaa; margin-bottom: 8px; font-size: 16px; }
.search-empty p { font-size: 13px; }
.search-hero {
  width: 100%;
  padding: 95px 30px;
  text-align: center;
  background:
    radial-gradient(circle at top, rgba(255,215,0,.12), transparent 70%),
    linear-gradient(180deg, #1a1a22, #141419);
  border-bottom: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  box-sizing: border-box;
}
.search-hero-icon {
  width: 55px;
  height: 55px;
  margin: 0 auto 12px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #FFD700, #e6b800);
  color: #111;
}
.search-hero-icon svg { width: 26px; height: 26px; }
.search-hero h2 {
  color: #fff;
  font-size: 21px;
  margin-bottom: 6px;
}
.search-hero p {
  color: #999;
  font-size: 13px;
  margin-bottom: 22px;
}
.hero-tags {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
}
.hero-pill {
  padding: 10px 16px;
  border-radius: 999px;
  background: #23232d;
  color: #ddd;
  font-size: 13px;
  cursor: pointer;
  transition: .25s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.hero-pill svg { width: 16px; height: 16px; }
.hero-pill.active {
  background: #FFD700;
  color: #111;
  font-weight: 700;
}
.hero-pill:hover {
  background: #FFD700;
  color: #111;
}
.search-back,
.search-close {
  width: 58px;
  height: 58px;
  border: none;
  background: none;
  color: white;
  cursor: pointer;
  font-size: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: .25s;
  flex-shrink: 0;
}
.search-back:hover,
.search-close:hover { color: #FFD700; }
.search-back {
  opacity: 0;
  pointer-events: none;
}
.search-modal.searching .search-back {
  opacity: 1;
  pointer-events: auto;
}
.search-close svg { width: 20px; height: 20px; }
@media (max-width: 600px) {
  .search-box { height: 85vh; border-radius: 16px; }
  .search-hero { padding: 48px 16px; }
  .search-hero h2 { font-size: 18px; }
  .search-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; }
  .search-card img { height: 110px; }
}

/* ========== MODAL / DEPOSIT / HISTORY ========== */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  backdrop-filter: blur(8px);
  z-index: 99999;
  align-items: center;
  justify-content: center;
}
.modal-content {
  width: 420px;
  max-width: 90%;
  background: #15151c;
  border: 1px solid rgba(255,215,0,.25);
  border-radius: 24px;
  padding: 30px;
  box-shadow: 0 25px 70px rgba(0,0,0,.6);
  animation: modalShow .25s ease;
}
@keyframes modalShow {
  from { opacity: 0; transform: scale(.9); }
  to { opacity: 1; transform: scale(1); }
}
.btn-outline {
  padding: 11px 22px; border-radius: 14px;
  border: 1px solid var(--gold-border); background: transparent;
  color: var(--gold); font-weight: 600; font-size: 13.5px;
  cursor: pointer; transition: all .25s; text-align: center;
}
.btn-outline:hover { background: rgba(255,215,0,.1); transform: translateY(-2px); }
.btn-primary {
  padding: 11px 22px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, #FFD700, #f0c000);
  color: #111; font-weight: 700; font-size: 13.5px;
  cursor: pointer; transition: all .25s;
  box-shadow: 0 4px 18px rgba(255,215,0,.25); text-align: center;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(255,215,0,.4); }

.deposit-modal .modal-content {
    width: 320px;
    max-width: 88%;
    padding: 18px 16px 16px;
    position: relative;
    border-radius: 16px;
}
.deposit-modal .close {
    position: absolute;
    top: 12px;
    right: 14px;
    margin-left: 0;
    font-size: 22px;
    line-height: 1;
    color: #888;
    cursor: pointer;
    transition: .2s;
    z-index: 2;
}
.deposit-modal .close:hover {
    color: #FFD700;
}
.deposit-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.deposit-title h3 {
    color: #FFD700;
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}
.deposit-title svg {
    width: 20px;
    height: 20px;
    color: #FFD700;
    stroke: currentColor;
    fill: none;
    flex-shrink: 0;
}
.deposit-sub {
    color: #8a8a96;
    font-size: 10.5px;
    margin-bottom: 12px;
}
.deposit-amount-label {
    font-size: 10px;
    color: var(--muted);
    font-weight: 500;
    margin-bottom: 5px;
}
.deposit-amount-input {
    position: relative;
    margin-bottom: 8px;
}
.deposit-amount-input .prefix {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #FFD700;
    font-weight: 700;
    font-size: 12px;
    pointer-events: none;
}
.deposit-amount-input input {
    width: 100%;
    height: 42px;
    padding: 0 12px 0 40px;
    background: #0e0e12;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 11px;
    color: var(--text);
    font-size: 15px;
    font-weight: 700;
    outline: none;
    transition: all .25s;
}
.deposit-amount-input input:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 3px rgba(255,215,0,.08);
}
.deposit-presets {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
    margin-bottom: 12px;
}
.deposit-preset {
    height: 32px;
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.03);
    border-radius: 8px;
    color: #c8c8d0;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.deposit-preset:hover {
    border-color: rgba(255,215,0,.35);
    color: #FFD700;
    background: rgba(255,215,0,.06);
}
.deposit-preset.active {
    border-color: #FFD700;
    background: rgba(255,215,0,.12);
    color: #FFD700;
    box-shadow: 0 0 14px rgba(255,215,0,.15);
}
.deposit-method-label {
    font-size: 10px;
    color: var(--muted);
    font-weight: 500;
    margin-bottom: 6px;
}
.deposit-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-bottom: 12px;
}
.deposit-method {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 9px;
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.025);
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s;
    user-select: none;
}
.deposit-method:hover {
    border-color: rgba(255,215,0,.3);
    background: rgba(255,215,0,.05);
}
.deposit-method.active {
    border-color: #FFD700;
    background: rgba(255,215,0,.1);
    box-shadow: 0 0 16px rgba(255,215,0,.12);
}
.deposit-method input {
    display: none;
}
.deposit-method-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(255,215,0,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #FFD700;
}
.deposit-method-icon svg {
    width: 15px;
    height: 15px;
    display: block;
    stroke: currentColor;
    fill: none;
    flex-shrink: 0;
}
.deposit-method.active .deposit-method-icon {
    background: rgba(255,215,0,.2);
    color: #FFD700;
}
.deposit-method-text {
    min-width: 0;
}
.deposit-method-text strong {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #eee;
    margin-bottom: 1px;
}
.deposit-method-text span {
    font-size: 8px;
    color: #888;
}
.deposit-method.active .deposit-method-text strong {
    color: #FFD700;
}
.deposit-actions {
    display: flex;
    gap: 6px;
    margin-top: 4px;
}
.deposit-actions .btn-primary,
.deposit-actions .btn-outline {
    flex: 1;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    border-radius: 9px;
}
.deposit-min-note {
    text-align: center;
    font-size: 8.5px;
    color: #666;
    margin-top: 8px;
}
@media (max-width: 480px) {
    .deposit-modal .modal-content {
        width: 320px;
        max-width: 88%;
        padding: 18px 16px 16px;
    }
    .deposit-presets {
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    .deposit-preset {
        height: 32px;
        font-size: 10px;
    }
    .deposit-methods {
        grid-template-columns: 1fr;
        gap: 6px;
    }
}

.history-modal .modal-content {
  width: 560px; max-width: 94%; max-height: 85vh; padding: 0;
  overflow: hidden; display: flex; flex-direction: column; position: relative;
}
.bonus-modal .modal-content {
  width: 320px;
  max-width: 88%;
  padding: 18px 16px 16px;
  position: relative;
  border-radius: 16px;
}
.history-modal .modal-header,
.bonus-modal .modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px 16px; border-bottom: 1px solid rgba(255,255,255,.06); flex-shrink: 0;
}
.history-modal .modal-header h3,
.bonus-modal .modal-header h3 {
  display: flex; align-items: center; gap: 10px;
  color: #FFD700; font-size: 17px; font-weight: 700; margin: 0;
}
.history-modal .modal-header h3 svg,
.bonus-modal .modal-header h3 svg {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  stroke: #FFD700;
  color: #FFD700;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
  display: block;
}
.deposit-title svg {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  stroke: #FFD700;
  color: #FFD700;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
  display: block;
}
.history-modal .close,
.bonus-modal .close {
  position: static; margin: 0; font-size: 24px; line-height: 1; color: #888;
  cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center;
  justify-content: center; border-radius: 10px; transition: .2s;
}
.history-modal .close:hover,
.bonus-modal .close:hover { color: #FFD700; background: rgba(255,215,0,.08); }
.history-tabs {
  display: flex; gap: 6px; padding: 12px 20px;
  border-bottom: 1px solid rgba(255,255,255,.04); flex-shrink: 0; overflow-x: auto;
}
.history-tab {
  padding: 8px 14px; border-radius: 10px; border: none; background: transparent;
  color: #8a8a96; font-size: 12.5px; font-weight: 500; cursor: pointer;
  white-space: nowrap; transition: .2s; font-family: inherit;
}
.history-tab:hover { color: #e8e0c0; background: rgba(255,255,255,.04); }
.history-tab.active { background: rgba(255,215,0,.12); color: #FFD700; font-weight: 600; }
.history-body, .bonus-body {
  flex: 1; overflow-y: auto; padding: 16px 20px 22px; min-height: 280px; max-height: 52vh;
}
.history-item {
  display: flex; align-items: center; gap: 14px; padding: 14px 12px;
  border-radius: 14px; border: 1px solid rgba(255,255,255,.05);
  background: rgba(255,255,255,.02); margin-bottom: 10px; transition: .2s;
}
.history-item:hover { border-color: rgba(255,215,0,.2); background: rgba(255,215,0,.04); }
.history-item-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
  background: rgba(255,215,0,.1); color: #FFD700;
  font-size: 18px; font-weight: 700; line-height: 1;
}
.history-item-icon svg {
  width: 18px; height: 18px;
  stroke: currentColor; fill: none;
  stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
  display: block;
}
.history-item-icon.deposit { background: rgba(45,255,107,.1); color: #2dff6b; }
.history-item-icon.withdraw { background: rgba(255,59,92,.1); color: #ff3b5c; }
.history-item-icon.win { background: rgba(45,255,107,.12); color: #2dff6b; }
.history-item-icon.lose { background: rgba(255,59,92,.1); color: #ff3b5c; }
.history-item-info { flex: 1; min-width: 0; }
.history-item-info strong { display: block; font-size: 13.5px; font-weight: 600; color: #eee; margin-bottom: 3px; }
.history-item-info span { font-size: 11.5px; color: #777; }
.history-item-amount { text-align: right; flex-shrink: 0; }
.history-item-amount .val { display: block; font-size: 14px; font-weight: 700; }
.history-item-amount .val.plus { color: #2dff6b; }
.history-item-amount .val.minus { color: #ff3b5c; }
.history-item-amount .status { font-size: 10.5px; color: #888; margin-top: 2px; }
.history-item-amount .status.ok { color: #2dff6b; }
.history-item-amount .status.pending { color: #FFD700; }
.history-item-amount .status.fail { color: #ff3b5c; }
.history-empty { text-align: center; padding: 48px 20px; color: #666; }
.history-empty p { font-size: 13px; margin-top: 6px; }
.bonus-card {
  padding: 16px; border-radius: 16px; border: 1px solid rgba(255,215,0,.14);
  background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(255,215,0,.02));
  margin-bottom: 12px;
}
.bonus-card-top { display: flex; gap: 14px; margin-bottom: 12px; }
.bonus-card-icon {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(255,215,0,.12);
  display: flex; align-items: center; justify-content: center; color: #FFD700; flex-shrink: 0;
  font-size: 20px; line-height: 1;
}
.bonus-card-icon svg {
  width: 20px; height: 20px;
  stroke: currentColor; fill: none;
  stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
  display: block;
}
.bonus-card-info .tag {
  display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 1px;
  color: #FFD700; margin-bottom: 4px;
}
.bonus-card-info h4 { font-size: 14px; color: #eee; margin-bottom: 4px; }
.bonus-card-info p { font-size: 12px; color: #888; }
.bonus-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.bonus-card-meta .expiry { font-size: 11px; color: #777; }
.btn-claim-sm {
  height: 34px; padding: 0 14px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, #FFD700, #e6b800); color: #111;
  font-size: 12px; font-weight: 700; cursor: pointer;
}
.btn-claim-sm:disabled, .btn-claimed {
  background: #333; color: #888; cursor: default;
}
.toast {
  position: fixed; top: 90px; right: 24px; z-index: 100000;
  display: flex; align-items: center; gap: 12px; padding: 14px 18px;
  background: #16161c; border: 1px solid rgba(255,215,0,.2); border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,.45); opacity: 0; transform: translateX(40px);
  pointer-events: none; transition: .3s;
}
.toast.show { opacity: 1; transform: translateX(0); pointer-events: auto; }
.toast-icon {
  width: 36px; height: 36px; border-radius: 10px; background: rgba(45,255,107,.12);
  color: #2dff6b; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.toast-icon.error { background: rgba(255,59,92,.12); color: #ff3b5c; }
.toast-title { font-size: 13px; font-weight: 700; color: #eee; }
.toast-message { font-size: 12px; color: #888; margin-top: 2px; }
@media (max-width: 480px) {
  .deposit-methods { grid-template-columns: 1fr; }
  .deposit-presets { gap: 6px; }
}
</style>
</head>
<body>

<header>
  <div class="logo">
    <a href="member.php"><img src="../assets/logo/rk.png" alt="Royal Knight's"></a>
  </div>

  <nav class="navbar">
    <div class="nav-menu">
      <a href="member.php">Lobby</a>
      <a href="game-list.php" class="active">Games</a>
      <a href="promosi.php?cat=slots">Promosi</a>
      <a href="game-list.php?cat=live">VIP</a>
      <a href="#">Kontak</a>
      <a href="#" class="search-btn" onclick="openSearch(event); return false;" aria-label="Cari game">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
      </a>
    </div>
  </nav>

  <div class="member-area">
    <div class="balance-card">
      <div class="balance-info">
        <div class="label">Saldo</div>
        <div class="amount">Rp <?= number_format($balance, 0, ',', '.') ?></div>
      </div>
      <button class="btn-deposit" type="button" onclick="openDepositModal()">+ Deposit</button>
    </div>

    <div class="user-dropdown">
      <div class="user-btn" onclick="toggleUserMenu()">
        <div class="avatar" <?= $avatar_url ? 'style="background-image:url(\''.htmlspecialchars($avatar_url).'\')"' : '' ?>>
          <?= $avatar_url ? '' : htmlspecialchars($avatar_initials) ?>
        </div>
        <div class="user-meta">
          <div class="name"><?= htmlspecialchars($username) ?></div>
          <div class="level"><?= htmlspecialchars($level) ?></div>
        </div>
      </div>
      <div class="dropdown-menu" id="userMenu">
        <a href="profile.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Profil Saya
        </a>
        <a href="#" onclick="openHistoryModal(event)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Riwayat Transaksi
        </a>
        <a href="#" onclick="openBetHistoryModal(event)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>
          Riwayat Taruhan
        </a>
        <a href="#" onclick="openBonusModal(event)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
          Bonus &amp; Promo
        </a>
        <a href="settings.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Pengaturan
        </a>
        <a href="#" onclick="confirmLogout(event)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Keluar
        </a>
      </div>
    </div>
  </div>
</header>

<div class="layout">

  <div class="side-col">
    <div class="sidebar">
      <ul class="side-nav">
        <li>
          <a href="<?= qs(['side'=>'all','provider'=>'','cat'=>'all','page'=>1]) ?>" class="<?= $side==='all' && $provider==='' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M8 10v4M12 9v6M16 10v4" stroke-linecap="round"/></svg>
            Semua Game
          </a>
        </li>
        <li>
          <a href="<?= qs(['side'=>'popular','provider'=>'','page'=>1]) ?>" class="<?= $side==='popular' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9"/></svg>
            Game Populer
          </a>
        </li>
        <li>
          <a href="<?= qs(['side'=>'rtp','provider'=>'','page'=>1]) ?>" class="<?= $side==='rtp' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            RTP Tertinggi
          </a>
        </li>
        <li>
          <a href="<?= qs(['side'=>'new','provider'=>'','page'=>1]) ?>" class="<?= $side==='new' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l1.5 5.5L19 9l-5.5 1.5L12 16l-1.5-5.5L5 9l5.5-1.5L12 2z"/></svg>
            Baru Rilis
          </a>
        </li>
        <li>
          <a href="<?= qs(['side'=>'fav','provider'=>'','page'=>1]) ?>" class="<?= $side==='fav' ? 'active' : '' ?>" id="favoriteSidebarLink">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            Favorit Saya
            <span class="badge-count" id="favCount" style="margin-left:auto; background:var(--gold); color:#0a090b; font-size:10px; font-weight:700; padding:2px 10px; border-radius:40px;"><?= $fav_count ?></span>
          </a>
        </li>
      </ul>

      <div class="side-divider"></div>
      <div class="side-label">Provider</div>
      <ul class="provider-list">
        <?php foreach (array_slice($providers, 0, 8) as $p):
          $pIcon = providerIcon($p['name'], $providerIcons);
          $pIconWhite = $pIcon && (str_contains($pIcon, 'spribe') || str_contains($pIcon, 'hacksaw'));
        ?>
        <li>
          <a href="<?= qs(['provider'=>$p['name'], 'side'=>'all', 'page'=>1]) ?>" class="<?= $provider === $p['name'] ? 'active' : '' ?>">
            <span class="prov-left">
              <?php if ($pIcon): ?>
              <img class="prov-icon<?= $pIconWhite ? ' white' : '' ?>" src="<?= htmlspecialchars($pIcon) ?>" alt="" width="16" height="16" loading="lazy">
              <?php endif; ?>
              <span class="prov-name"><?= htmlspecialchars($p['name']) ?></span>
            </span>
            <span class="cnt"><?= $p['count'] ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="bonus-side" role="button" tabindex="0" onclick="openBonusModal(event)" style="cursor:pointer">
      <div class="bonus-side-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 12 20 22 4 22 4 12"/>
          <rect x="2" y="7" width="20" height="5"/>
          <line x1="12" y1="22" x2="12" y2="7"/>
          <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
          <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
        </svg>
      </div>
      <h4>BONUS HARIAN</h4>
      <p>Claim bonus setiap hari</p>
      <button type="button" onclick="event.stopPropagation(); openBonusModal(event)">KLAIM SEKARANG</button>
    </div>
  </div>

  <main class="main">
    <div class="banner-slider">
      <?php
      $banners = [
        ['bgm1.png', 'WELCOME BONUS', 'Menang Maxwin Tiada Batas!', 'RTP Tinggi · Gacor · Jackpot Besar'],
        ['bgm2.png', 'SLOT GACOR', 'Putar & Raih Kemenangan', 'Provider terbaik dunia'],
        ['bgm3.png', 'LIVE CASINO', 'Sensasi Live Dealer', 'Blackjack · Baccarat · Roulette'],
        ['bgm4.png', 'PROMO', 'Bonus Reload 100%', 'Min deposit Rp50.000'],
        ['bgm5.png', 'TURNAMEN', 'Turnamen Slot Jutaan', 'Bersaing setiap minggu'],
      ];
      foreach ($banners as $i => $b):
      ?>
      <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>">
        <img src="../assets/banner/<?= htmlspecialchars($b[0]) ?>" alt="">
        <div class="banner-content">
          <span><?= $b[1] ?></span>
          <h2><?= $b[2] ?></h2>
          <p><?= $b[3] ?></p>
          <a href="member.php">Main Sekarang</a>
        </div>
      </div>
      <?php endforeach; ?>
      <button type="button" class="banner-nav prev" onclick="slideBanner(-1)">‹</button>
      <button type="button" class="banner-nav next" onclick="slideBanner(1)">›</button>
      <div class="banner-dots" id="bannerDots">
        <?php for ($i = 0; $i < 5; $i++): ?>
        <span class="<?= $i === 0 ? 'active' : '' ?>" onclick="goBanner(<?= $i ?>)"></span>
        <?php endfor; ?>
      </div>
    </div>

   <form class="toolbar-wrap" method="GET" action="game-list.php" id="filterForm">
  <input type="hidden" name="side" value="<?= htmlspecialchars($side) ?>">
  <?php if ($provider !== ''): ?>
  <input type="hidden" name="provider" value="<?= htmlspecialchars($provider) ?>">
  <?php endif; ?>

  <div class="toolbar">
    <?php foreach ($chips as $k => $item):
      [$lb, $icon] = $item;
    ?>
    <a href="<?= qs(['cat'=>$k, 'page'=>1]) ?>" class="filter-chip <?= $cat === $k ? 'active' : '' ?>">
      <?= $icon ?>
      <?= $lb ?>
    </a>
    <?php endforeach; ?>

    <div class="toolbar-right">
      <div class="search-inline">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari game..." autocomplete="off">
      </div>
      <button type="submit" class="btn-filter">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
        Filter
      </button>
      <?php
        $sortLabels = [
          'newest' => 'Terbaru',
          'rtp' => 'RTP Tertinggi',
          'name' => 'Nama A–Z',
          'provider' => 'Provider',
        ];
        $sortLabel = $sortLabels[$sort] ?? 'Terbaru';
      ?>
      <input type="hidden" name="sort" id="sortInput" value="<?= htmlspecialchars($sort) ?>">
      <div class="sort-wrap" id="sortWrap">
        <button type="button" class="sort-btn" id="sortBtn"><?= htmlspecialchars($sortLabel) ?></button>
        <div class="sort-menu" id="sortMenu">
          <?php foreach ($sortLabels as $val => $lab): ?>
          <button type="button" class="sort-option<?= $sort === $val ? ' active' : '' ?>" data-value="<?= $val ?>"><?= $lab ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</form>

    <div id="gamesPanel">
    <div class="section-label" id="sectionLabel">
      <h2>
        <?php
        if ($provider !== '') echo htmlspecialchars($provider);
        elseif ($side === 'popular') echo 'Game Populer';
        elseif ($side === 'rtp') echo 'RTP Tertinggi';
        elseif ($side === 'new') echo 'Baru Rilis';
        elseif ($side === 'fav') echo 'Favorit Saya';
        elseif ($cat !== 'all') echo $chips[$cat][0] ?? ucfirst($cat);
        else echo 'Semua Game';
        ?>
      </h2>
      <div class="meta">
        <strong><?= number_format(count($games)) ?></strong> ditampilkan · <?= number_format($total_games) ?> dari <?= number_format($total_all) ?> game
      </div>
    </div>

    <div class="games-grid" id="gamesGrid">
      <?php if (empty($games)): ?>
      <div class="empty">
        <?= $side === 'fav' ? 'Belum ada game favorit.' : 'Tidak ada game ditemukan.' ?><br>
        <?php if ($side === 'fav'): ?>
          <span style="font-size:13px;color:#666">Klik icon ❤️ di game untuk menambahkan</span>
        <?php else: ?>
          <a href="game-list.php">Reset filter</a>
        <?php endif; ?>
      </div>
      <?php else: foreach ($games as $g):
        $rc = rtpColor($g['rtp']);
        $bc = in_array($g['badge'], ['hot','new','cold','normal'], true) ? $g['badge'] : 'normal';
      ?>
      <a class="game-card" href="game.php?id=<?= $g['id'] ?>" data-game-id="<?= $g['id'] ?>">
        <div class="game-thumb">
          <div class="badge <?= htmlspecialchars($bc) ?>"><?= strtoupper(htmlspecialchars($g['badge'])) ?></div>
          
          <!-- ===== ICON FAVORITE (SVG) ===== -->
          <div class="favorite<?= in_array((int)$g['id'], $fav_ids, true) ? ' active' : '' ?>" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(<?= (int)$g['id'] ?>, this)">
            <?php if (in_array((int)$g['id'], $fav_ids, true)): ?>
            <svg class="ico heart" viewBox="0 0 24 24" fill="#ff3b5c" stroke="#ff3b5c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php else: ?>
            <svg class="ico heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06-7.78 7.78L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php endif; ?>
          </div>
          
          <img src="<?= htmlspecialchars($g['img']) ?>" alt="<?= htmlspecialchars($g['name']) ?>" loading="lazy">
          <div class="play-overlay">
            <div class="play-btn">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              PLAY
            </div>
          </div>
        </div>
        <div class="game-body">
          <h3><?= htmlspecialchars($g['name']) ?></h3>
          <div class="prov"><?= htmlspecialchars($g['provider']) ?></div>
          <div class="rtp-val" style="color:<?= $rc ?>">RTP <?= number_format($g['rtp'], 2) ?>%</div>
          <div class="rtp-bar">
          <div class="rtp-fill"
            data-w="<?= min((float)$g['rtp'], 100) ?>"
            style="width:0%; background:<?= $rc ?>;">
        </div>
        </div>
        </div>
      </a>
      <?php endforeach; endif; ?>
    </div>

    <div id="loadMoreSlot">
    <?php if ($has_more && $side !== 'fav'): ?>
    <div class="load-more-wrap">
      <a href="<?= qs(['page' => $page + 1]) ?>" class="btn-load-more">
        Muat Lebih Banyak
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
    </div>
    <?php endif; ?>
    </div>
    </div>
  </main>

</div>

<footer>© 2026 Royal Knight's · Bermain secara bertanggung jawab · 18+</footer>

<div class="search-modal" id="searchModal">
  <div class="search-box" role="dialog" aria-modal="true" aria-label="Cari game">
    <div class="search-header">
      <button type="button" class="search-back" onclick="backSearch()" aria-label="Kembali">←</button>
      <input type="text" id="popupSearch" placeholder="Cari permainan..." autocomplete="off">
      <button type="button" class="search-close" onclick="closeSearch()" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="search-result" id="searchResult"></div>
  </div>
</div>

<div id="toast" class="toast">
  <div class="toast-icon" id="toastIcon">✓</div>
  <div class="toast-content">
    <div class="toast-title">Berhasil</div>
    <div class="toast-message"></div>
  </div>
</div>

<!-- DEPOSIT MODAL -->
<div id="depositModal" class="modal deposit-modal">
  <div class="modal-content">
    <span class="close" onclick="closeDepositModal()">&times;</span>
    <div class="deposit-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M12 14h4"/>
      </svg>
      <h3>Deposit Saldo</h3>
    </div>
    <p class="deposit-sub">Isi saldo akun Anda dengan cepat dan aman.</p>
    <form id="depositForm" action="/casino/auth/deposit.php" method="POST" onsubmit="return submitDeposit(event)">
      <div class="deposit-amount-label">Jumlah Deposit</div>
      <div class="deposit-amount-input">
        <span class="prefix">Rp</span>
        <input type="text" id="depositAmount" name="amount" inputmode="numeric" placeholder="0" autocomplete="off" required>
      </div>
      <div class="deposit-presets">
        <button type="button" class="deposit-preset" data-amount="50000">50.000</button>
        <button type="button" class="deposit-preset" data-amount="100000">100.000</button>
        <button type="button" class="deposit-preset" data-amount="250000">250.000</button>
        <button type="button" class="deposit-preset" data-amount="500000">500.000</button>
        <button type="button" class="deposit-preset" data-amount="1000000">1.000.000</button>
        <button type="button" class="deposit-preset" data-amount="2000000">2.000.000</button>
      </div>
      <div class="deposit-method-label">Metode Pembayaran</div>
      <div class="deposit-methods">
        <label class="deposit-method active">
          <input type="radio" name="method" value="bank" checked>
          <div class="deposit-method-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 21h18"/>
              <path d="M3 10h18"/>
              <path d="M5 6l7-3 7 3"/>
              <path d="M4 10v11"/>
              <path d="M20 10v11"/>
              <path d="M8 14v3"/>
              <path d="M12 14v3"/>
              <path d="M16 14v3"/>
            </svg>
          </div>
          <div class="deposit-method-text"><strong>Transfer Bank</strong><span>BCA · Mandiri · BNI</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="ewallet">
          <div class="deposit-method-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="2" width="14" height="20" rx="2"/>
              <line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>
          </div>
          <div class="deposit-method-text"><strong>E-Wallet</strong><span>DANA · OVO · GoPay</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="qris">
          <div class="deposit-method-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"/>
              <rect x="14" y="3" width="7" height="7"/>
              <rect x="3" y="14" width="7" height="7"/>
              <path d="M14 14h3v3h-3z"/>
              <path d="M17 17h3v3h-3z"/>
              <path d="M14 20h3"/>
            </svg>
          </div>
          <div class="deposit-method-text"><strong>QRIS</strong><span>Scan &amp; bayar instan</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="pulsa">
          <div class="deposit-method-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 20h.01"/>
              <path d="M7 20v-4"/>
              <path d="M12 20v-8"/>
              <path d="M17 20V8"/>
              <path d="M22 4v16"/>
            </svg>
          </div>
          <div class="deposit-method-text"><strong>Pulsa</strong><span>Telkomsel · XL · Indosat</span></div>
        </label>
      </div>
      <div class="deposit-actions">
        <button type="button" class="btn-outline" onclick="closeDepositModal()">Batal</button>
        <button type="submit" class="btn-primary">Lanjutkan</button>
      </div>
      <p class="deposit-min-note">Minimal deposit Rp 50.000 · Diproses otomatis</p>
    </form>
  </div>
</div>

<!-- RIWAYAT TRANSAKSI -->
<div id="historyModal" class="modal history-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg> Riwayat Transaksi</h3>
      <span class="close" onclick="closeHistoryModal()">&times;</span>
    </div>
    <div class="history-tabs" id="historyTabs">
      <button type="button" class="history-tab active" data-filter="all">Semua</button>
      <button type="button" class="history-tab" data-filter="deposit">Deposit</button>
      <button type="button" class="history-tab" data-filter="withdraw">Withdraw</button>
    </div>
    <div class="history-body" id="historyBody"></div>
  </div>
</div>

<!-- RIWAYAT TARUHAN -->
<div id="betHistoryModal" class="modal history-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg> Riwayat Taruhan</h3>
      <span class="close" onclick="closeBetHistoryModal()">&times;</span>
    </div>
    <div class="history-tabs" id="betHistoryTabs">
      <button type="button" class="history-tab active" data-filter="all">Semua</button>
      <button type="button" class="history-tab" data-filter="win">Menang</button>
      <button type="button" class="history-tab" data-filter="lose">Kalah</button>
    </div>
    <div class="history-body" id="betHistoryBody"></div>
  </div>
</div>

<!-- BONUS & PROMO -->
<div id="bonusModal" class="modal bonus-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg> Bonus &amp; Promo</h3>
      <span class="close" onclick="closeBonusModal()">&times;</span>
    </div>
    <div class="bonus-body" id="bonusBody"></div>
  </div>
</div>

<div class="logout-modal" id="logoutModal">
  <div class="logout-box">
    <h3>Logout</h3>
    <p>Apakah Anda yakin ingin keluar?</p>
    <div class="logout-actions">
      <button type="button" class="logout-cancel" onclick="closeLogout()">Batal</button>
      <button type="button" class="logout-confirm" onclick="location.href='/casino/member/logout.php'">Keluar</button>
    </div>
  </div>
</div>

<script>
let bi = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('#bannerDots span');

function goBanner(i) {
  slides[bi]?.classList.remove('active');
  dots[bi]?.classList.remove('active');
  bi = (i + slides.length) % slides.length;
  slides[bi].classList.add('active');
  dots[bi].classList.add('active');
}
function slideBanner(d) { goBanner(bi + d); }
setInterval(() => goBanner(bi + 1), 5000);

window.addEventListener('scroll', () => {
  document.querySelector('header')?.classList.toggle('scrolled', window.scrollY > 20);
});

/* ===== SEARCH MODAL ===== */
const games = <?= json_encode($searchGames, JSON_UNESCAPED_UNICODE) ?>;
const searchResult = document.getElementById('searchResult');
const popupSearch = document.getElementById('popupSearch');

function playGame(id) {
  window.location.href = 'game.php?id=' + id;
}

function openSearch(e) {
  if (e && e.preventDefault) e.preventDefault();
  const modal = document.getElementById('searchModal');
  modal.classList.add('show');
  modal.classList.remove('searching');
  popupSearch.value = '';
  renderSearch(games);
  setTimeout(() => popupSearch.focus(), 80);
}

function closeSearch() {
  document.getElementById('searchModal').classList.remove('show');
}

function backSearch() {
  popupSearch.value = '';
  const modal = document.getElementById('searchModal');
  modal.classList.remove('searching');
  renderSearch(games);
  document.querySelectorAll('.hero-pill').forEach(x => x.classList.remove('active'));
  popupSearch.focus();
}

function renderSearch(list) {
  if (popupSearch.value === '' && list.length === games.length) {
    searchResult.innerHTML = `
      <div class="search-hero">
        <div class="search-hero-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg>
        </div>
        <h2>Temukan Kemenangan Terbesarmu</h2>
        <p>Jelajahi <strong>${games.length}+</strong> permainan dari provider terbaik dunia.</p>
        <div class="hero-tags">
          <div class="hero-pill" data-cat="slots">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="8" y2="16"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="16" y1="8" x2="16" y2="16"/></svg> Slots
          </div>
          <div class="hero-pill" data-cat="live">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg> Live
          </div>
          <div class="hero-pill" data-cat="crash">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg> Crash
          </div>
          <div class="hero-pill" data-cat="table">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="12" height="16" rx="2"/><path d="M9 3v16"/><path d="M17 7l2 1v11a2 2 0 0 1-2 2H9"/></svg> Table
          </div>
          <div class="hero-pill" data-cat="all">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 23c-4.4 0-8-3-8-7.5 0-2.5 1.2-4.7 3-6.2.5 2.2 1.8 3.4 3 4-1-4 2-8 5-10 1 3 3 5 5 6.5 1.5 1.2 2.5 2.8 2.5 5.2C22.5 20 18.4 23 12 23z"/></svg> Semua
          </div>
        </div>
      </div>`;
    return;
  }

  if (list.length === 0) {
    searchResult.innerHTML = `
      <div class="search-empty">
        <h3>Tidak ada game ditemukan</h3>
        <p>Coba gunakan kata kunci lain.</p>
      </div>`;
    return;
  }

  searchResult.innerHTML = `
    <div class="search-grid">
      ${list.map(g => `
        <div class="search-card" onclick="playGame(${g.id})">
          <img src="${g.img}" alt="${g.name}" loading="lazy">
          <div class="search-card-info">
            <h4>${g.name}</h4>
            <div class="search-card-provider">${g.provider || ''}</div>
            <div class="search-card-rtp">RTP ${Number(g.rtp).toFixed(2)}%</div>
          </div>
        </div>
      `).join('')}
    </div>`;

  document.querySelectorAll('.search-card').forEach((card, index) => {
    setTimeout(() => card.classList.add('show'), index * 45);
  });
}

popupSearch?.addEventListener('input', () => {
  const modal = document.getElementById('searchModal');
  const key = popupSearch.value.toLowerCase().trim();
  if (key.length > 0) modal.classList.add('searching');
  else modal.classList.remove('searching');

  renderSearch(
    games.filter(g =>
      g.name.toLowerCase().includes(key) ||
      (g.provider || '').toLowerCase().includes(key)
    )
  );
});

document.addEventListener('click', function(e) {
  const pill = e.target.closest('.hero-pill');
  if (!pill) return;
  const cat = pill.dataset.cat;
  popupSearch.value = '';
  document.querySelectorAll('.hero-pill').forEach(x => x.classList.remove('active'));
  pill.classList.add('active');
  if (cat === 'all') {
    renderSearch(games);
    return;
  }
  renderSearch(games.filter(g => g.cat === cat));
});

document.getElementById('searchModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeSearch();
});

function toggleUserMenu() {
  document.getElementById('userMenu').classList.toggle('show');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.user-dropdown')) {
    document.getElementById('userMenu')?.classList.remove('show');
  }
});

function confirmLogout(e) {
  e.preventDefault();
  document.getElementById('userMenu')?.classList.remove('show');
  document.getElementById('logoutModal').classList.add('show');
}
function closeLogout() {
  document.getElementById('logoutModal').classList.remove('show');
}
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeSearch(); closeLogout(); }
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openSearch(e); }
});

/* ===== FAVORITE SYSTEM (DATABASE = SOURCE OF TRUTH) ===== */
const SVG_HEART_EMPTY = `<svg class="ico heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
const SVG_HEART_FULL = `<svg class="ico heart" viewBox="0 0 24 24" fill="#ff3b5c" stroke="#ff3b5c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;

function refreshFavoriteUi() {
    document.querySelectorAll('.game-card[data-game-id] .favorite').forEach(btn => {
        btn.innerHTML = btn.classList.contains('active') ? SVG_HEART_FULL : SVG_HEART_EMPTY;
    });
}

function updateFavCount(value) {
    const el = document.getElementById('favCount');
    if (el) el.textContent = Math.max(0, Number(value) || 0);
}

function toggleFavorite(gameId, el) {
    if (!el || el.dataset.busy === '1') return;

    const card = el.closest('.game-card');
    const wasActive = el.classList.contains('active');
    el.dataset.busy = '1';
    el.style.pointerEvents = 'none';

    fetch('/casino/member/favorite.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        credentials: 'same-origin',
        body: 'action=toggle&game_id=' + encodeURIComponent(gameId)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Gagal mengupdate favorit');

        const added = data.action === 'added';
        el.classList.toggle('active', added);
        el.innerHTML = added ? SVG_HEART_FULL : SVG_HEART_EMPTY;

        const count = parseInt(document.getElementById('favCount')?.textContent || '0', 10);
        if (added && !wasActive) updateFavCount(count + 1);
        if (!added && wasActive) updateFavCount(count - 1);

        showToast(added ? '❤️' : '💔',
            added ? 'Game ditambahkan ke favorit!' : 'Game dihapus dari favorit.',
            'success');

        const side = new URLSearchParams(window.location.search).get('side') || 'all';
        if (!added && wasActive && side === 'fav' && card) {
            card.remove();
            const remaining = document.querySelectorAll('#gamesGrid .game-card[data-game-id]').length;
            const meta = document.querySelector('.section-label .meta');
            if (meta) meta.innerHTML = `<strong>${remaining}</strong> favorit ditampilkan`;

            if (remaining === 0) {
                const grid = document.getElementById('gamesGrid');
                const empty = document.createElement('div');
                empty.className = 'empty empty-fav';
                empty.style.gridColumn = '1 / -1';
                empty.innerHTML = `Belum ada game favorit.<br><span style="font-size:13px;color:#666">Klik icon ❤️ di game untuk menambahkan</span>`;
                grid.appendChild(empty);
            }
        }
    })
    .catch(error => {
        el.classList.toggle('active', wasActive);
        el.innerHTML = wasActive ? SVG_HEART_FULL : SVG_HEART_EMPTY;
        showToast('Error', error.message || 'Gagal terhubung ke server', 'error');
    })
    .finally(() => {
        el.dataset.busy = '0';
        el.style.pointerEvents = '';
    });
}

/* ===== Soft filter navigation (no scroll jump + smooth anim) ===== */
(function() {
  let busy = false;
  let controller = null;
  let searchTimer = null;

  function parseSideProvider(href) {
    try {
      const u = new URL(href, window.location.origin);
      return {
        side: (u.searchParams.get('side') || 'all').toLowerCase(),
        provider: u.searchParams.get('provider') || ''
      };
    } catch (_) {
      return { side: 'all', provider: '' };
    }
  }
  const _initSp = parseSideProvider(window.location.href);
  let lastSide = _initSp.side;
  let lastProvider = _initSp.provider;
  
function animateRtpBars(root) {
    const scope = root || document;

    const bars = Array.from(scope.querySelectorAll('.rtp-fill'));
    if (!bars.length) return;

    // Reset dulu supaya browser benar-benar mengenali state awal (0%).
    bars.forEach(bar => {
        bar.style.transition = 'none';
        bar.style.width = '0%';
    });

    // Paksa browser melakukan layout sebelum animasi dimulai.
    void scope.offsetWidth;

    requestAnimationFrame(() => {
        bars.forEach(bar => {
            const value = parseFloat(bar.getAttribute('data-w'));
            const width = Number.isFinite(value) ? Math.max(0, Math.min(value, 100)) : 0;

            // Pastikan transition kembali aktif setelah reset.
            bar.style.transition = 'width 1s ease';
            bar.style.width = width + '%';
        });
    });
}

  function staggerCards(grid) {
    if (!grid) return;
    grid.classList.remove('anim-in');
    void grid.offsetWidth;
    const cards = grid.querySelectorAll('.game-card');
    cards.forEach((card, i) => {
      card.style.animationDelay = Math.min(i * 28, 420) + 'ms';
    });
    grid.classList.add('anim-in');
  }

  function applyFilterHtml(doc, url, push) {
    const scrollY = window.pageYOffset;
    const next = parseSideProvider(url);

    const newSidebar = doc.querySelector('.sidebar');
    const curSidebar = document.querySelector('.sidebar');
    if (newSidebar && curSidebar) {
      const sidebarChanged =
        lastSide !== next.side || lastProvider !== next.provider;
      if (sidebarChanged) {
        const savedScroll = curSidebar.scrollTop;
        curSidebar.innerHTML = newSidebar.innerHTML;
        curSidebar.scrollTop = savedScroll;
        lastSide = next.side;
        lastProvider = next.provider;
      }
    }

    const newForm = doc.querySelector('#filterForm');
    const curForm = document.querySelector('#filterForm');
    if (newForm && curForm) {
      curForm.innerHTML = newForm.innerHTML;
    }

    const newPanel = doc.querySelector('#gamesPanel');
    const curPanel = document.querySelector('#gamesPanel');
    if (newPanel && curPanel) {
      curPanel.innerHTML = newPanel.innerHTML;
      curPanel.classList.remove('is-loading');
      const grid = curPanel.querySelector('#gamesGrid, .games-grid');
      staggerCards(grid);
      animateRtpBars(curPanel);
      
      // Favorite state dirender dari database oleh PHP.
      refreshFavoriteUi();
    }

    if (push) {
      history.pushState({ softNav: true }, '', url);
    }

    window.scrollTo(0, scrollY);
  }

  async function softNavigate(url, opts) {
    const push = !opts || opts.push !== false;
    if (!url) return;
    let absolute;
    try {
      absolute = new URL(url, window.location.href);
    } catch (_) {
      window.location.href = url;
      return;
    }
    if (absolute.pathname.replace(/\/+$/, '') !== window.location.pathname.replace(/\/+$/, '') &&
        !absolute.pathname.endsWith('game-list.php')) {
      window.location.href = url;
      return;
    }


    const next = absolute.pathname + absolute.search;
    if (busy && controller) controller.abort();

    busy = true;
    const panel = document.getElementById('gamesPanel');
    panel?.classList.add('is-loading');

    controller = new AbortController();
    try {
      const res = await fetch(absolute.href, {
        signal: controller.signal,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      if (!doc.querySelector('#gamesPanel')) throw new Error('Invalid page');
      applyFilterHtml(doc, next, push);
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      panel?.classList.remove('is-loading');
      window.location.href = url;
    } finally {
      busy = false;
    }
  }

  function formToUrl(form) {
    const fd = new FormData(form);
    const params = new URLSearchParams();
    fd.forEach((v, k) => {
      if (v === '' || v == null) return;
      if (k === 'side' && v === 'all') return;
      if (k === 'cat' && v === 'all') return;
      if (k === 'sort' && v === 'newest') return;
      if (k === 'page' && String(v) === '1') return;
      params.set(k, v);
    });
    const qs = params.toString();
    const base = form.getAttribute('action') || 'game-list.php';
    return base + (qs ? '?' + qs : '');
  }

  document.addEventListener('click', function(e) {
    const chip = e.target.closest('a.filter-chip');
    if (chip) {
      e.preventDefault();
      softNavigate(chip.href);
      return;
    }

    const sideLink = e.target.closest('.side-nav a, .provider-list a');
    if (sideLink) {
      e.preventDefault();
      softNavigate(sideLink.href);
      return;
    }

    const more = e.target.closest('a.btn-load-more');
    if (more) {
      e.preventDefault();
      softNavigate(more.href);
      return;
    }

    const reset = e.target.closest('#gamesPanel .empty a[href*="game-list"]');
    if (reset) {
      e.preventDefault();
      softNavigate(reset.href);
      return;
    }

    const sortBtn = e.target.closest('#sortBtn');
    if (sortBtn) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('sortWrap')?.classList.toggle('open');
      return;
    }

    const sortOpt = e.target.closest('.sort-option');
    if (sortOpt) {
      e.preventDefault();
      const wrap = document.getElementById('sortWrap');
      const input = document.getElementById('sortInput');
      const btn = document.getElementById('sortBtn');
      const menu = document.getElementById('sortMenu');
      const val = sortOpt.dataset.value;
      if (input) input.value = val;
      if (btn) btn.textContent = sortOpt.textContent.trim();
      menu?.querySelectorAll('.sort-option').forEach(o => o.classList.remove('active'));
      sortOpt.classList.add('active');
      wrap?.classList.remove('open');
      const form = document.getElementById('filterForm');
      if (form) softNavigate(formToUrl(form));
      return;
    }

    if (!e.target.closest('#sortWrap')) {
      document.getElementById('sortWrap')?.classList.remove('open');
    }
  });

  document.addEventListener('submit', function(e) {
    const form = e.target.closest('#filterForm');
    if (!form) return;
    e.preventDefault();
    softNavigate(formToUrl(form));
  });

  document.addEventListener('input', function(e) {
    if (!e.target.matches('.search-inline input[name="q"]')) return;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      const form = document.getElementById('filterForm');
      if (form) softNavigate(formToUrl(form));
    }, 450);
  });

  window.addEventListener('popstate', function() {
    softNavigate(window.location.href, { push: false });
  });

  window.softNavigateGames = softNavigate;
  window.formToUrlGames = formToUrl;
})();

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', function() {
    animateRtpBars(document);
    refreshFavoriteUi();
});

/* ===== DROPDOWN + DEPOSIT + HISTORY ===== */
function closeUserMenu() {
  document.getElementById('userMenu')?.classList.remove('show');
}

const sampleTransactions = [
  { type: 'deposit', title: 'Deposit Bank BCA', date: '10 Agu 2026 · 14:32', amount: 500000, status: 'ok', statusText: 'Berhasil' },
  { type: 'withdraw', title: 'Withdraw E-Wallet', date: '09 Agu 2026 · 21:10', amount: 250000, status: 'pending', statusText: 'Diproses' },
  { type: 'deposit', title: 'Deposit QRIS', date: '08 Agu 2026 · 11:05', amount: 100000, status: 'ok', statusText: 'Berhasil' },
  { type: 'withdraw', title: 'Withdraw Bank BNI', date: '07 Agu 2026 · 16:48', amount: 750000, status: 'ok', statusText: 'Berhasil' },
  { type: 'deposit', title: 'Deposit DANA', date: '05 Agu 2026 · 09:20', amount: 200000, status: 'fail', statusText: 'Gagal' }
];
const sampleBets = [
  { type: 'win', title: 'Gates of Olympus', date: '10 Agu 2026 · 15:12', amount: 1250000, stake: 25000 },
  { type: 'lose', title: 'Sweet Bonanza', date: '10 Agu 2026 · 14:58', amount: 50000, stake: 50000 },
  { type: 'win', title: 'Starlight Princess', date: '09 Agu 2026 · 22:41', amount: 340000, stake: 20000 },
  { type: 'lose', title: 'Aviator', date: '09 Agu 2026 · 20:03', amount: 100000, stake: 100000 }
];
const sampleBonuses = [
  { id: 1, tag: 'WELCOME', title: 'Bonus Deposit 100%', desc: 'Min deposit Rp50.000 · Maks bonus Rp1.000.000', expiry: 'Berlaku hingga 17 Agu 2026', claimed: false },
  { id: 2, tag: 'HARIAN', title: 'Cashback 10%', desc: 'Cashback kekalahan slot setiap hari, maks Rp500.000', expiry: 'Reset setiap hari 00:00', claimed: false },
  { id: 3, tag: 'MINGGUAN', title: 'Reload Bonus 50%', desc: 'Bonus reload setiap Senin, min Rp100.000', expiry: 'Berlaku Senin saja', claimed: true }
];

function fmtRp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

function openHistoryModal(e) {
  if (e) e.preventDefault();
  closeUserMenu();
  renderTransactions('all');
  document.getElementById('historyModal').style.display = 'flex';
}
function closeHistoryModal() { document.getElementById('historyModal').style.display = 'none'; }

function openBetHistoryModal(e) {
  if (e) e.preventDefault();
  closeUserMenu();
  renderBets('all');
  document.getElementById('betHistoryModal').style.display = 'flex';
}
function closeBetHistoryModal() { document.getElementById('betHistoryModal').style.display = 'none'; }

function openBonusModal(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  closeUserMenu();
  renderBonuses();
  const modal = document.getElementById('bonusModal');
  if (!modal) return;
  modal.style.display = 'flex';
  const box = modal.querySelector('.modal-content');
  if (box) {
    box.style.animation = 'none';
    void box.offsetWidth;
    box.style.animation = '';
  }
}
function closeBonusModal() {
  const modal = document.getElementById('bonusModal');
  if (modal) modal.style.display = 'none';
}

function renderTransactions(filter) {
  const body = document.getElementById('historyBody');
  let list = sampleTransactions;
  if (filter !== 'all') list = list.filter(t => t.type === filter);
  if (!list.length) {
    body.innerHTML = '<div class="history-empty"><strong>Belum ada transaksi</strong><p>Riwayat akan muncul di sini.</p></div>';
    return;
  }
  body.innerHTML = list.map(t => {
    const isDep = t.type === 'deposit';
    const icon = isDep
      ? '<svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>'
      : '<svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
    return `<div class="history-item">
      <div class="history-item-icon ${isDep ? 'deposit' : 'withdraw'}">${icon}</div>
      <div class="history-item-info"><strong>${t.title}</strong><span>${t.date}</span></div>
      <div class="history-item-amount">
        <span class="val ${isDep ? 'plus' : 'minus'}">${isDep ? '+' : '−'}${fmtRp(t.amount)}</span>
        <div class="status ${t.status}">${t.statusText}</div>
      </div>
    </div>`;
  }).join('');
}

function renderBets(filter) {
  const body = document.getElementById('betHistoryBody');
  let list = sampleBets;
  if (filter !== 'all') list = list.filter(t => t.type === filter);
  if (!list.length) {
    body.innerHTML = '<div class="history-empty"><strong>Belum ada taruhan</strong><p>Riwayat taruhan akan muncul di sini.</p></div>';
    return;
  }
  body.innerHTML = list.map(t => {
    const isWin = t.type === 'win';
    const icon = '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/></svg>';
    return `<div class="history-item">
      <div class="history-item-icon ${isWin ? 'win' : 'lose'}">${icon}</div>
      <div class="history-item-info"><strong>${t.title}</strong><span>${t.date} · Stake ${fmtRp(t.stake)}</span></div>
      <div class="history-item-amount">
        <span class="val ${isWin ? 'plus' : 'minus'}">${isWin ? '+' : '−'}${fmtRp(t.amount)}</span>
        <div class="status ${isWin ? 'ok' : 'fail'}">${isWin ? 'Menang' : 'Kalah'}</div>
      </div>
    </div>`;
  }).join('');
}

function renderBonuses() {
  const body = document.getElementById('bonusBody');
  body.innerHTML = sampleBonuses.map(b => `
    <div class="bonus-card">
      <div class="bonus-card-top">
        <div class="bonus-card-icon"><svg viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg></div>
        <div class="bonus-card-info">
          <div class="tag">${b.tag}</div>
          <h4>${b.title}</h4>
          <p>${b.desc}</p>
        </div>
      </div>
      <div class="bonus-card-meta">
        <span class="expiry">${b.expiry}</span>
        ${b.claimed
          ? '<button type="button" class="btn-claim-sm btn-claimed" disabled>Sudah Diklaim</button>'
          : `<button type="button" class="btn-claim-sm" onclick="claimBonus(${b.id}, this)">Klaim</button>`}
      </div>
    </div>`).join('');
}

function claimBonus(id, btn) {
  const item = sampleBonuses.find(b => b.id === id);
  if (!item || item.claimed) return;
  item.claimed = true;
  btn.textContent = 'Sudah Diklaim';
  btn.disabled = true;
  btn.classList.add('btn-claimed');
  showToast('Berhasil', 'Bonus berhasil diklaim!', 'success');
}

document.getElementById('historyTabs')?.addEventListener('click', function(e) {
  const tab = e.target.closest('.history-tab');
  if (!tab) return;
  this.querySelectorAll('.history-tab').forEach(t => t.classList.remove('active'));
  tab.classList.add('active');
  renderTransactions(tab.dataset.filter);
});
document.getElementById('betHistoryTabs')?.addEventListener('click', function(e) {
  const tab = e.target.closest('.history-tab');
  if (!tab) return;
  this.querySelectorAll('.history-tab').forEach(t => t.classList.remove('active'));
  tab.classList.add('active');
  renderBets(tab.dataset.filter);
});

window.addEventListener('click', function(e) {
  if (e.target === document.getElementById('historyModal')) closeHistoryModal();
  if (e.target === document.getElementById('betHistoryModal')) closeBetHistoryModal();
  if (e.target === document.getElementById('bonusModal')) closeBonusModal();
  if (e.target === document.getElementById('depositModal')) closeDepositModal();
});

function openDepositModal() {
  const modal = document.getElementById('depositModal');
  modal.style.display = 'flex';
  const amountInput = document.getElementById('depositAmount');
  amountInput.value = '';
  document.querySelectorAll('.deposit-preset').forEach(b => b.classList.remove('active'));
  setTimeout(() => amountInput.focus(), 150);
}
function closeDepositModal() {
  document.getElementById('depositModal').style.display = 'none';
}

function formatDepositAmount(val) {
  const num = String(val).replace(/\D/g, '');
  if (!num) return '';
  return Number(num).toLocaleString('id-ID');
}
function parseDepositAmount(val) {
  return Number(String(val).replace(/\D/g, '')) || 0;
}

const depositAmountInput = document.getElementById('depositAmount');
if (depositAmountInput) {
  depositAmountInput.addEventListener('input', function() {
    const raw = this.value.replace(/\D/g, '');
    this.value = formatDepositAmount(raw);
    const amount = Number(raw) || 0;
    document.querySelectorAll('.deposit-preset').forEach(btn => {
      btn.classList.toggle('active', Number(btn.dataset.amount) === amount);
    });
  });
}
document.querySelectorAll('.deposit-preset').forEach(btn => {
  btn.addEventListener('click', function() {
    const amount = this.dataset.amount;
    depositAmountInput.value = formatDepositAmount(amount);
    document.querySelectorAll('.deposit-preset').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    depositAmountInput.focus();
  });
});
document.querySelectorAll('.deposit-method').forEach(label => {
  label.addEventListener('click', function() {
    document.querySelectorAll('.deposit-method').forEach(m => m.classList.remove('active'));
    this.classList.add('active');
    const radio = this.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
  });
});

function submitDeposit(e) {
  e.preventDefault();
  const amount = parseDepositAmount(depositAmountInput.value);
  const method = document.querySelector('input[name="method"]:checked')?.value;
  if (amount < 50000) {
    showToast('Gagal', 'Minimal deposit adalah Rp 50.000', 'error');
    depositAmountInput.focus();
    return false;
  }
  if (!method) {
    showToast('Gagal', 'Pilih metode pembayaran terlebih dahulu.', 'error');
    return false;
  }
  depositAmountInput.value = amount;
  document.getElementById('depositForm').submit();
  return true;
}
</script>
</body>
</html>