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
    $stmt = $conn->prepare("
        SELECT id, username, fullname, avatar, balance, level, points
        FROM users WHERE id = ? LIMIT 1
    ");
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
$fullname = $user['fullname'] ?: $username;
$balance  = (float) $user['balance'];
$level    = $user['level'] ?: 'Bronze';
$points   = (int) $user['points'];
$avatar_initials = strtoupper(mb_substr($username, 0, 2));
$avatar_file = $user['avatar'] ?? null;
$avatar_url  = $avatar_file ? '../uploads/avatars/' . $avatar_file : null;

/* ========== GAME DATA ========== */
$game_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$game = null;
if ($game_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM rtp_live WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
}

if (!$game) {
    header('Location: member.php');
    exit;
}

/* ===== FAVORITE STATE (DATABASE) ===== */
$is_favorite = false;
if ($user_id && $game_id > 0) {
    $fav_stmt = $conn->prepare("SELECT 1 FROM user_favorites WHERE user_id = ? AND game_id = ? LIMIT 1");
    if ($fav_stmt) {
        $fav_stmt->bind_param('ii', $user_id, $game_id);
        $fav_stmt->execute();
        $is_favorite = (bool) $fav_stmt->get_result()->fetch_row();
        $fav_stmt->close();
    }
}

$game_name  = $game['game_name'];
$provider   = $game['provider'];
$category   = strtolower($game['category'] ?? 'slots');
$rtp        = round((float) $game['rtp'], 2);
$status     = strtolower($game['status'] ?? '');
$trend      = $game['trend'] ?? '';
$volatility  = $game['volatility'] ?? 'Medium';

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
$game_img = $gameImages[$game_name] ?? '../assets/game/default.jpg';

$rtp_color = $rtp >= 96 ? '#2dff6b' : ($rtp >= 90 ? '#FFD700' : '#ff3b5c');
$rtp_label = $rtp >= 96 ? 'Excellent' : ($rtp >= 90 ? 'Good' : 'Average');

/* ===== SVG ICON LIBRARY ===== */
$SVG = [
    'megaways'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="4" height="18" rx="1"/><rect x="10" y="3" width="4" height="18" rx="1"/><rect x="18" y="3" width="4" height="18" rx="1"/></svg>',
    'star'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    'spin'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M22 12A10 10 0 0 1 3.6 18.4"/><path d="M2 12a10 10 0 0 1 18.4-6.4"/></svg>',
    'multiplier' => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/><circle cx="12" cy="12" r="9"/></svg>',
    'wild'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    'tumble'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 10 12 15 17 10"/><polyline points="7 6 12 11 17 6"/><line x1="12" y1="15" x2="12" y2="21"/></svg>',
    'antebet'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    'bomb'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="13" r="7"/><path d="M14.35 4.65l2.83 2.83"/><path d="M17 2l2 2"/><path d="M8 13h6"/><path d="M11 10v6"/></svg>',
    'airplane'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21 4 19 2c-2-2-4-2-5.5-.5L10 5 1.8 6.2A1 1 0 0 0 1 7.2l.8 2.4a1 1 0 0 0 .8.6L6 10l-1 4H4l-1 2 3 1 1 3 2-1v-1l4-1 .4 3.4a1 1 0 0 0 .6.8l2.4.8a1 1 0 0 0 1-.2l1-1.2z"/></svg>',
    'cashout'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
    'chart'      => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'rocket'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'gravity'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="3"/><line x1="12" y1="8" x2="12" y2="21"/><polyline points="9 18 12 21 15 18"/></svg>',
    'risk'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'mine'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>',
    'grid'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
    'diamond'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.7 10.3a2.41 2.41 0 0 0 0 3.41l7.59 7.59a2.41 2.41 0 0 0 3.41 0l7.59-7.59a2.41 2.41 0 0 0 0-3.41L13.7 2.71a2.41 2.41 0 0 0-3.41 0z"/></svg>',
    'coins'      => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/></svg>',
    'multibet'   => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="9" height="9" rx="1"/><rect x="13" y="7" width="9" height="9" rx="1"/><path d="M6 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M17 7V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"/></svg>',
    'live'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3" fill="currentColor"/><path d="M6.34 6.34a8 8 0 1 0 11.32 0"/></svg>',
    'shield'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'plinko'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="4" r="2" fill="currentColor"/><circle cx="6" cy="10" r="1.5"/><circle cx="12" cy="10" r="1.5"/><circle cx="18" cy="10" r="1.5"/><circle cx="9" cy="15" r="1.5"/><circle cx="15" cy="15" r="1.5"/><rect x="4" y="19" width="4" height="3" rx="1"/><rect x="10" y="19" width="4" height="3" rx="1"/><rect x="16" y="19" width="4" height="3" rx="1"/></svg>',
    'target'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'satellite'  => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09zM12 15l-3-3 8-8 3 3z"/><path d="m15 12 3.5 3.5"/><path d="M9 9 5.5 5.5"/></svg>',
    'mobile'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
    'cluster'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><circle cx="5" cy="7" r="2"/><circle cx="19" cy="7" r="2"/><circle cx="5" cy="17" r="2"/><circle cx="19" cy="17" r="2"/><line x1="7" y1="8.5" x2="10" y2="10.5"/><line x1="17" y1="8.5" x2="14" y2="10.5"/><line x1="7" y1="15.5" x2="10" y2="13.5"/><line x1="17" y1="15.5" x2="14" y2="13.5"/></svg>',
    'dragon'     => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8 2 4 5 4 9c0 2 1 4 2 5l-2 6h16l-2-6c1-1 2-3 2-5 0-4-4-7-8-7z"/><path d="M9 9h.01M15 9h.01"/></svg>',
    'fish'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6z"/><path d="M18 12h-8"/><path d="M6.5 12 2 8"/><path d="m6.5 12-4.5 4"/><circle cx="16" cy="11" r="1" fill="currentColor" stroke="none"/></svg>',
    'mahjong'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="7" height="9" rx="1"/><rect x="14" y="5" width="7" height="9" rx="1"/><path d="M6 10h1M10 10h1"/><path d="M17 10h1M21 10h1"/><rect x="7" y="16" width="10" height="3" rx="1"/></svg>',
    'neko'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/><path d="M8 2 9 7"/><path d="M16 2 15 7"/></svg>',
    'buffalo'    => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7c0 0 1-3 5-3s5 2 5 2 1-2 5-2 5 3 5 3"/><path d="M6 7v3a6 6 0 0 0 12 0V7"/><path d="M9 17v4"/><path d="M15 17v4"/><path d="M9 21h6"/></svg>',
    'ways'       => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>',
];

/* Game features & about */
$gameFeaturesMap = [
    // ===== SLOTS =====
    'The Dog House Megaways' => [
        ['icon'=>'megaways', 'title'=>'Megaways™',       'desc'=>'Hingga 11.664 cara untuk menang'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Dapatkan hingga 15 putaran gratis'],
        ['icon'=>'multiplier','title'=>'Multiplier Tinggi','desc'=>'Menangkan hingga 6,750x taruhan'],
        ['icon'=>'wild',     'title'=>'Sticky Wilds',     'desc'=>'Wild tetap di reel sepanjang Free Spins'],
    ],
    'Gates of Olympus' => [
        ['icon'=>'tumble',   'title'=>'Tumble',           'desc'=>'Simbol jatuh & rantai kemenangan beruntun'],
        ['icon'=>'multiplier','title'=>'Multiplier 500x', 'desc'=>'Random multiplier hingga 500x per spin'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Putaran gratis dengan akumulasi multiplier'],
        ['icon'=>'antebet',  'title'=>'Ante Bet',         'desc'=>'Tingkatkan 25% taruhan untuk scatter lebih sering'],
    ],
    'Sweet Bonanza' => [
        ['icon'=>'tumble',   'title'=>'Tumble',           'desc'=>'Kemenangan beruntun tanpa henti di satu spin'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Hingga 30 putaran gratis bisa di-retrigger'],
        ['icon'=>'bomb',     'title'=>'Bomb Multiplier',  'desc'=>'Lollipop bomb multiplier hingga 100x'],
        ['icon'=>'antebet',  'title'=>'Bonus Buy',        'desc'=>'Beli langsung fitur Free Spins'],
    ],
    'Starlight Princess' => [
        ['icon'=>'tumble',   'title'=>'Tumble',           'desc'=>'Simbol hilang & diganti simbol baru otomatis'],
        ['icon'=>'multiplier','title'=>'Random Multiplier','desc'=>'Pengali acak hingga 500x saat Free Spins'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Scatter trigger kapan saja, bisa retrigger'],
        ['icon'=>'antebet',  'title'=>'Ante Bet',         'desc'=>'Naikkan taruhan untuk peluang scatter lebih tinggi'],
    ],
    'Big Bass Bonanza' => [
        ['icon'=>'fish',     'title'=>'Money Symbol',     'desc'=>'Ikan pembawa uang dengan nilai acak'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'10 putaran gratis dengan potensi tambahan spin'],
        ['icon'=>'multiplier','title'=>'Angler Multiplier','desc'=>'Tiap ikan di Free Spins gandakan total hadiah'],
        ['icon'=>'wild',     'title'=>'Wild Substitusi',  'desc'=>'Wild mengganti semua simbol kecuali scatter'],
    ],
    'Fruit Party' => [
        ['icon'=>'cluster',  'title'=>'Cluster Pays',     'desc'=>'Menang dengan 5+ simbol bersebelahan'],
        ['icon'=>'tumble',   'title'=>'Tumble',           'desc'=>'Setiap kemenangan picu simbol baru berjatuhan'],
        ['icon'=>'multiplier','title'=>'Random Multiplier','desc'=>'Pengali acak muncul saat fitur Free Spins'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'4 scatter trigger putaran gratis berlipat'],
    ],
    'Dragon Gold' => [
        ['icon'=>'dragon',   'title'=>'Dragon Wild',      'desc'=>'Wild naga perluas dan gandakan kemenangan'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Scatter trigger hingga 25 putaran gratis'],
        ['icon'=>'multiplier','title'=>'Win Multiplier',  'desc'=>'Pengali kemenangan hingga 3x selama bonus'],
        ['icon'=>'coins',    'title'=>'Jackpot Prize',    'desc'=>'Jackpot tetap tersedia di setiap taruhan'],
    ],
    'Wild Bandito' => [
        ['icon'=>'wild',     'title'=>'Split Wild',       'desc'=>'Wild menyebar ke posisi acak di reel'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Hingga 20 putaran gratis dengan retrigger'],
        ['icon'=>'multiplier','title'=>'Stacked Multiplier','desc'=>'Multiplier bertumpuk hingga 20x saat bonus'],
        ['icon'=>'star',     'title'=>'Super Wild',       'desc'=>'Super Wild muncul khusus saat Free Spins aktif'],
    ],
    'Wanted Dead or a Wild' => [
        ['icon'=>'wild',     'title'=>'Walking Wilds',    'desc'=>'Wild bergerak satu langkah tiap spin'],
        ['icon'=>'star',     'title'=>'Colossal Symbols', 'desc'=>'Simbol raksasa 3x3 tutup banyak posisi'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Hingga 20 putaran gratis dengan retrigger'],
        ['icon'=>'multiplier','title'=>'Max Win 12,345x', 'desc'=>'Potensi kemenangan tertinggi di kelasnya'],
    ],
    'Mahjong Ways 2' => [
        ['icon'=>'mahjong',  'title'=>'324 Ways',         'desc'=>'Hingga 324 cara menang di setiap spin'],
        ['icon'=>'wild',     'title'=>'Wild Tiles',       'desc'=>'Tile wild gantikan semua tile biasa'],
        ['icon'=>'spin',     'title'=>'Free Games',       'desc'=>'Scatter trigger Free Games dengan fitur khusus'],
        ['icon'=>'multiplier','title'=>'Bonus Tiles',     'desc'=>'Tile spesial hadir dengan nilai prize tinggi'],
    ],
    'Lucky Neko' => [
        ['icon'=>'neko',     'title'=>'Maneki Neko Wild', 'desc'=>'Wild kucing keberuntungan perluas reel'],
        ['icon'=>'star',     'title'=>'Gigantic Symbol',  'desc'=>'Simbol raksasa 3x3 untuk kemenangan besar'],
        ['icon'=>'spin',     'title'=>'Respin',           'desc'=>'Respin otomatis saat Gigantic Symbol muncul'],
        ['icon'=>'coins',    'title'=>'Jackpot Meter',    'desc'=>'Kumpulkan koin emas untuk jackpot spesial'],
    ],
    'Buffalo King' => [
        ['icon'=>'buffalo',  'title'=>'4,096 Ways',       'desc'=>'Megaways-style dengan 4,096 cara menang'],
        ['icon'=>'spin',     'title'=>'Free Spins',       'desc'=>'Hingga 100 putaran gratis dengan retrigger'],
        ['icon'=>'wild',     'title'=>'Buffalo Wild',     'desc'=>'Wild kerbau dengan multiplier hingga 5x'],
        ['icon'=>'coins',    'title'=>'Coin Collect',     'desc'=>'Kumpulkan koin untuk nilai hadiah ekstra'],
    ],
    'Sugar Rush' => [
        ['icon'=>'cluster',  'title'=>'Cluster Pays',     'desc'=>'Menang dengan 5+ permen bersebelahan'],
        ['icon'=>'tumble',   'title'=>'Tumble',           'desc'=>'Permen menghilang & diganti kombinasi baru'],
        ['icon'=>'multiplier','title'=>'Win Multiplier',  'desc'=>'Multiplier naik terus selama Free Spins'],
        ['icon'=>'antebet',  'title'=>'Bonus Buy',        'desc'=>'Akses langsung Free Spins tanpa menunggu'],
    ],

    // ===== CRASH GAMES =====
    'Aviator' => [
        ['icon'=>'airplane', 'title'=>'Auto Cashout',     'desc'=>'Set target multiplier, cashout otomatis tercapai'],
        ['icon'=>'multibet', 'title'=>'Dual Bet',         'desc'=>'Pasang dua taruhan sekaligus dalam satu ronde'],
        ['icon'=>'live',     'title'=>'Live Multiplayer', 'desc'=>'Bermain bersama ribuan pemain secara real-time'],
        ['icon'=>'chart',    'title'=>'Statistik Live',   'desc'=>'Pantau histori penerbangan & pola multiplier'],
    ],
    'JetX' => [
        ['icon'=>'rocket',   'title'=>'Multi-Bet',        'desc'=>'Hingga 3 taruhan aktif secara bersamaan'],
        ['icon'=>'cashout',  'title'=>'Auto Cashout',     'desc'=>'Cashout otomatis di multiplier target Anda'],
        ['icon'=>'live',     'title'=>'Multiplayer Live', 'desc'=>'Lihat cashout pemain lain secara real-time'],
        ['icon'=>'chart',    'title'=>'Jackpot Meter',    'desc'=>'Jackpot progresif terpicu di ronde tertentu'],
    ],
    'Spaceman' => [
        ['icon'=>'satellite','title'=>'Free Bets',        'desc'=>'Dapatkan taruhan gratis dari fitur bonus'],
        ['icon'=>'cashout',  'title'=>'Auto Cashout',     'desc'=>'Atur multiplier target & cashout otomatis'],
        ['icon'=>'live',     'title'=>'Multiplayer Live', 'desc'=>'Lihat semua pemain & keputusan cashout mereka'],
        ['icon'=>'chart',    'title'=>'Turbo Mode',       'desc'=>'Percepat ronde untuk sesi bermain lebih cepat'],
    ],

    // ===== INSTANT GAMES =====
    'Plinko' => [
        ['icon'=>'plinko',   'title'=>'Pilih Risk Level', 'desc'=>'Low / Medium / High untuk kontrol volatilitas'],
        ['icon'=>'target',   'title'=>'Multi-Ball',       'desc'=>'Jatuhkan hingga 10 bola sekaligus per ronde'],
        ['icon'=>'multiplier','title'=>'Max 1,000x',      'desc'=>'Kotak tepi board capai multiplier tertinggi'],
        ['icon'=>'risk',     'title'=>'16 Slot Board',    'desc'=>'Board 16 kolom untuk distribusi paling akurat'],
    ],
    'Mines' => [
        ['icon'=>'mine',     'title'=>'Custom Mines',     'desc'=>'Pilih 1–24 ranjau sesuai risk appetite Anda'],
        ['icon'=>'grid',     'title'=>'5×5 Grid',         'desc'=>'25 kotak dengan distribusi ranjau acak setiap ronde'],
        ['icon'=>'cashout',  'title'=>'Cashout Kapan Saja','desc'=>'Kunci kemenangan sebelum kena ranjau'],
        ['icon'=>'diamond',  'title'=>'Max 1,000x',       'desc'=>'Buka semua kotak aman untuk multiplier tertinggi'],
    ],
];

$game_features = $gameFeaturesMap[$game_name] ?? [
    ['icon'=>'star',     'title'=>'RTP Tinggi',   'desc'=>'Return to Player kompetitif'],
    ['icon'=>'spin',     'title'=>'Free Spins',   'desc'=>'Putaran gratis di fitur bonus'],
    ['icon'=>'wild',     'title'=>'Wild Symbol',  'desc'=>'Simbol wild membantu kombinasi'],
    ['icon'=>'mobile',   'title'=>'Mobile Ready', 'desc'=>'Optimasi penuh di semua perangkat'],
];

$gameAboutMap = [
    'Big Bass Bonanza' => [
        'desc' => 'Big Bass Bonanza adalah slot fishing bertema dari Pragmatic Play. Kumpulkan simbol pancing untuk trigger Free Spins dengan multiplier Money Symbol yang bisa mencapai kemenangan besar.',
        'year' => '2020', 'reels' => '5', 'rows' => 'Slots', 'max_win' => '2,100x',
    ],
    'The Dog House Megaways' => [
        'desc' => 'The Dog House Megaways dari Pragmatic Play menghadirkan Megaways™ hingga 11.664 cara menang. Nikmati Sticky Wilds, Free Spins, dan potensi kemenangan hingga 6,750x taruhan Anda!',
        'year' => '2019', 'reels' => '6', 'rows' => 'Megaways™', 'max_win' => '6,750x',
    ],
    'Fruit Party' => [
        'desc' => 'Fruit Party dari Pragmatic Play adalah slot 7x7 dengan mekanisme cluster pays. Setiap kemenangan memicu Tumble dan peluang multiplier acak yang bisa menggandakan reward Anda.',
        'year' => '2020', 'reels' => '7', 'rows' => '7', 'max_win' => '5,000x',
    ],
    'Dragon Gold' => [
        'desc' => 'Dragon Gold membawa Anda ke dunia naga legenda Asia dengan simbol scatter dan wild yang kuat. Aktifkan free spins dan raih potensi kemenangan besar bersama sang naga.',
        'year' => '2021', 'reels' => '5', 'rows' => 'Slots', 'max_win' => '1,500x',
    ],
    'Gates of Olympus' => [
        'desc' => 'Gates of Olympus dari Pragmatic Play membawa Anda ke puncak Olympus bersama Zeus. Fitur Tumble, multiplier hingga 500x, dan Free Spins menjadikannya slot dengan volatilitas sangat tinggi.',
        'year' => '2021', 'reels' => '6', 'rows' => '5', 'max_win' => '5,000x',
    ],
    'Sweet Bonanza' => [
        'desc' => 'Sweet Bonanza adalah slot permen 6x5 dari Pragmatic Play dengan mekanisme Tumble dan Bomb Multiplier hingga 100x. Free Spins bisa di-retrigger untuk kemenangan berantai yang manis.',
        'year' => '2019', 'reels' => '6', 'rows' => '5', 'max_win' => '21,100x',
    ],
    'Starlight Princess' => [
        'desc' => 'Starlight Princess dari Pragmatic Play hadir dengan tema putri bintang Jepang. Slot 6x5 ini punya Tumble mechanic, multiplier acak hingga 500x, dan Free Spins yang bisa triggered kapan saja.',
        'year' => '2021', 'reels' => '6', 'rows' => '5', 'max_win' => '5,000x',
    ],
    'Wild Bandito' => [
        'desc' => 'Wild Bandito dari PG Soft membawa tema koboi Meksiko yang seru. Fitur Split Wilds dan Free Spins dengan multiplier bertumpuk bisa mengantarkan kemenangan besar di setiap putaran.',
        'year' => '2022', 'reels' => '5', 'rows' => '4', 'max_win' => '2,500x',
    ],
    'Wanted Dead or a Wild' => [
        'desc' => 'Wanted Dead or a Wild dari Hacksaw Gaming adalah slot Wild West 5x5 dengan Colossal Symbols, Free Spins, dan Walking Wilds. Salah satu game dengan pembayaran tertinggi di kelasnya.',
        'year' => '2022', 'reels' => '5', 'rows' => '5', 'max_win' => '12,345x',
    ],
    'Mahjong Ways 2' => [
        'desc' => 'Mahjong Ways 2 dari PG Soft menggabungkan tile mahjong klasik dengan mekanisme slot modern. Nikmati hingga 324 ways to win, fitur Wild, dan Free Games dengan simbol khusus berhadiah.',
        'year' => '2021', 'reels' => '5', 'rows' => '4', 'max_win' => '5,000x',
    ],
    'Lucky Neko' => [
        'desc' => 'Lucky Neko dari PG Soft menghadirkan kucing keberuntungan Jepang dalam slot 6x5 yang penuh warna. Gigantisk Symbols dan mekanisme respin membuat setiap putaran penuh kejutan.',
        'year' => '2021', 'reels' => '6', 'rows' => '5', 'max_win' => '2,000x',
    ],
    'Aviator' => [
        'desc' => 'Aviator dari Spribe adalah crash game multiplayer revolusioner. Pasang taruhan, pantau pesawat naik, dan cashout sebelum terbang pergi — semakin lama menunggu, semakin besar potensi reward.',
        'year' => '2019', 'reels' => '—', 'rows' => 'Crash', 'max_win' => '∞',
    ],
    'JetX' => [
        'desc' => 'JetX dari SmartSoft Gaming adalah crash game bertema jet tempur. Mirip Aviator namun dengan visual lebih modern dan opsi multi-bet simultan untuk strategi bermain yang lebih fleksibel.',
        'year' => '2020', 'reels' => '—', 'rows' => 'Crash', 'max_win' => '25,000x',
    ],
    'Plinko' => [
        'desc' => 'Plinko adalah game bola jatuh klasik yang kini hadir dalam format kasino digital. Pilih level risiko dan jumlah pin, lalu saksikan bola memantul menuju multiplier di bagian bawah papan.',
        'year' => '2022', 'reels' => '—', 'rows' => 'Instant', 'max_win' => '1,000x',
    ],
    'Mines' => [
        'desc' => 'Mines adalah game strategi instan di mana Anda membuka kotak satu per satu menghindari ranjau. Semakin banyak kotak aman yang dibuka, semakin besar multiplier — tapi kapan harus berhenti?',
        'year' => '2020', 'reels' => '—', 'rows' => 'Instant', 'max_win' => '1,000x',
    ],
    'Spaceman' => [
        'desc' => 'Spaceman dari Pragmatic Play adalah crash game bertema luar angkasa dengan kosmonot melayang. Cashout sebelum astronaut menghilang dan kumpulkan multiplier yang terus meningkat setiap detiknya.',
        'year' => '2022', 'reels' => '—', 'rows' => 'Crash', 'max_win' => '∞',
    ],
    'Buffalo King' => [
        'desc' => 'Buffalo King dari Pragmatic Play membawa tema padang rumput Amerika dengan 4,096 ways to win. Free Spins dengan coin collect dan multiplier liar menjadikan setiap sesi penuh potensi besar.',
        'year' => '2019', 'reels' => '6', 'rows' => '4', 'max_win' => '93,750x',
    ],
    'Sugar Rush' => [
        'desc' => 'Sugar Rush dari Pragmatic Play adalah slot permen 7x7 dengan mekanisme cluster pays dan Tumble. Free Spins hadir dengan multiplier yang terus bertambah — manis tapi bisa sangat menguntungkan.',
        'year' => '2022', 'reels' => '7', 'rows' => '7', 'max_win' => '5,000x',
    ],
];
$game_about = $gameAboutMap[$game_name] ?? [
    'desc' => $game_name . ' dari ' . $provider . ' menghadirkan pengalaman bermain premium dengan RTP live ' . number_format($rtp, 2) . '%. Mainkan sekarang di Royal Knight\'s.',
    'year' => '—', 'reels' => '—', 'rows' => ucfirst($category), 'max_win' => '—',
];

/* Related games */
$related = [];
$stmt = $conn->prepare("
    SELECT * FROM rtp_live
    WHERE id != ? AND (provider = ? OR LOWER(category) = ?)
    ORDER BY rtp DESC
    LIMIT 8
");
$stmt->bind_param('iss', $game_id, $provider, $category);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $related[] = [
        'id'       => $row['id'],
        'name'     => $row['game_name'],
        'provider' => $row['provider'],
        'cat'      => strtolower($row['category'] ?? ''),
        'rtp'      => round((float) $row['rtp'], 2),
        'badge'    => strtolower($row['status'] ?? ''),
        'img'      => $gameImages[$row['game_name']] ?? '../assets/game/default.jpg',
    ];
}

/* All games for search */
$all_games = [];
$res = $conn->query("SELECT * FROM rtp_live ORDER BY rtp DESC");
while ($row = $res->fetch_assoc()) {
    $all_games[] = [
        'id'       => $row['id'],
        'name'     => $row['game_name'],
        'provider' => $row['provider'],
        'cat'      => strtolower($row['category'] ?? ''),
        'rtp'      => round((float) $row['rtp'], 2),
        'badge'    => strtolower($row['status'] ?? ''),
        'img'      => $gameImages[$row['game_name']] ?? '../assets/game/default.jpg',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($game_name) ?> | Royal Knight's</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #FFD700;
    --gold-soft: rgba(255,215,0,.12);
    --gold-border: rgba(255,215,0,.22);
    --bg: #0a0a0c;
    --card: #121216;
    --text: #f5f5f5;
    --muted: #8a8a96;
    --success: #2dff6b;
    --danger: #ff3b5c;
}

* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body {
    background: var(--bg);
    color: var(--text);
    padding-top: 74px;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Ambient orbs */
.bg-orbs {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}
.bg-orbs span {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: .35;
}
.bg-orbs span:nth-child(1) {
    width: 480px; height: 480px;
    background: radial-gradient(circle, rgba(255,215,0,.25), transparent 70%);
    top: -120px; right: -80px;
    animation: orbFloat 18s ease-in-out infinite alternate;
}
.bg-orbs span:nth-child(2) {
    width: 360px; height: 360px;
    background: radial-gradient(circle, rgba(120,80,255,.2), transparent 70%);
    bottom: 10%; left: -100px;
    animation: orbFloat 22s ease-in-out infinite alternate-reverse;
}
.bg-orbs span:nth-child(3) {
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(255,60,100,.15), transparent 70%);
    top: 45%; right: 15%;
    animation: orbFloat 16s ease-in-out infinite alternate;
}
@keyframes orbFloat {
    0%   { transform: translate(0,0) scale(1); }
    100% { transform: translate(-30px, 40px) scale(1.12); }
}

/* ========== HEADER ========== */
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
.logo { width: 138px; max-height: 88px; object-fit: contain; }
.logo img { width: 130px; display: block; position: relative; top: 4px; }

.navbar { flex: 1; display: flex; justify-content: center; align-items: center; }
.nav-menu { display: flex; align-items: center; gap: 14px; height: 100%; }
.nav-menu a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 18px;
    border-radius: 20px;
    color: #e8e8e8;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    line-height: 1;
    letter-spacing: .3px;
    white-space: nowrap;
    border: 1px solid transparent;
    box-sizing: border-box;
    flex-shrink: 0;
    transition: background .25s ease, border-color .25s ease, color .25s ease, box-shadow .25s ease, transform .25s ease;
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
    box-shadow: 0 0 15px rgba(255,215,0,.35), inset 0 1px 0 rgba(255,255,255,.3);
}

.member-area { display: flex; align-items: center; gap: 12px; height: 100%; }
.balance-card {
    display: flex; align-items: center; gap: 10px;
    height: 44px; padding: 0 6px 0 14px;
    background: rgba(255,255,255,.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px; transition: .25s;
}
.balance-card:hover {
    border-color: rgba(255,215,0,.35);
    box-shadow: 0 0 18px rgba(255,215,0,.12);
}
.balance-info { display: flex; flex-direction: column; line-height: 1.1; }
.balance-info .label { font-size: 9px; color: #888; letter-spacing: 1px; text-transform: uppercase; }
.balance-info .amount { font-size: 14px; font-weight: 700; color: #FFD700; }
.btn-deposit {
    height: 34px; padding: 0 14px; border: none; border-radius: 9px;
    background: #FFD700; color: #111; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: .2s;
}
.btn-deposit:hover { background: #ffe24d; transform: translateY(-1px); }

.user-dropdown { position: relative; }
.user-btn {
    display: flex; align-items: center; gap: 10px; cursor: pointer;
    background: #141418; border: 1px solid rgba(255,255,255,.08);
    border-radius: 40px; padding: 5px 16px 5px 5px; transition: all .25s ease;
}
.user-btn:hover { background: rgba(255,255,255,.08); border-color: rgba(255,215,0,.3); }
.avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(145deg, #FFD700, #e6a800);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: #111; font-size: 14px;
    box-shadow: 0 0 0 2px rgba(255,215,0,.2);
    background-size: cover; background-position: center;
}
.user-meta { line-height: 1.25; }
.user-meta .name { font-size: 13px; font-weight: 600; }
.user-meta .level { font-size: 11px; color: var(--gold); font-weight: 500; }

.dropdown-menu {
    position: absolute; top: 56px; right: 0; width: 230px;
    background: #141418; border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px; overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.55); z-index: 100;
    opacity: 0; visibility: hidden;
    transform: translateY(-12px) scale(.96);
    transition: opacity .25s ease, transform .25s ease, visibility .25s;
}
.dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
.dropdown-menu a {
    position: relative; display: flex; align-items: center; gap: 12px;
    padding: 14px 20px; color: #e0e0e8; text-decoration: none; font-size: 13.5px;
    border-bottom: 1px solid rgba(255,255,255,.04);
    transition: color .25s, padding-left .25s, background .25s;
}
.dropdown-menu a::before {
    content: ""; position: absolute; left: 0; top: 0; width: 3px; height: 100%;
    background: #FFD700; transform: scaleY(0); transition: transform .25s;
}
.dropdown-menu a:last-child { border: none; color: var(--danger); }
.dropdown-menu a:last-child::before { background: #ff3b5c; }
.dropdown-menu a:hover { background: rgba(255,215,0,.06); color: #FFD700; padding-left: 28px; }
.dropdown-menu a:hover::before { transform: scaleY(1); }
.dropdown-menu a:last-child:hover { background: rgba(255,59,92,.08); color: var(--danger); }
.dropdown-menu a .ico { width: 18px; height: 18px; flex-shrink: 0; }

.search-btn {
    width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
    border-radius: 50%; background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08); transition: .25s; text-decoration: none;
}
.search-btn:hover { background: #FFD700; border-color: #FFD700; transform: translateY(-2px); }
.search-btn img { width: 18px; height: 18px; filter: brightness(0) invert(1); }
.search-btn:hover img { filter: none; }

/* ========== PAGE ========== */
.page {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 24px 80px;
}

/* Breadcrumb */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 24px; font-size: 13px; color: var(--muted);
}
.breadcrumb a {
    color: var(--muted); text-decoration: none; transition: color .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.breadcrumb a:hover { color: var(--gold); }
.breadcrumb .sep { opacity: .4; }
.breadcrumb .current { color: #e8e8e8; font-weight: 500; }
.breadcrumb .ico { width: 14px; height: 14px; }

/* ========== HERO GAME ========== */
.game-hero {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 28px;
    margin-bottom: 28px;
}

.game-cover {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    aspect-ratio: 3/4;
    background: #15151a;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow:
        0 20px 60px rgba(0,0,0,.45),
        0 0 40px rgba(255,215,0,.06);
}
.game-cover img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .6s ease;
}
.game-cover:hover img { transform: scale(1.05); }
.game-cover::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.75) 0%, transparent 50%);
    pointer-events: none;
}
.game-badge {
    position: absolute; top: 16px; left: 16px; z-index: 3;
    padding: 6px 14px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase;
    backdrop-filter: blur(12px);
    box-shadow: 0 4px 16px rgba(0,0,0,.35);
}
.game-badge.hot {
    background: linear-gradient(135deg, #ff3b5c, #ff1744);
    color: #fff; border: 1px solid rgba(255,255,255,.25);
}
.game-badge.new {
    background: linear-gradient(135deg, #00ff88, #00c853);
    color: #08120c; border: 1px solid rgba(255,255,255,.25);
}
.game-badge.cold {
    background: linear-gradient(135deg, #42a5f5, #1e88e5);
    color: #fff;
}
.game-badge.normal {
    background: linear-gradient(135deg, #7e57c2, #5e35b1) !important;
    color: #fff !important;
}
.fav-btn {
    position: absolute; top: 16px; right: 16px; z-index: 3;
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.5); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.1);
    color: #fff; cursor: pointer; transition: .25s;
}
.fav-btn .ico { width: 20px; height: 20px; }
.fav-btn:hover { background: #FFD700; color: #111; transform: scale(1.08); }
.fav-btn.active { background: #ff3b5c; color: #fff; border-color: transparent; }

/* Info panel — glass */
.game-panel {
    position: relative;
    border-radius: 28px;
    padding: 32px 36px;
    background:
        linear-gradient(145deg, rgba(22,22,28,.75) 0%, rgba(14,14,18,.85) 100%);
    border: 1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(28px);
    -webkit-backdrop-filter: blur(28px);
    box-shadow:
        0 4px 6px rgba(0,0,0,.12),
        0 24px 48px rgba(0,0,0,.3),
        inset 0 1px 0 rgba(255,255,255,.06);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.game-panel::before {
    content: "";
    position: absolute;
    width: 320px; height: 320px;
    right: -100px; top: -80px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,215,0,.12), transparent 70%);
    filter: blur(30px);
    pointer-events: none;
}
.game-panel > * { position: relative; z-index: 2; }

.provider-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 999px;
    background: rgba(255,215,0,.1);
    border: 1px solid rgba(255,215,0,.2);
    color: #FFD700; font-size: 11px; font-weight: 600;
    letter-spacing: .8px; text-transform: uppercase;
    width: fit-content; margin-bottom: 14px;
}
.provider-chip .ico { width: 13px; height: 13px; }

.game-panel h1 {
    font-size: 32px; font-weight: 700;
    letter-spacing: -.4px; line-height: 1.2;
    margin-bottom: 8px; color: #f8f8fa;
}
.game-panel .tagline {
    color: var(--muted); font-size: 14px;
    line-height: 1.55; margin-bottom: 24px; max-width: 480px;
}

/* Stats pills */
.game-stats {
    display: flex; flex-wrap: wrap; gap: 12px;
    margin-bottom: 20px;
}
.stat-glass {
    flex: 1; min-width: 120px;
    padding: 14px 16px;
    border-radius: 16px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    backdrop-filter: blur(12px);
    transition: .3s;
}
.stat-glass:hover {
    border-color: rgba(255,215,0,.25);
    background: rgba(255,215,0,.05);
    transform: translateY(-2px);
}
.stat-glass .lbl {
    font-size: 10.5px; color: #7a7a86;
    letter-spacing: .6px; text-transform: uppercase;
    font-weight: 500; margin-bottom: 6px;
}
.stat-glass .val {
    font-size: 20px; font-weight: 700;
    letter-spacing: -.3px;
}
.stat-glass .val.gold { color: #FFD700; }
.stat-glass .val.green { color: #2dff6b; }
.stat-glass .sub {
    font-size: 11px; color: #666; margin-top: 3px;
}

/* RTP bar large */
.rtp-block {
    margin-bottom: 28px;
}
.rtp-block .rtp-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 10px;
}
.rtp-block .rtp-head span {
    font-size: 12px; color: var(--muted); font-weight: 500;
}
.rtp-block .rtp-head strong {
    font-size: 15px; font-weight: 700;
}
.rtp-track {
    height: 10px; border-radius: 20px;
    background: rgba(255,255,255,.06);
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.04);
}
.rtp-fill {
    height: 100%; width: 0;
    border-radius: 20px;
    transition: width 1.2s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 0 16px currentColor;
}

/* Actions */
.game-actions {
    width: 100%;
    max-width: 580px;
    margin: auto auto 0;
    display: grid;
    grid-template-columns: 1.35fr .9fr .9fr;
    align-items: center;
    gap: 8px;
}

.btn-play {
    width: 100%;
    min-width: 0;
    height: 44px;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    border: none; border-radius: 16px;
    background: linear-gradient(135deg, #FFE566 0%, #FFD700 50%, #E8B000 100%);
    color: #111; font-size: 13px; font-weight: 800;
    cursor: pointer; transition: all .25s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 7px 26px rgba(255,215,0,.34), inset 0 1px 0 rgba(255,255,255,.35);
    text-decoration: none;
}

.btn-play .ico { width: 17px; height: 17px; }
.btn-play:hover {
    transform: translateY(-3px) scale(1.015);
    box-shadow: 0 13px 36px rgba(255,215,0,.5), inset 0 1px 0 rgba(255,255,255,.4);
}

.btn-play:active { transform: translateY(0) scale(1); }

.btn-ghost {
    width: 100%;
    height: 40px; padding: 0 10px;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border-radius: 16px;
    border: 1px solid rgba(255,215,0,.25);
    background: rgba(255,215,0,.06);
    color: #FFD700; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: .25s;
    text-decoration: none;
}
.btn-ghost .ico { width: 16px; height: 16px; }
.btn-ghost:hover {
    background: rgba(255,215,0,.12);
    border-color: rgba(255,215,0,.4);
    transform: translateY(-2px);
}

/* ========== PANEL FEATURES (inside game-panel) ========== */
.panel-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 4px 0 18px;
}
.panel-features-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #8a8a96;
    margin-bottom: 14px;
}
.panel-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 18px;
    margin-bottom: 22px;
}
.panel-ft {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}
.panel-ft .ft-ico {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,215,0,.1);
    border: 1px solid rgba(255,215,0,.18);
    color: #FFD700;
}
.panel-ft .ft-ico .ico { width: 16px; height: 16px; }
.panel-ft .ft-text {
    min-width: 0;
    flex: 1;
}
.panel-ft .ft-text strong {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #eee;
    margin-bottom: 3px;
    line-height: 1.25;
}
.panel-ft .ft-text span {
    display: block;
    font-size: 11.5px;
    color: #888;
    line-height: 1.45;
    letter-spacing: 0;
    word-break: normal;
    overflow-wrap: break-word;
    white-space: normal;
    hyphens: none;
}

/* RTP in panel */
.panel-rtp {
    margin-bottom: 22px;
}
.panel-rtp .rtp-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.panel-rtp .rtp-head span {
    font-size: 12px;
    color: var(--muted);
    font-weight: 500;
}
.panel-rtp .rtp-head strong {
    font-size: 13px;
    font-weight: 700;
}
.panel-rtp .rtp-track {
    height: 8px;
    border-radius: 20px;
    background: rgba(255,255,255,.06);
    overflow: hidden;
}
.panel-rtp .rtp-fill {
    height: 100%;
    width: 0;
    border-radius: 20px;
    transition: width 1.2s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 0 12px currentColor;
}

/* ========== TENTANG GAME ========== */
.about-section {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 24px;
    align-items: start;
    padding: 24px 28px;
    border-radius: 20px;
    background: rgba(18,18,24,.6);
    border: 1px solid rgba(255,255,255,.06);
    margin-bottom: 36px;
}
.about-section h2 {
    font-size: 16px;
    font-weight: 700;
    color: #eee;
    margin-bottom: 10px;
}
.about-section .about-desc {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.6;
    max-width: 560px;
}
.about-meta {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
}
.about-meta .meta-box {
    min-width: 88px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    text-align: center;
}
.about-meta .meta-box .lbl {
    font-size: 10px;
    color: #777;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.about-meta .meta-box .val {
    font-size: 15px;
    font-weight: 700;
    color: #FFD700;
}

/* ========== RELATED ========== */
.section-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 18px;
}
.section-head h2 {
    display: flex; align-items: center; gap: 10px;
    font-size: 20px; font-weight: 700; color: #FFD700;
}
.section-head h2 .ico { width: 22px; height: 22px; }
.section-head a {
    color: var(--muted); font-size: 13px; text-decoration: none; transition: color .2s;
}
.section-head a:hover { color: var(--gold); }

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}
.rel-card {
    position: relative;
    height: 210px;
    border-radius: 18px;
    overflow: hidden;
    cursor: pointer;
    background: #181818;
    border: 1px solid rgba(255,255,255,.05);
    transition: .35s;
    text-decoration: none; color: inherit;
}
.rel-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255,215,0,.35);
    box-shadow: 0 18px 40px rgba(0,0,0,.45), 0 0 25px rgba(255,215,0,.1);
}
.rel-card img {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(.82) saturate(1.05);
    transition: .5s ease;
}
.rel-card:hover img {
    filter: brightness(.45) saturate(.9);
    transform: scale(1.1);
}
.rel-card::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,.2) 55%, transparent 100%);
    z-index: 1;
}
.rel-info {
    position: absolute; left: 12px; right: 12px; bottom: 12px;
    z-index: 2;
}
.rel-info .prov {
    color: #FFD700; font-size: 9px; letter-spacing: 1.2px;
    text-transform: uppercase; margin-bottom: 4px;
}
.rel-info h4 {
    font-size: 13px; color: #fff; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 6px;
}
.rel-rtp {
    font-size: 11px; font-weight: 600;
}

/* ========== PLAY FRAME (fullscreen-ish) ========== */
.play-overlay-fs {
    display: none;
    position: fixed; inset: 0; z-index: 100000;
    background: #050507;
    flex-direction: column;
}
.play-overlay-fs.show { display: flex; }
.play-bar {
    height: 56px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px;
    background: rgba(18,18,22,.9);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.play-bar-left {
    display: flex; align-items: center; gap: 14px;
}
.play-bar-left .thumb {
    width: 36px; height: 36px; border-radius: 10px; object-fit: cover;
}
.play-bar-left .meta strong {
    display: block; font-size: 13px; font-weight: 600;
}
.play-bar-left .meta span {
    font-size: 11px; color: var(--muted);
}
.play-bar-right {
    display: flex; align-items: center; gap: 10px;
}
.play-bar-btn {
    height: 36px; padding: 0 14px;
    display: inline-flex; align-items: center; gap: 6px;
    border-radius: 10px; border: 1px solid rgba(255,255,255,.1);
    background: rgba(255,255,255,.05); color: #ddd;
    font-size: 12.5px; font-weight: 600; cursor: pointer; transition: .2s;
}
.play-bar-btn .ico { width: 14px; height: 14px; }
.play-bar-btn:hover { border-color: rgba(255,215,0,.3); color: #FFD700; }
.play-bar-btn.danger:hover { border-color: rgba(255,59,92,.4); color: #ff3b5c; }
.play-frame-wrap {
    flex: 1; position: relative; background: #0a0a0c;
}
.play-frame-wrap iframe {
    width: 100%; height: 100%; border: none;
}
.play-placeholder {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 16px; text-align: center; padding: 40px;
}
.play-placeholder .ico {
    width: 64px; height: 64px; color: #FFD700; opacity: .7;
}
.play-placeholder h3 { font-size: 20px; font-weight: 700; }
.play-placeholder p { color: var(--muted); font-size: 14px; max-width: 360px; line-height: 1.5; }

/* Toast */
.toast {
    position: fixed; top: 95px; right: 30px;
    display: flex; align-items: center; gap: 14px;
    min-width: 300px; max-width: 400px; padding: 16px 18px;
    background: rgba(20,20,25,.9); backdrop-filter: blur(18px);
    border: 1px solid rgba(255,215,0,.25); border-radius: 18px;
    box-shadow: 0 20px 45px rgba(0,0,0,.45);
    transform: translateX(450px); opacity: 0;
    transition: transform .45s cubic-bezier(.2,.9,.3,1), opacity .35s;
    z-index: 999999;
}
.toast.show { transform: translateX(0); opacity: 1; }
.toast.hide { transform: translateX(450px); opacity: 0; }
.toast-icon {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #FFD700, #ffbf00); color: #111;
    box-shadow: 0 0 16px rgba(255,215,0,.4);
}
.toast-icon .ico { width: 18px; height: 18px; }
.toast-icon.error {
    background: linear-gradient(135deg, #ff3b5c, #ff1744); color: #fff;
    box-shadow: 0 0 16px rgba(255,59,92,.4);
}
.toast-title { color: #fff; font-weight: 700; margin-bottom: 2px; font-size: 14px; }
.toast-message { color: #bcbcbc; font-size: 12.5px; }

/* ===== NAVBAR SEARCH BTN ===== */
.nav-menu a.search-btn {
    width: 42px; height: 42px; padding: 0; border-radius: 50%;
    background: rgba(255,255,255,.05); border: 1px solid transparent;
    color: #aaa; box-shadow: none; outline: none;
}
.nav-menu a.search-btn img {
    width: 18px; height: 18px;
    filter: brightness(0) invert(1);
}
.nav-menu a.search-btn:hover {
    background: #FFD700; border-color: transparent; color: #111;
    transform: translateY(-2px); box-shadow: none;
}
.nav-menu a.search-btn:hover img { filter: none; }

/* Search modal (sama member.php) */
.search-modal {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    display: flex; justify-content: center; align-items: center;
    padding-top: 25px;
    opacity: 0; visibility: hidden; transition: .25s; z-index: 999999;
}
.search-modal.show { opacity: 1; visibility: visible; }
.search-box {
    width: 900px; max-width: 95vw; height: 75vh; max-height: 850px;
    display: flex; flex-direction: column;
    background: #16161c; border-radius: 22px; overflow: hidden;
    border: 1px solid rgba(255,255,255,.08);
}
.search-header {
    display: flex; align-items: center;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.search-header input {
    flex: 1; height: 58px; background: none; border: none;
    padding: 0 20px; color: #fff; font-size: 16px; outline: none;
}
.search-header button {
    width: 58px; height: 58px; border: none; background: none;
    color: #fff; cursor: pointer; font-size: 22px;
    display: flex; align-items: center; justify-content: center;
}
.search-header button .ico { width: 18px; height: 18px; }
.search-back, .search-close {
    width: 58px; height: 58px; border: none; background: none;
    color: #fff; cursor: pointer; font-size: 22px;
    display: flex; align-items: center; justify-content: center;
}
.search-back { display: none; }
.search-modal.searching .search-back { display: flex; }
.search-result { flex: 1; overflow-y: auto; padding: 20px; }
.search-hero {
    width: 100%; padding: 95px 30px; text-align: center;
    background: radial-gradient(circle at top, rgba(255,215,0,.12), transparent 70%),
                linear-gradient(180deg,#1a1a22,#141419);
    border-bottom: 1px solid rgba(255,255,255,.06); border-radius: 16px;
}
.search-hero-icon {
    width: 55px; height: 55px; margin: 0 auto 12px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg,#FFD700,#e6b800); color: #111;
}
.search-hero-icon svg { width: 26px; height: 26px; }
.search-hero h2 { color: #fff; font-size: 21px; margin-bottom: 6px; }
.search-hero p { color: #999; font-size: 13px; margin-bottom: 22px; }
.hero-tags { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; }
.hero-pill {
    padding: 10px 16px; border-radius: 999px; background: #23232d;
    color: #ddd; font-size: 13px; cursor: pointer; transition: .25s;
    display: inline-flex; align-items: center; gap: 6px;
}
.hero-pill svg { width: 14px; height: 14px; }
.hero-pill:hover, .hero-pill.active { background: #FFD700; color: #111; }
.search-grid {
    display: grid; grid-template-columns: repeat(auto-fill,minmax(150px,1fr)); gap: 16px;
}
.search-card {
    background: #1a1a22; border: 1px solid rgba(255,255,255,.06);
    border-radius: 16px; overflow: hidden; cursor: pointer;
    transition: opacity .35s, transform .35s, filter .35s;
    opacity: 0; transform: translateY(20px) scale(.96); filter: blur(6px);
}
.search-card.show { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
.search-card:hover {
    transform: translateY(-5px) scale(1.02); border-color: #FFD700;
    box-shadow: 0 10px 25px rgba(255,215,0,.18);
}
.search-card img { width: 100%; height: 140px; object-fit: cover; display: block; }
.search-card-info { padding: 12px; }
.search-card-info h4 {
    color: #fff; font-size: 14px; margin-bottom: 5px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.search-card-provider { color: #999; font-size: 11px; }
.search-card-rtp { margin-top: 6px; color: #2dff6b; font-size: 12px; font-weight: 600; }
.search-empty { padding: 50px; text-align: center; color: #777; }

/* Generic modals — smooth fade + scale */
.modal {
    position: fixed; inset: 0;
    display: flex; justify-content: center; align-items: center;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    z-index: 100000;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .28s ease, visibility .28s ease;
}
.modal.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.modal .modal-content {
    background: #17171c; border: 1px solid rgba(255,255,255,.08);
    border-radius: 20px; max-height: 85vh; overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.55);
    transform: translateY(14px) scale(.94);
    opacity: 0;
    transition:
        transform .32s cubic-bezier(.22,.7,.3,1),
        opacity .28s ease;
}
.modal.show .modal-content {
    transform: translateY(0) scale(1);
    opacity: 1;
}
.modal .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,.06);
}
.modal .modal-header h3 {
    display: flex; align-items: center; gap: 10px;
    color: #FFD700; font-size: 16px; margin: 0;
}
.modal .modal-header h3 .ico { width: 18px; height: 18px; }
.modal .close {
    font-size: 24px; color: #888; cursor: pointer; line-height: 1;
}
.modal .close:hover { color: #FFD700; }

/* Deposit modal */
.deposit-modal .modal-content {
    width: 320px; max-width: 88%; padding: 18px 16px 16px; position: relative; border-radius: 16px;
}
.deposit-modal .close {
    position: absolute; top: 12px; right: 14px; font-size: 22px; color: #888; cursor: pointer; z-index: 2;
}
.deposit-modal .close:hover { color: #FFD700; }
.deposit-title { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.deposit-title h3 { color: #FFD700; font-size: 16px; font-weight: 700; margin: 0; }
.deposit-title svg { width: 19px; height: 19px; color: #FFD700; }
.deposit-sub { color: #8a8a96; font-size: 10.5px; margin-bottom: 12px; }
.deposit-amount-label { font-size: 10px; color: #8a8a96; font-weight: 500; margin-bottom: 5px; }
.deposit-amount-input { position: relative; margin-bottom: 8px; }
.deposit-amount-input .prefix {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #FFD700; font-weight: 700; font-size: 12px; pointer-events: none;
}
.deposit-amount-input input {
    width: 100%; height: 42px; padding: 0 12px 0 40px; background: #0e0e12;
    border: 1px solid rgba(255,255,255,.08); border-radius: 11px; color: #f5f5f5;
    font-size: 15px; font-weight: 700; outline: none;
}
.deposit-amount-input input:focus {
    border-color: rgba(255,215,0,.22); box-shadow: 0 0 0 3px rgba(255,215,0,.08);
}
.deposit-presets { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-bottom: 12px; }
.deposit-preset {
    height: 32px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03);
    border-radius: 8px; color: #c8c8d0; font-size: 10px; font-weight: 600; cursor: pointer;
}
.deposit-preset:hover { border-color: rgba(255,215,0,.35); color: #FFD700; background: rgba(255,215,0,.06); }
.deposit-preset.active {
    border-color: #FFD700; background: rgba(255,215,0,.12); color: #FFD700;
}
.deposit-method-label { font-size: 10px; color: #8a8a96; font-weight: 500; margin-bottom: 6px; }
.deposit-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 12px; }
.deposit-method {
    display: flex; align-items: center; gap: 7px; padding: 8px 9px;
    border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.025);
    border-radius: 10px; cursor: pointer; user-select: none;
}
.deposit-method:hover { border-color: rgba(255,215,0,.3); background: rgba(255,215,0,.05); }
.deposit-method.active {
    border-color: #FFD700; background: rgba(255,215,0,.1);
}
.deposit-method input { display: none; }
.deposit-method-icon {
    width: 27px; height: 27px; border-radius: 7px; background: rgba(255,215,0,.1);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #FFD700;
}
.deposit-method-icon svg { width: 14px; height: 14px; }
.deposit-method-text strong { display: block; font-size: 10px; font-weight: 600; color: #eee; }
.deposit-method-text span { font-size: 8px; color: #888; }
.deposit-method.active .deposit-method-text strong { color: #FFD700; }
.deposit-actions { display: flex; gap: 6px; margin-top: 4px; }
.deposit-actions .btn-primary, .deposit-actions .btn-outline {
    flex: 1; height: 38px; display: flex; align-items: center; justify-content: center;
    font-size: 11px; border-radius: 9px; border: none; cursor: pointer; font-weight: 600;
}
.deposit-actions .btn-primary { background: linear-gradient(135deg,#FFD700,#e6b800); color: #111; }
.deposit-actions .btn-outline { background: #333; color: #fff; }
.deposit-min-note { text-align: center; font-size: 8.5px; color: #666; margin-top: 8px; }

/* History / Bonus modals */
.history-modal .modal-content, .bonus-modal .modal-content {
    width: 420px; max-width: 94vw;
}
.history-tabs {
    display: flex; gap: 6px; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.06);
}
.history-tab {
    padding: 7px 14px; border-radius: 999px; border: 1px solid rgba(255,255,255,.08);
    background: transparent; color: #999; font-size: 12px; cursor: pointer; font-weight: 500;
}
.history-tab.active { background: rgba(255,215,0,.12); color: #FFD700; border-color: rgba(255,215,0,.3); }
.history-body, .bonus-body {
    padding: 12px 16px 18px; max-height: 55vh; overflow-y: auto;
}
.history-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,.04);
}
.history-item:last-child { border-bottom: none; }
.history-item-icon {
    width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.05); flex-shrink: 0;
}
.history-item-icon svg { width: 16px; height: 16px; }
.history-item-icon.deposit { color: #2dff6b; background: rgba(45,255,107,.1); }
.history-item-icon.withdraw { color: #ff3b5c; background: rgba(255,59,92,.1); }
.history-item-icon.win { color: #2dff6b; background: rgba(45,255,107,.1); }
.history-item-icon.lose { color: #ff3b5c; background: rgba(255,59,92,.1); }
.history-item-info { flex: 1; min-width: 0; }
.history-item-info strong { display: block; font-size: 13px; color: #eee; }
.history-item-info span { font-size: 11px; color: #777; }
.history-item-amount { text-align: right; }
.history-item-amount .val { font-size: 13px; font-weight: 700; display: block; }
.history-item-amount .val.plus { color: #2dff6b; }
.history-item-amount .val.minus { color: #ff3b5c; }
.history-item-amount .status {
    display: inline-block; margin-top: 3px; font-size: 10px; padding: 2px 8px; border-radius: 999px;
}
.history-item-amount .status.ok { background: rgba(45,255,107,.12); color: #2dff6b; }
.history-item-amount .status.pending { background: rgba(255,215,0,.12); color: #FFD700; }
.history-item-amount .status.fail { background: rgba(255,59,92,.12); color: #ff3b5c; }
.history-empty { text-align: center; padding: 40px 16px; color: #777; }
.history-empty strong { display: block; color: #aaa; margin-bottom: 6px; }
.bonus-card {
    background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px; padding: 14px; margin-bottom: 10px;
}
.bonus-card-top { display: flex; gap: 12px; margin-bottom: 12px; }
.bonus-card-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(145deg,#FFE566,#FFD700); color: #111;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.bonus-card-icon svg { width: 18px; height: 18px; }
.bonus-card-info .tag {
    display: inline-block; font-size: 9px; font-weight: 700; letter-spacing: 1px;
    color: #FFD700; margin-bottom: 4px;
}
.bonus-card-info h4 { font-size: 14px; color: #fff; margin-bottom: 4px; }
.bonus-card-info p { font-size: 12px; color: #888; }
.bonus-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.bonus-card-meta .expiry { font-size: 11px; color: #666; }
.btn-claim-sm {
    height: 30px; padding: 0 14px; border: none; border-radius: 8px;
    background: linear-gradient(135deg,#FFD700,#e6b800); color: #111;
    font-size: 11px; font-weight: 700; cursor: pointer;
}
.btn-claim-sm.btn-claimed { background: #333; color: #888; cursor: default; }

/* Logout modal */
.logout-modal {
    position: fixed; inset: 0;
    display: flex; justify-content: center; align-items: center;
    background: rgba(0,0,0,.45); backdrop-filter: blur(10px);
    opacity: 0; visibility: hidden; transition: .3s; z-index: 99999;
}
.logout-modal.show { opacity: 1; visibility: visible; }
.logout-box {
    width: 360px; max-width: 92%;
    background: #17171c; border: 1px solid rgba(255,215,0,.25);
    border-radius: 20px; padding: 28px; text-align: center;
}
.logout-box h3 {
    color: #FFD700; margin-bottom: 12px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.logout-box h3 .ico { width: 20px; height: 20px; }
.logout-box p { color: #bbb; margin-bottom: 24px; font-size: 14px; }
.logout-actions { display: flex; gap: 12px; }
.logout-actions button {
    flex: 1; height: 42px; border: none; border-radius: 10px;
    cursor: pointer; font-weight: 600; font-size: 13.5px;
}
.cancel-btn { background: #333; color: #fff; }
.logout-btn { background: #ff3b5c; color: #fff; }
.logout-btn:hover { background: #ff2147; }

footer {
    text-align: center; padding: 32px 20px;
    background: #0c0c0e; color: #555; font-size: 13px;
    border-top: 1px solid rgba(255,255,255,.04);
    position: relative; z-index: 2;
}

.ico { display: inline-block; vertical-align: -0.15em; flex-shrink: 0; }

@media (max-width: 900px) {
    header { padding: 0 16px; }
    .nav-menu { display: none; }
    .game-hero { grid-template-columns: 1fr; }
    .game-cover { max-width: 280px; margin: 0 auto; aspect-ratio: 3/4; }
    .game-panel { padding: 24px 20px; }
    .game-panel h1 { font-size: 24px; }
    .panel-features { grid-template-columns: repeat(2, 1fr); }
    .about-section { grid-template-columns: 1fr; }
    .about-meta { flex-wrap: wrap; }
}
@media (max-width: 560px) {
    .game-stats { flex-direction: column; }
    .game-actions {
        width: 100%;
        max-width: 420px;
        grid-template-columns: 1fr;
        margin-left: auto;
        margin-right: auto;
    }
    .btn-play, .btn-ghost { width: 100%; }
    .related-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .panel-features { grid-template-columns: 1fr 1fr; gap: 12px; }
}
.stat-glass .val.gold { color: #FFD700; }
.stat-glass .val.green { color: #2dff6b; }
.stat-glass .val.volatility {
    font-size: 20px;
    font-weight: 700;
}

.stat-glass .val.volatility.low {
    color: #2dff6b;
}

.stat-glass .val.volatility.medium {
    color: #FFD700;
}

.stat-glass .val.volatility.high {
    color: #a855f7;
}

.stat-glass .val.volatility.very-high {
    color: #ff3b5c;
}

.stat-glass .val.volatility.variable {
    color: #38bdf8;
}
</style>
</head>
<body>

<div class="bg-orbs">
    <span></span><span></span><span></span>
</div>

<!-- HEADER -->
<header>
    <div class="logo">
        <a href="member.php">
            <img src="../assets/logo/rk.png" alt="Royal Knight's">
        </a>
    </div>

    <nav class="navbar">
        <div class="nav-menu">
            <a href="member.php">Lobby</a>
            <a href="game-list.php" class="active">Games</a>
            <a href="#">Promosi</a>
            <a href="#">VIP</a>
            <a href="#">Kontak</a>
            <a href="#" onclick="openSearch(event)" class="search-btn">
                <img src="../assets/icons/find.png" alt="">
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
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profil Saya
                </a>
                <a href="#" onclick="openHistoryModal(event)">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Riwayat Transaksi
                </a>
                <a href="#" onclick="openBetHistoryModal(event)">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>
                    Riwayat Taruhan
                </a>
                <a href="#" onclick="openBonusModal(event)">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                    Bonus &amp; Promo
                </a>
                <a href="settings.php">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Pengaturan
                </a>
                <a href="#" onclick="confirmLogout(event)">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<div id="toast" class="toast">
    <div class="toast-icon" id="toastIcon">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="toast-content">
        <div class="toast-title">Berhasil</div>
        <div class="toast-message"></div>
    </div>
</div>

<div class="page">

    <nav class="breadcrumb">
        <a href="member.php">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Lobby
        </a>
        <span class="sep">/</span>
        <a href="game-list.php"><?= htmlspecialchars(ucfirst($category)) ?></a>
        <span class="sep">/</span>
        <span class="current"><?= htmlspecialchars($game_name) ?></span>
    </nav>

    <!-- HERO -->
    <section class="game-hero">

        <div class="game-cover">
            <?php
              $badge_cls = $status ?: 'normal';
              if ($badge_cls === 'null') $badge_cls = 'normal';
            ?>
            <div class="game-badge <?= htmlspecialchars($badge_cls) ?>"><?= strtoupper(htmlspecialchars($badge_cls)) ?></div>
            <button type="button" class="fav-btn" id="favBtn" onclick="toggleFav(this)" title="Favorit">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <img src="<?= htmlspecialchars($game_img) ?>" alt="<?= htmlspecialchars($game_name) ?>">
        </div>

        <div class="game-panel">
            <div class="provider-chip">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                <?= htmlspecialchars($provider) ?>
            </div>

            <h1><?= htmlspecialchars($game_name) ?></h1>
            <p class="tagline">
                Siap merasakan sensasi kemenangan? Mainkan sekarang dengan RTP live
                <strong style="color:<?= $rtp_color ?>"><?= number_format($rtp, 2) ?>%</strong>
                dan rasakan pengalaman premium Royal Knight's.
            </p>

            <div class="game-stats">
                <div class="stat-glass">
                    <div class="lbl">RTP Live</div>
                    <div class="val" style="color:<?= $rtp_color ?>"><?= number_format($rtp, 2) ?>%</div>
                    <div class="sub"><?= $rtp_label ?></div>
                </div>
                <div class="stat-glass">
                    <div class="lbl">Kategori</div>
                    <div class="val gold"><?= htmlspecialchars(ucfirst($category)) ?></div>
                    <div class="sub">Game Type</div>
                </div>
                <div class="stat-glass">
                    <div class="lbl">Volatility</div>
                    <div class="val volatility <?= strtolower(str_replace(' ', '-', $volatility)) ?>">
                        <?= htmlspecialchars($volatility) ?>
                    </div>
                    <div class="sub">
                        <?php
                        switch (strtolower($volatility)) {
                            case 'low': echo 'Frekuensi kemenangan lebih stabil'; break;
                            case 'medium': echo 'Keseimbangan risiko dan kemenangan'; break;
                            case 'high': echo 'Tingkat volatilitas tinggi'; break;
                            case 'very high': echo 'Potensi besar dengan risiko sangat tinggi'; break;
                            case 'variable': echo 'Volatilitas dapat berubah'; break;
                            default: echo 'Informasi volatilitas game';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="panel-divider"></div>
            <div class="panel-features-label">Fitur Unggulan</div>
            <div class="panel-features">
                <?php foreach ($game_features as $ft):
                    $iconKey = $ft['icon'] ?? 'star';
                    $iconSvg = $SVG[$iconKey] ?? $SVG['star'];
                ?>
                <div class="panel-ft">
                    <div class="ft-ico"><?= $iconSvg ?></div>
                    <div class="ft-text">
                        <strong><?= htmlspecialchars($ft['title']) ?></strong>
                        <span><?= htmlspecialchars($ft['desc']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="panel-rtp">
                <div class="rtp-head">
                    <span>Return to Player</span>
                    <strong style="color:<?= $rtp_color ?>"><?= number_format($rtp, 2) ?>%</strong>
                </div>
                <div class="rtp-track">
                    <div class="rtp-fill" id="rtpFill" data-width="<?= min(100, $rtp) ?>" style="background:<?= $rtp_color ?>;color:<?= $rtp_color ?>"></div>
                </div>
            </div>

            <div class="game-actions">
                <button type="button" class="btn-play" onclick="startPlay()">
                    <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    Main Sekarang
                </button>
                <button type="button" class="btn-ghost" onclick="startDemo()">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/></svg>
                    Mode Demo
                </button>
                <a href="game-list.php" class="btn-ghost">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Kembali
                </a>
            </div>
        </div>
    </section>

    <!-- TENTANG GAME -->
    <div class="about-section">
        <div>
            <h2>Tentang Game</h2>
            <p class="about-desc"><?= htmlspecialchars($game_about['desc']) ?></p>
        </div>
        <div class="about-meta">
            <div class="meta-box">
                <div class="lbl">Rilis</div>
                <div class="val"><?= htmlspecialchars($game_about['year']) ?></div>
            </div>
            <div class="meta-box">
                <div class="lbl">Reel</div>
                <div class="val"><?= htmlspecialchars($game_about['reels']) ?></div>
            </div>
            <div class="meta-box">
                <div class="lbl">Baris</div>
                <div class="val"><?= htmlspecialchars($game_about['rows']) ?></div>
            </div>
            <div class="meta-box">
                <div class="lbl">Maks Win</div>
                <div class="val"><?= htmlspecialchars($game_about['max_win']) ?></div>
            </div>
        </div>
    </div>

    <!-- RELATED -->
    <?php if (count($related) > 0): ?>
    <div class="section-head">
        <h2>
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg>
            Game Serupa
        </h2>
        <a href="game-list.php">Lihat Semua →</a>
    </div>

    <div class="related-grid">
        <?php foreach ($related as $r):
            $rc = $r['rtp'] >= 96 ? '#2dff6b' : ($r['rtp'] >= 90 ? '#FFD700' : '#ff3b5c');
        ?>
        <a class="rel-card" href="game.php?id=<?= (int)$r['id'] ?>">
            <img src="<?= htmlspecialchars($r['img']) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
            <div class="rel-info">
                <div class="prov"><?= htmlspecialchars($r['provider']) ?></div>
                <h4><?= htmlspecialchars($r['name']) ?></h4>
                <div class="rel-rtp" style="color:<?= $rc ?>">RTP <?= number_format($r['rtp'], 2) ?>%</div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- FULLSCREEN PLAY -->
<div class="play-overlay-fs" id="playOverlay">
    <div class="play-bar">
        <div class="play-bar-left">
            <img class="thumb" src="<?= htmlspecialchars($game_img) ?>" alt="">
            <div class="meta">
                <strong><?= htmlspecialchars($game_name) ?></strong>
                <span><?= htmlspecialchars($provider) ?> · <span id="playModeLabel">Real Play</span></span>
            </div>
        </div>
        <div class="play-bar-right">
            <button type="button" class="play-bar-btn" onclick="toggleFullscreen()">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                Fullscreen
            </button>
            <button type="button" class="play-bar-btn danger" onclick="closePlay()">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Tutup
            </button>
        </div>
    </div>
    <div class="play-frame-wrap" id="playFrameWrap">
        <div class="play-placeholder" id="playPlaceholder">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M8 10v4M12 9v6M16 10v4"/></svg>
            <h3 id="placeholderTitle">Memuat permainan...</h3>
            <p id="placeholderDesc">Integrasikan URL game provider Anda di sini (iframe). Sementara ini tampilan placeholder siap pakai.</p>
            <button type="button" class="btn-play" style="max-width:220px;margin-top:8px" onclick="closePlay()">
                Kembali ke Detail
            </button>
        </div>
        <!-- 
            Ganti src iframe dengan launch URL provider, contoh:
            <iframe id="gameFrame" src="https://provider.com/launch?game=...&token=..." allowfullscreen></iframe>
        -->
    </div>
</div>

<!-- SEARCH -->
<div class="search-modal" id="searchModal">
    <div class="search-box" role="dialog" aria-modal="true" aria-label="Cari game">
        <div class="search-header">
            <button type="button" class="search-back" onclick="backSearch()" aria-label="Kembali">←</button>
            <input type="text" id="popupSearch" placeholder="Cari permainan..." autocomplete="off">
            <button type="button" class="search-close" onclick="closeSearch()" aria-label="Tutup">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="search-result" id="searchResult"></div>
    </div>
</div>

<!-- DEPOSIT MODAL -->
<div id="depositModal" class="modal deposit-modal">
  <div class="modal-content">
    <span class="close" onclick="closeDepositModal()">&times;</span>
    <div class="deposit-title">
      <svg viewBox="0 0 24 24" fill="none">
        <rect x="3" y="6" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/>
        <path d="M3 10h18" stroke="currentColor" stroke-width="2"/>
        <path d="M12 13v4M10 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
          <div class="deposit-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg></div>
          <div class="deposit-method-text"><strong>Transfer Bank</strong><span>BCA · Mandiri · BNI</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="ewallet">
          <div class="deposit-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></div>
          <div class="deposit-method-text"><strong>E-Wallet</strong><span>DANA · OVO · GoPay</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="qris">
          <div class="deposit-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M17 17h3v3h-3z"/><path d="M14 20h3"/></svg></div>
          <div class="deposit-method-text"><strong>QRIS</strong><span>Scan &amp; bayar instan</span></div>
        </label>
        <label class="deposit-method">
          <input type="radio" name="method" value="pulsa">
          <div class="deposit-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20h.01"/><path d="M7 20v-4"/><path d="M12 20v-8"/><path d="M17 20V8"/><path d="M22 4v16"/></svg></div>
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
      <h3><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Riwayat Transaksi</h3>
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
      <h3><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/></svg> Riwayat Taruhan</h3>
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
      <h3><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/></svg> Bonus &amp; Promo</h3>
      <span class="close" onclick="closeBonusModal()">&times;</span>
    </div>
    <div class="bonus-body" id="bonusBody"></div>
  </div>
</div>

<!-- LOGOUT -->
<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <h3>
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="logout-actions">
            <button type="button" class="cancel-btn" onclick="closeLogout()">Batal</button>
            <button type="button" class="logout-btn" onclick="logoutNow()">Keluar</button>
        </div>
    </div>
</div>

<footer>
    © 2026 Royal Knight's • Bermain secara bertanggung jawab • 18+
</footer>

<script>
const games = <?= json_encode($all_games, JSON_UNESCAPED_UNICODE) ?>;
const GAME_ID = <?= (int)$game_id ?>;
const GAME_NAME = <?= json_encode($game_name, JSON_UNESCAPED_UNICODE) ?>;

/* RTP animate */
requestAnimationFrame(() => {
    const bar = document.getElementById('rtpFill');
    if (bar) bar.style.width = bar.dataset.width + '%';
});

window.addEventListener('scroll', () => {
    document.querySelector('header')?.classList.toggle('scrolled', window.scrollY > 20);
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
    if (e) e.preventDefault();
    document.getElementById('userMenu')?.classList.remove('show');
    document.getElementById('logoutModal').classList.add('show');
}
function closeLogout() {
    document.getElementById('logoutModal').classList.remove('show');
}
function logoutNow() {
    window.location.href = '/casino/member/logout.php';
}
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

// ===== FAVORITE SYSTEM (DATABASE = SOURCE OF TRUTH) =====
const HEART_EMPTY = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
const HEART_FULL = `<svg class="ico" viewBox="0 0 24 24" fill="#ff3b5c" stroke="#ff3b5c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
const FAVORITE_INITIAL = <?= $is_favorite ? 'true' : 'false' ?>;

function initFavButton() {
    const btn = document.getElementById('favBtn');
    if (!btn) return;
    btn.classList.toggle('active', FAVORITE_INITIAL);
    btn.innerHTML = FAVORITE_INITIAL ? HEART_FULL : HEART_EMPTY;
}

function toggleFav(btn) {
    if (!btn || btn.dataset.busy === '1') return;
    const id = Number(GAME_ID);
    const wasFavorite = btn.classList.contains('active');
    btn.dataset.busy = '1';
    btn.disabled = true;

    fetch('/casino/member/favorite.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        credentials: 'same-origin',
        body: 'action=toggle&game_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Gagal mengupdate favorit');
        const active = data.action === 'added';
        btn.classList.toggle('active', active);
        btn.innerHTML = active ? HEART_FULL : HEART_EMPTY;
        showToast(active ? '❤️' : '💔',
            active ? GAME_NAME + ' ditambahkan ke favorit!' : GAME_NAME + ' dihapus dari favorit.',
            'success');
    })
    .catch(error => {
        btn.classList.toggle('active', wasFavorite);
        btn.innerHTML = wasFavorite ? HEART_FULL : HEART_EMPTY;
        showToast('Error', error.message || 'Gagal terhubung ke server', 'error');
    })
    .finally(() => {
        btn.dataset.busy = '0';
        btn.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', initFavButton);

function showToast(title, message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    toast.querySelector('.toast-title').innerText = title;
    toast.querySelector('.toast-message').innerText = message;
    if (type === 'error') {
        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        icon.classList.add('error');
    } else {
        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
        icon.classList.remove('error');
    }
    toast.classList.remove('hide');
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
    }, 2800);
}

/* Play */
function startPlay() {
    document.getElementById('playModeLabel').textContent = 'Real Play';
    document.getElementById('placeholderTitle').textContent = 'Mode Real Play';
    document.getElementById('placeholderDesc').textContent =
        'Hubungkan launch URL provider (dengan token user) ke iframe. Placeholder ini siap diganti.';
    document.getElementById('playOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function startDemo() {
    document.getElementById('playModeLabel').textContent = 'Demo';
    document.getElementById('placeholderTitle').textContent = 'Mode Demo';
    document.getElementById('placeholderDesc').textContent =
        'Mode demo tanpa taruhan nyata. Ganti iframe src dengan demo URL provider Anda.';
    document.getElementById('playOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closePlay() {
    document.getElementById('playOverlay').classList.remove('show');
    document.body.style.overflow = '';
    if (document.fullscreenElement) document.exitFullscreen?.();
}
function toggleFullscreen() {
    const el = document.getElementById('playOverlay');
    if (!document.fullscreenElement) {
        el.requestFullscreen?.() || el.webkitRequestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
}

/* Search */
function playGame(id) {
    window.location.href = 'game.php?id=' + id;
}
function openSearch(e) {
    if (e && e.preventDefault) e.preventDefault();
    const modal = document.getElementById('searchModal');
    modal.classList.add('show');
    modal.classList.remove('searching');
    const input = document.getElementById('popupSearch');
    input.value = '';
    renderSearch(games);
    setTimeout(() => input.focus(), 80);
}
function closeSearch() {
    document.getElementById('searchModal').classList.remove('show');
}
function backSearch() {
    const input = document.getElementById('popupSearch');
    input.value = '';
    const modal = document.getElementById('searchModal');
    modal.classList.remove('searching');
    renderSearch(games);
    document.querySelectorAll('.hero-pill').forEach(x => x.classList.remove('active'));
    input.focus();
}
document.getElementById('searchModal').addEventListener('click', function(e) {
    if (e.target === this) closeSearch();
});
document.getElementById('popupSearch').addEventListener('input', function() {
    const modal = document.getElementById('searchModal');
    const key = this.value.toLowerCase().trim();
    if (key.length > 0) modal.classList.add('searching');
    else modal.classList.remove('searching');
    renderSearch(
        key
            ? games.filter(g =>
                g.name.toLowerCase().includes(key) ||
                (g.provider || '').toLowerCase().includes(key)
              )
            : games
    );
});
function renderSearch(list) {
    const box = document.getElementById('searchResult');
    const input = document.getElementById('popupSearch');
    if (input.value === '' && list.length === games.length) {
        box.innerHTML = `
      <div class="search-hero">
        <div class="search-hero-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg>
        </div>
        <h2>Temukan Kemenangan Terbesarmu</h2>
        <p>Jelajahi <strong>${games.length}+</strong> permainan dari provider terbaik dunia.</p>
        <div class="hero-tags">
          <div class="hero-pill" data-cat="slots"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="8" y2="16"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="16" y1="8" x2="16" y2="16"/></svg> Slots</div>
          <div class="hero-pill" data-cat="live"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg> Live</div>
          <div class="hero-pill" data-cat="crash"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg> Crash</div>
          <div class="hero-pill" data-cat="table"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="5" y="3" width="12" height="16" rx="2"/><path d="M9 3v16"/></svg> Table</div>
          <div class="hero-pill" data-cat="all"><svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 23c-4.4 0-8-3-8-7.5 0-2.5 1.2-4.7 3-6.2.5 2.2 1.8 3.4 3 4-1-4 2-8 5-10 1 3 3 5 5 6.5 1.5 1.2 2.5 2.8 2.5 5.2C22.5 20 18.4 23 12 23z"/></svg> Semua</div>
        </div>
      </div>`;
        return;
    }
    if (!list.length) {
        box.innerHTML = '<div class="search-empty"><h3>Tidak ada game ditemukan</h3><p>Coba gunakan kata kunci lain.</p></div>';
        return;
    }
    box.innerHTML = `<div class="search-grid">${list.slice(0, 60).map(g => `
        <div class="search-card" onclick="playGame(${g.id})">
            <img src="${g.img}" alt="" loading="lazy">
            <div class="search-card-info">
                <h4>${g.name}</h4>
                <div class="search-card-provider">${g.provider || ''}</div>
                <div class="search-card-rtp">RTP ${Number(g.rtp).toFixed(2)}%</div>
            </div>
        </div>`).join('')}</div>`;
    document.querySelectorAll('.search-card').forEach((card, i) => {
        setTimeout(() => card.classList.add('show'), i * 45);
    });
}
document.addEventListener('click', function(e) {
    const pill = e.target.closest('.hero-pill');
    if (!pill) return;
    const cat = pill.dataset.cat;
    document.getElementById('popupSearch').value = '';
    document.querySelectorAll('.hero-pill').forEach(x => x.classList.remove('active'));
    pill.classList.add('active');
    if (cat === 'all') renderSearch(games);
    else renderSearch(games.filter(g => (g.cat || '') === cat));
});

function closeUserMenu() {
    document.getElementById('userMenu')?.classList.remove('show');
}
function fmtRp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
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
function openHistoryModal(e) {
  if (e) e.preventDefault();
  closeUserMenu();
  renderTransactions('all');
  document.getElementById('historyModal').classList.add('show');
}
function closeHistoryModal() { document.getElementById('historyModal').classList.remove('show'); }
function openBetHistoryModal(e) {
  if (e) e.preventDefault();
  closeUserMenu();
  renderBets('all');
  document.getElementById('betHistoryModal').classList.add('show');
}
function closeBetHistoryModal() { document.getElementById('betHistoryModal').classList.remove('show'); }
function openBonusModal(e) {
  if (e) e.preventDefault();
  closeUserMenu();
  renderBonuses();
  document.getElementById('bonusModal').classList.add('show');
}
function closeBonusModal() { document.getElementById('bonusModal').classList.remove('show'); }
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
      ? '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>'
      : '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
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
    return `<div class="history-item">
      <div class="history-item-icon ${isWin ? 'win' : 'lose'}"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/></svg></div>
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
        <div class="bonus-card-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/></svg></div>
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
function openDepositModal() {
  const modal = document.getElementById('depositModal');
  modal.classList.add('show');
  const amountInput = document.getElementById('depositAmount');
  amountInput.value = '';
  document.querySelectorAll('.deposit-preset').forEach(b => b.classList.remove('active'));
  setTimeout(() => amountInput.focus(), 180);
}
function closeDepositModal() {
  document.getElementById('depositModal')?.classList.remove('show');
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
window.addEventListener('click', function(e) {
  if (e.target === document.getElementById('historyModal')) closeHistoryModal();
  if (e.target === document.getElementById('betHistoryModal')) closeBetHistoryModal();
  if (e.target === document.getElementById('bonusModal')) closeBonusModal();
  if (e.target === document.getElementById('depositModal')) closeDepositModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeSearch();
        closePlay();
        closeLogout();
        closeDepositModal();
        closeHistoryModal();
        closeBetHistoryModal();
        closeBonusModal();
    }
});
</script>
</body>
</html>