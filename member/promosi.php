<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Support both session keys (login flag / user id)
if (!isset($_SESSION['login']) && !isset($_SESSION['id'])) {
    header("Location: /casino/auth/login.php");
    exit;
}

$user_id = $_SESSION['id'] ?? null;

if (!$user_id && isset($_SESSION['login']) && is_numeric($_SESSION['login'])) {
    $user_id = (int) $_SESSION['login'];
}

$user = null;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT
            id, username, fullname, email, phone, avatar,
            balance, level, points, total_deposit, total_withdraw
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} elseif (!empty($_SESSION['username'])) {
    $uname = $_SESSION['username'];
    $stmt = $conn->prepare("
        SELECT
            id, username, fullname, email, phone, avatar,
            balance, level, points, total_deposit, total_withdraw
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if (!$user) {
    session_destroy();
    header("Location: /casino/auth/login.php");
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
$avatar_url  = $avatar_file ? "../uploads/avatars/" . $avatar_file : null;

/* ========== GAMES (untuk search) ========== */
$games = [];
$result = $conn->query("SELECT * FROM rtp_live ORDER BY rtp DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $games[] = [
            "id"       => $row["id"],
            "name"     => $row["game_name"],
            "provider" => $row["provider"],
            "cat"      => strtolower($row["category"]),
            "rtp"      => round($row["rtp"], 2),
            "badge"    => strtolower($row["status"]),
            "trend"    => $row["trend"]
        ];
    }
}

/* ========== DATA PROMO ========== */
$promos = [
    [
        "id" => 1,
        "tag" => "WELCOME",
        "title" => "Bonus Deposit 100%",
        "desc" => "Bonus 100% untuk deposit pertama. Min Rp50.000 · Maks bonus Rp1.000.000",
        "terms" => "Berlaku untuk member baru. Turnover 10x. Hanya untuk game slot.",
        "expiry" => "Berlaku hingga 31 Agu 2026",
        "badge" => "hot",
        "claimed" => false,
        "cat" => "deposit"
    ],
    [
        "id" => 2,
        "tag" => "HARIAN",
        "title" => "Cashback 10%",
        "desc" => "Cashback kekalahan slot setiap hari, maks Rp500.000",
        "terms" => "Dihitung dari net loss harian. Minimal loss Rp100.000.",
        "expiry" => "Reset setiap hari 00:00 WIB",
        "badge" => "hot",
        "claimed" => false,
        "cat" => "cashback"
    ],
    [
        "id" => 3,
        "tag" => "MINGGUAN",
        "title" => "Reload Bonus 50%",
        "desc" => "Bonus reload setiap Senin, min deposit Rp100.000",
        "terms" => "Hanya berlaku setiap hari Senin. Maks bonus Rp500.000. Turnover 8x.",
        "expiry" => "Berlaku Senin saja",
        "badge" => "normal",
        "claimed" => true,
        "cat" => "deposit"
    ],
    [
        "id" => 4,
        "tag" => "VIP",
        "title" => "Extra Spin 50x",
        "desc" => "Free spin 50x untuk member Gold ke atas",
        "terms" => "Khusus level Gold, Platinum, Diamond. Berlaku 3 hari setelah klaim.",
        "expiry" => "Berlaku 3 hari setelah klaim",
        "badge" => "exclusive",
        "claimed" => false,
        "cat" => "vip"
    ],
    [
        "id" => 5,
        "tag" => "SLOT",
        "title" => "Turnamen Slot Mingguan",
        "desc" => "Total hadiah Rp250.000.000 setiap minggu dari Pragmatic Play",
        "terms" => "Minimal taruhan Rp10.000. Leaderboard real-time. Hadiah dibagikan setiap Senin.",
        "expiry" => "Berakhir setiap Minggu 23:59",
        "badge" => "limited",
        "claimed" => false,
        "cat" => "tournament"
    ],
    [
        "id" => 6,
        "tag" => "LIVE",
        "title" => "Cashback Live Casino 5%",
        "desc" => "Cashback 5% untuk Live Casino (Blackjack, Baccarat, Roulette)",
        "terms" => "Dihitung dari net loss. Maks Rp300.000/hari.",
        "expiry" => "Berlaku setiap hari",
        "badge" => "new",
        "claimed" => false,
        "cat" => "cashback"
    ],
    [
        "id" => 7,
        "tag" => "REFERRAL",
        "title" => "Bonus Referral 10%",
        "desc" => "Ajak teman, dapat 10% dari deposit pertama mereka",
        "terms" => "Teman harus deposit min Rp100.000. Bonus langsung masuk saldo.",
        "expiry" => "Tidak ada batas waktu",
        "badge" => "normal",
        "claimed" => false,
        "cat" => "referral"
    ],
    [
        "id" => 8,
        "tag" => "CRASH",
        "title" => "Crash Game Bonus 20%",
        "desc" => "Bonus 20% khusus untuk Aviator, JetX, dan Spaceman",
        "terms" => "Min deposit Rp50.000. Maks bonus Rp200.000. Turnover 5x.",
        "expiry" => "Berlaku hingga 20 Agu 2026",
        "badge" => "limited",
        "claimed" => false,
        "cat" => "deposit"
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promosi | Royal Knight's</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #FFD700;
    --gold-soft: rgba(255,215,0,.12);
    --gold-border: rgba(255,215,0,.22);
    --bg: #0a0a0c;
    --card: #121216;
    --card-hover: #16161c;
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
}

/* ========== HEADER ========== */
header{
    position:fixed;
    top:0;
    left:0;
    right:0;
    height:72px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 30px;

    /* lebih solid, biar tidak tembus */
    background:rgba(14,14,18,.94);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);

    border-bottom:1px solid rgba(255,255,255,.08);
    z-index:9999;
    overflow:visible; /* cegah logo/glow bocor */
    transition: background .3s, border-color .3s;
}
header.scrolled{
    background:rgba(10,10,10,.96);
    border-bottom:1px solid rgba(255,255,255,.1); /* tanpa emas */
}

/* Logo — sama ukuran & posisi seperti member.php */
.logo{
    width:138px;
    max-height:88px;
    object-fit:contain;
    filter: drop-shadow(0 2px 8px rgba(255,215,0,.15));
}

.logo img{
    width:130px;
    display:block;
    position:relative;
    top:4px;   /* turunkan logo */
}

.navbar{ flex:1; display:flex; justify-content:center; align-items:center; }
.nav-menu{ display:flex; align-items:center; gap:14px; height:100%; }
.nav-menu a{
    display:flex; align-items:center; justify-content:center;
    height:42px; padding:0 18px; border-radius:20px;
    color:#e8e8e8; text-decoration:none; font-size:14px; font-weight:600; letter-spacing:.3px;
    border:1px solid transparent; transition:.25s ease;
}
.nav-menu a:hover{
    background:#202020; border-color:rgba(255,215,0,.25); color:#FFD700; transform:translateY(-2px);
}
.nav-menu a.active{
    background:linear-gradient(135deg,#FFD700,#f0c000);
    color:#111;
    font-weight:700;
    border:1px solid #FFD700;

    box-shadow:
        0 0 6px rgba(255,215,0,.45),
        0 0 16px rgba(255,215,0,.28),
        inset 0 1px 0 rgba(255,255,255,.35);

    text-shadow:none;
}

.search-btn{
    width:42px; height:42px;
    display:flex; align-items:center; justify-content:center;
    border-radius:50%; background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08); transition:.25s;
}
.search-btn img{
    width:18px; height:18px;
    filter:brightness(0) invert(1);
}
.search-btn:hover{
    background:#FFD700; border-color:#FFD700; transform:translateY(-2px);
}
.search-btn:hover img{ filter:none; }

.member-area{ display:flex; align-items:center; justify-self:end; gap:12px; height:100%; }

.balance-card{
    display:flex; align-items:center; gap:10px;
    height:44px; padding:0 6px 0 14px;
    background:rgba(255,255,255,.05); backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.08); border-radius:12px; transition:.25s;
}
.balance-card:hover{ border-color:rgba(255,215,0,.35); box-shadow:0 0 18px rgba(255,215,0,.12); }
.balance-info{ display:flex; flex-direction:column; line-height:1.1; }
.balance-info .label{ font-size:9px; color:#888; letter-spacing:1px; text-transform:uppercase; }
.balance-info .amount{ font-size:14px; font-weight:700; color:#FFD700; }

.btn-deposit{
    height:34px; padding:0 14px; border:none; border-radius:9px;
    background:#FFD700; color:#111; font-size:12px; font-weight:700; cursor:pointer; transition:.2s;
}
.btn-deposit:hover{ background:#ffe24d; transform:translateY(-1px); }

.user-dropdown {
    position:relative;
    z-index:10001;
}
.user-btn {
    display:flex; align-items:center; gap:10px; cursor:pointer;
    background: #141418; border: 1px solid rgba(255,255,255,.08);
    border-radius: 40px; padding: 5px 16px 5px 5px; transition: all .25s ease;
}
.user-btn:hover{ background:rgba(255,255,255,.08); border-color:rgba(255,215,0,.3); }

.avatar {
    width:38px; height:38px; border-radius:50%;
    background: linear-gradient(145deg, #FFD700, #e6a800);
    display:flex; align-items:center; justify-content:center;
    font-weight:700; color:#111; font-size:14px;
    box-shadow: 0 0 0 2px rgba(255,215,0,.2);
    background-size: cover; background-position: center;
}
.user-meta { line-height:1.25; }
.user-meta .name { font-size:13px; font-weight:600; }
.user-meta .level { font-size:11px; color:var(--gold); font-weight:500; }

.dropdown-menu {
    position:absolute;
    top:calc(100% + 10px);
    right:0;
    width:230px;

    background:#141418;
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    overflow:hidden;

    box-shadow:0 24px 60px rgba(0,0,0,.55);
    z-index:10002;

    opacity:0;
    visibility:hidden;
    transform:translateY(-8px) scale(.96);
    transform-origin:top right;

    transition:
        opacity .2s ease,
        transform .2s ease,
        visibility .2s ease;
}

.dropdown-menu.show {
    opacity:1;
    visibility:visible;
    transform:translateY(0) scale(1);
}
.dropdown-menu a{
    position:relative; display:flex; align-items:center; gap:12px;
    padding:14px 20px; color:#e0e0e8; text-decoration:none; font-size:13.5px;
    border-bottom:1px solid rgba(255,255,255,.04);
    transition: color .25s, padding-left .25s, background .25s;
}
.dropdown-menu a::before{
    content:""; position:absolute; left:0; top:0; width:3px; height:100%;
    background:#FFD700; transform:scaleY(0); transition: transform .25s;
}
.dropdown-menu a:last-child { border:none; color:var(--danger); }
.dropdown-menu a:last-child:hover{ background:rgba(255,59,92,.08); color:#ff4a67; }
.dropdown-menu a:last-child::before{ background:#ff3b5c; }
.dropdown-menu a:hover{ background:rgba(255,215,0,.06); color:#FFD700; padding-left:28px; }
.dropdown-menu a:hover::before{ transform:scaleY(1); }

/* ========== PAGE HERO ========== */
.promo-hero {
    padding: 28px 7% 10px;
    max-width: 1400px;
    margin: 0 auto;
}
.promo-hero-inner {
    position: relative; overflow: hidden;
    padding: 36px 40px 32px;
    background: linear-gradient(145deg, rgba(22,22,28,.92) 0%, rgba(14,14,18,.96) 100%);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 28px;
    box-shadow: 0 4px 6px rgba(0,0,0,.12), 0 24px 48px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.06);
    backdrop-filter: blur(24px);
}
.promo-hero-inner::before {
    content: "";
    position: absolute; width: 480px; height: 480px;
    right: -140px; top: -140px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,215,0,.14) 0%, transparent 70%);
    filter: blur(20px); pointer-events: none;
}
.promo-hero-content { position: relative; z-index: 2; }
.promo-hero-content h1 {
    font-size: 32px; font-weight: 700; margin-bottom: 8px; letter-spacing: -.4px;
}
.promo-hero-content h1 span {
    background: linear-gradient(135deg, #FFE566 0%, #FFD700 45%, #E8A800 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.promo-hero-content p {
    color: var(--muted); font-size: 14px; max-width: 480px; line-height: 1.55; margin-bottom: 22px;
}

.tabs {
    display: flex; gap: 6px; flex-wrap: wrap;
    padding: 4px; background: rgba(0,0,0,.25);
    border-radius: 16px; border: 1px solid rgba(255,255,255,.04);
    width: fit-content; max-width: 100%;
}
.tab {
    padding: 9px 18px; border-radius: 12px; border: none;
    background: transparent; color: #8a8a96;
    font-size: 13px; font-weight: 500; cursor: pointer;
    transition: all .22s ease; white-space: nowrap;
}
.tab:hover { color: #e8e0c0; background: rgba(255,255,255,.04); }
.tab.active {
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111; font-weight: 600;
    box-shadow: 0 2px 12px rgba(255,215,0,.35);
}

/* ========== PROMO GRID + ANIMATION ========== */
.promo-section {
    padding: 20px 7% 60px;
    max-width: 1400px;
    margin: 0 auto;
}
.promo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    transition: opacity .28s ease;
}
.promo-grid.is-switching {
    opacity: 0;
    pointer-events: none;
}

.promo-card {
    position: relative;
    background: linear-gradient(180deg, #15151a, #111114);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 22px;
    overflow: hidden;
    transition: all .35s cubic-bezier(.4,0,.2,1);
    display: flex; flex-direction: column;

    /* entrance animation base */
    opacity: 0;
    transform: translateY(18px) scale(.97);
    filter: blur(4px);
}
.promo-card.show {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: blur(0);
    transition:
        opacity .4s cubic-bezier(.2,.9,.3,1),
        transform .4s cubic-bezier(.2,.9,.3,1),
        filter .4s ease,
        border-color .3s,
        box-shadow .3s;
}
.promo-card:hover {
    border-color: rgba(255,215,0,.28);
    transform: translateY(-6px);
    box-shadow: 0 20px 45px rgba(0,0,0,.4), 0 0 25px rgba(255,215,0,.08);
}

.promo-card-top {
    padding: 22px 22px 16px;
    display: flex; gap: 16px; align-items: flex-start;
}
.promo-card-icon {
    width: 56px; height: 56px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(145deg, #FFE566, #FFD700 40%, #E0A800);
    color: #111;
    box-shadow: 0 6px 18px rgba(255,215,0,.28);
}
.promo-card-icon .ico { width: 26px; height: 26px; }

.promo-card-info { flex: 1; min-width: 0; }
.promo-card-info .tag {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; letter-spacing: 1.4px;
    text-transform: uppercase; color: #FFD700; margin-bottom: 6px;
}
.promo-card-info h3 {
    font-size: 17px; font-weight: 700; color: #fff; margin-bottom: 6px; line-height: 1.3;
}
.promo-card-info p {
    font-size: 13px; color: #8a8a96; line-height: 1.5;
}

.promo-card-body {
    padding: 0 22px 16px;
    flex: 1;
}
.promo-terms {
    font-size: 12px;
    color: #8a8a96;
    line-height: 1.55;
    padding: 12px 14px 12px 16px;
    background: rgba(255,255,255,.025);
    border-left: 3px solid rgba(255,215,0,.45);
    border-radius: 0 10px 10px 0;
    margin: 0;
}
.promo-terms-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #FFD700;
    margin-bottom: 5px;
    opacity: .9;
}

.promo-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 14px 22px 18px;
    border-top: 1px solid rgba(255,255,255,.05);
}
.promo-expiry { font-size: 11.5px; color: #777; }
.btn-claim-promo {
    height: 38px; padding: 0 20px; border: none; border-radius: 11px;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all .25s;
    box-shadow: 0 4px 14px rgba(255,215,0,.25);
}
.btn-claim-promo:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(255,215,0,.4);
}
.btn-claim-promo:disabled,
.btn-claimed {
    background: rgba(255,255,255,.08);
    color: #888; cursor: default;
    box-shadow: none; transform: none;
}

/* ========== PROMO BADGE ========== */
.promo-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 9px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: .7px;
    text-transform: uppercase;
    white-space: nowrap;
    box-sizing: border-box;
}

.promo-badge.hot {
    background: rgba(255, 59, 92, .14);
    color: #ff5a73;
    border: 1px solid rgba(255, 59, 92, .28);
}

.promo-badge.new {
    background: rgba(0, 174, 255, .14);
    color: #35bfff;
    border: 1px solid rgba(0, 174, 255, .28);
}

.promo-badge.normal {
    background: rgba(255, 255, 255, .07);
    color: #aaa;
    border: 1px solid rgba(255, 255, 255, .12);
}

.promo-badge.limited {
    background: rgba(255, 149, 0, .14);
    color: #ffad32;
    border: 1px solid rgba(255, 149, 0, .30);
}

.promo-badge.exclusive {
    background: rgba(168, 85, 247, .14);
    color: #bd7aff;
    border: 1px solid rgba(168, 85, 247, .30);
}

/* Beri ruang agar badge tidak menabrak judul/tag */
.promo-card-top {
    padding-right: 105px;
}

.promo-empty {
    grid-column: 1 / -1;
    text-align: center; padding: 60px 20px; color: #666;
    opacity: 0; transform: translateY(12px);
    transition: opacity .35s ease, transform .35s ease;
}
.promo-empty.show {
    opacity: 1; transform: translateY(0);
}
.promo-empty .ico {
    width: 48px; height: 48px; margin: 0 auto 14px; opacity: .45; display: block;
}
.promo-empty p { font-size: 14px; margin-top: 6px; }

/* ========== SEARCH MODAL (sama member.php) ========== */
.search-modal{
    position:fixed; inset:0;
    background:rgba(0,0,0,.55); backdrop-filter:blur(12px);
    display:flex; justify-content:center; align-items:center;
    padding-top:25px;
    opacity:0; visibility:hidden; transition:.25s;
    z-index:999999;
}
.search-modal.show{ opacity:1; visibility:visible; }

.search-box{
    width:900px; max-width:95vw;
    height:75vh; max-height:850px;
    display:flex; flex-direction:column;
    background:#16161c; border-radius:22px; overflow:hidden;
    border:1px solid rgba(255,255,255,.08);
}
.search-header{
    display:flex; align-items:center;
    border-bottom:1px solid rgba(255,255,255,.06);
}
.search-header input{
    flex:1; height:58px; background:none; border:none;
    padding:0 20px; color:white; font-size:16px; outline:none;
}
.search-back, .search-close{
    width:58px; height:58px; border:none; background:none;
    color:white; cursor:pointer; font-size:22px;
    display:flex; align-items:center; justify-content:center; transition:.25s;
}
.search-back:hover, .search-close:hover{ color:#FFD700; }
.search-back{ opacity:0; pointer-events:none; }
.search-modal.searching .search-back{ opacity:1; pointer-events:auto; }

.search-result{ flex:1; overflow-y:auto; padding:20px; }
.search-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(150px,1fr));
    gap:16px;
}
.search-card{
    background:#1a1a22; border:1px solid rgba(255,255,255,.06);
    border-radius:16px; overflow:hidden; cursor:pointer;
    transition: opacity .35s, transform .35s, filter .35s, border-color .25s, box-shadow .25s;
    opacity:0; transform:translateY(20px) scale(.96); filter:blur(6px);
}
.search-card.show{
    opacity:1; transform:translateY(0) scale(1); filter:blur(0);
}
.search-card:hover{
    transform:translateY(-5px) scale(1.02);
    border-color:#FFD700;
    box-shadow:0 10px 25px rgba(255,215,0,.18);
}
.search-card img{ width:100%; height:140px; object-fit:cover; display:block; }
.search-card-info{ padding:12px; }
.search-card-info h4{
    color:#fff; font-size:14px; margin-bottom:5px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.search-card-provider{ color:#999; font-size:11px; }
.search-card-rtp{ margin-top:6px; color:#2dff6b; font-size:12px; font-weight:600; }

.search-hero{
    width:100%; padding:95px 30px; text-align:center;
    background:
        radial-gradient(circle at top, rgba(255,215,0,.12), transparent 70%),
        linear-gradient(180deg,#1a1a22,#141419);
    border-bottom:1px solid rgba(255,255,255,.06);
    border-radius:16px; box-sizing:border-box;
}
.search-hero-icon{
    width:55px; height:55px; margin:0 auto 12px; border-radius:16px;
    display:flex; align-items:center; justify-content:center; font-size:26px;
    background:linear-gradient(135deg,#FFD700,#e6b800); color:#111;
}
.search-hero h2{ color:#fff; font-size:21px; margin-bottom:6px; }
.search-hero p{ color:#999; font-size:13px; margin-bottom:22px; }
.hero-tags{ display:flex; justify-content:center; flex-wrap:wrap; gap:10px; }
.hero-pill{
    padding:10px 16px; border-radius:999px; background:#23232d;
    color:#ddd; font-size:13px; transition:.25s; cursor:pointer;
}
.hero-pill.active{ background:#FFD700; color:#111; font-weight:700; }
.hero-pill:hover{ background:#FFD700; color:#111; }
.hero-pill .ico{ width:14px; height:14px; margin-right:4px; vertical-align:-0.15em; }

.search-empty{ padding:50px; text-align:center; color:#777; }

.search-result::-webkit-scrollbar{ width:8px; }
.search-result::-webkit-scrollbar-track{ background:transparent; }
.search-result::-webkit-scrollbar-thumb{
    background:linear-gradient(180deg,#888,#444);
    border-radius:20px; border:2px solid transparent; background-clip:padding-box;
}
.search-result{ scrollbar-width:thin; scrollbar-color:#aaa transparent; }

/* ========== FOOTER / TOAST / LOGOUT ========== */
footer {
    text-align:center; padding: 36px 20px;
    background: #0c0c0e; color: #555; font-size:13px;
    border-top: 1px solid rgba(255,255,255,.04);
}
.toast{
    position:fixed; top:95px; right:30px;
    display:flex; align-items:center; gap:14px;
    min-width:320px; max-width:420px; padding:16px 18px;
    background:rgba(20,20,25,.88); backdrop-filter:blur(18px);
    border:1px solid rgba(255,215,0,.25); border-radius:18px;
    box-shadow: 0 20px 45px rgba(0,0,0,.45), 0 0 20px rgba(255,215,0,.08);
    transform:translateX(450px); opacity:0;
    transition: transform .45s cubic-bezier(.2,.9,.3,1), opacity .35s;
    z-index:999999;
}
.toast.show{ transform:translateX(0); opacity:1; }
.toast.hide{ transform:translateX(450px); opacity:0; }
.toast-icon{
    width:46px; height:46px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#FFD700,#ffbf00); color:#111;
    font-weight:700; font-size:20px;
    box-shadow:0 0 18px rgba(255,215,0,.45);
}
.toast-icon.error{
    background:linear-gradient(135deg,#ff3b5c,#ff1744); color:white;
    box-shadow:0 0 18px rgba(255,59,92,.45);
}
.toast-title{ color:#fff; font-weight:700; margin-bottom:3px; }
.toast-message{ color:#bcbcbc; font-size:13px; line-height:1.4; }

.logout-modal{
    position:fixed; inset:0; display:flex; justify-content:center; align-items:center;
    background:rgba(0,0,0,.25); backdrop-filter:blur(10px);
    opacity:0; visibility:hidden; transition:.3s ease; z-index:99999;
}
.logout-modal.show{ opacity:1; visibility:visible; }
.logout-box{
    width:360px; background:#17171c; border:1px solid rgba(255,215,0,.25);
    border-radius:20px; padding:28px; text-align:center;
}
.logout-box h3{ color:#FFD700; margin-bottom:12px; }
.logout-box p{ color:#bbb; margin-bottom:24px; }
.logout-actions{ display:flex; gap:12px; }
.logout-actions button{
    flex:1; height:42px; border:none; border-radius:10px; cursor:pointer; font-weight:600;
}
.cancel-btn{ background:#333; color:#fff; }
.logout-btn{ background:#ff3b5c; color:#fff; }
.logout-btn:hover{ background:#ff2147; }
body.modal-open > *:not(.logout-modal){
    filter:blur(6px); transition:filter .3s ease; pointer-events:none; user-select:none;
}

.ico {
    width: 1.15em; height: 1.15em;
    display: inline-block; vertical-align: -0.2em; flex-shrink: 0;
}
.dropdown-menu a .ico { width: 18px; height: 18px; }
.logout-box h3 .ico { width: 20px; height: 20px; margin-right: 6px; }
.search-hero-icon .ico { width: 28px; height: 28px; color: #111; }
.search-close .ico, .search-back .ico { width: 16px; height: 16px; }

/* Sparkles */
.sparkles{ position:fixed; inset:0; overflow:hidden; pointer-events:none; z-index:1; }
.sparkles span{
    position:absolute; display:block; border-radius:50%;
    background:radial-gradient(circle,#fff 0%,#FFD700 60%,transparent 100%);
    box-shadow: 0 0 8px rgba(255,215,0,.8), 0 0 20px rgba(255,215,0,.45);
    animation: floatSpark linear infinite, twinkle ease-in-out infinite;
}
.sparkles span:nth-child(odd){ width:3px; height:3px; }
.sparkles span:nth-child(even){ width:5px; height:5px; }
.sparkles span:nth-child(1){left:5%;top:105%;animation-duration:18s,3s;animation-delay:0s,.2s;}
.sparkles span:nth-child(2){left:14%;top:108%;animation-duration:22s,4s;animation-delay:2s,1s;}
.sparkles span:nth-child(3){left:26%;top:104%;animation-duration:16s,2.5s;}
.sparkles span:nth-child(4){left:37%;top:109%;animation-duration:20s,3.5s;}
.sparkles span:nth-child(5){left:48%;top:103%;animation-duration:25s,4s;}
.sparkles span:nth-child(6){left:58%;top:107%;animation-duration:17s,2.8s;}
.sparkles span:nth-child(7){left:66%;top:105%;animation-duration:19s,3.2s;}
.sparkles span:nth-child(8){left:75%;top:110%;animation-duration:24s,4.5s;}
.sparkles span:nth-child(9){left:83%;top:104%;animation-duration:18s,3.5s;}
.sparkles span:nth-child(10){left:90%;top:108%;animation-duration:21s,3.8s;}
.sparkles span:nth-child(11){left:55%;top:112%;animation-duration:27s,5s;}
.sparkles span:nth-child(12){left:10%;top:111%;animation-duration:23s,4.2s;}
@keyframes floatSpark{
    from{ transform:translateY(0) translateX(0) scale(.8); }
    25%{ transform:translateY(-25vh) translateX(12px); }
    50%{ transform:translateY(-50vh) translateX(-10px); }
    75%{ transform:translateY(-75vh) translateX(15px); }
    to{ transform:translateY(-120vh) translateX(-8px); }
}
@keyframes twinkle{
    0%,100%{ opacity:.15; filter:blur(.3px); transform:scale(.8); }
    50%{ opacity:1; filter:blur(0); transform:scale(1.6); }
}

/* ========== HISTORY / BONUS MODALS ========== */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    backdrop-filter:blur(8px);
    z-index:99999;
    align-items:center;
    justify-content:center;
}
.history-modal .modal-content,
.bonus-modal .modal-content {
    width: 560px;
    max-width: 94%;
    max-height: 85vh;
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    background:#15151c;
    border:1px solid rgba(255,215,0,.25);
    border-radius:24px;
    box-shadow:0 25px 70px rgba(0,0,0,.6);
}
.history-modal .modal-header,
.bonus-modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    flex-shrink: 0;
}
.history-modal .modal-header h3,
.bonus-modal .modal-header h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #FFD700;
    font-size: 17px;
    font-weight: 700;
    margin: 0;
}
.history-modal .modal-header h3 .ico,
.bonus-modal .modal-header h3 .ico { width: 20px; height: 20px; }
.history-modal .close,
.bonus-modal .close {
    position: static;
    margin: 0;
    font-size: 24px;
    line-height: 1;
    color: #888;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: .2s;
}
.history-modal .close:hover,
.bonus-modal .close:hover {
    color: #FFD700;
    background: rgba(255,215,0,.08);
}
.history-tabs {
    display: flex;
    gap: 6px;
    padding: 12px 20px;
    border-bottom: 1px solid rgba(255,255,255,.04);
    flex-shrink: 0;
    overflow-x: auto;
}
.history-tab {
    padding: 8px 14px;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: #8a8a96;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: .2s;
}
.history-tab:hover { color: #e8e0c0; background: rgba(255,255,255,.04); }
.history-tab.active {
    background: rgba(255,215,0,.12);
    color: #FFD700;
    font-weight: 600;
}
.history-body,
.bonus-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px 22px;
    min-height: 280px;
    max-height: 52vh;
}
.history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 12px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.05);
    background: rgba(255,255,255,.02);
    margin-bottom: 10px;
    transition: .2s;
}
.history-item:hover {
    border-color: rgba(255,215,0,.2);
    background: rgba(255,215,0,.04);
}
.history-item-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(255,215,0,.1);
    color: #FFD700;
}
.history-item-icon .ico { width: 20px; height: 20px; }
.history-item-icon.deposit { background: rgba(45,255,107,.1); color: #2dff6b; }
.history-item-icon.withdraw { background: rgba(255,59,92,.1); color: #ff3b5c; }
.history-item-icon.win { background: rgba(45,255,107,.12); color: #2dff6b; }
.history-item-icon.lose { background: rgba(255,59,92,.1); color: #ff3b5c; }
.history-item-info { flex: 1; min-width: 0; }
.history-item-info strong {
    display: block;
    font-size: 13.5px;
    font-weight: 600;
    color: #eee;
    margin-bottom: 3px;
}
.history-item-info span { font-size: 11.5px; color: #777; }
.history-item-amount { text-align: right; flex-shrink: 0; }
.history-item-amount .val {
    display: block;
    font-size: 14px;
    font-weight: 700;
}
.history-item-amount .val.plus { color: #2dff6b; }
.history-item-amount .val.minus { color: #ff3b5c; }
.history-item-amount .status {
    font-size: 10.5px;
    color: #888;
    margin-top: 2px;
}
.history-item-amount .status.ok { color: #2dff6b; }
.history-item-amount .status.pending { color: #FFD700; }
.history-item-amount .status.fail { color: #ff3b5c; }
.history-empty {
    text-align: center;
    padding: 48px 20px;
    color: #666;
}
.history-empty .ico {
    width: 40px;
    height: 40px;
    margin: 0 auto 12px;
    opacity: .5;
    display: block;
}
.history-empty p { font-size: 13px; margin-top: 6px; }
.bonus-card {
    padding: 16px;
    border-radius: 16px;
    border: 1px solid rgba(255,215,0,.14);
    background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(255,215,0,.02));
    margin-bottom: 12px;
    transition: .25s;
}
.bonus-card:hover {
    border-color: rgba(255,215,0,.3);
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
}
.bonus-card-top {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 12px;
}
.bonus-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(145deg, #FFE566, #FFD700 40%, #E0A800);
    color: #111;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bonus-card-icon .ico { width: 24px; height: 24px; }
.bonus-card-info { flex: 1; min-width: 0; }
.bonus-card-info .tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #FFD700;
    margin-bottom: 4px;
}
.bonus-card-info h4 {
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}
.bonus-card-info p {
    font-size: 12px;
    color: #8a8a96;
    line-height: 1.4;
}
.bonus-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.bonus-card-meta .expiry { font-size: 11px; color: #777; }
.bonus-card-meta .btn-claim-sm {
    height: 34px;
    padding: 0 16px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}
.bonus-card-meta .btn-claim-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(255,215,0,.3);
}
.bonus-card-meta .btn-claim-sm:disabled,
.bonus-card-meta .btn-claimed {
    background: rgba(255,255,255,.08);
    color: #888;
    cursor: default;
    box-shadow: none;
    transform: none;
}

@media (max-width: 960px) {
    header { padding: 0 16px; }
    .nav-menu { display:none; }
    .promo-hero, .promo-section { padding-left: 20px; padding-right: 20px; }
    .promo-hero-inner { padding: 28px 22px 24px; border-radius: 22px; }
    .promo-hero-content h1 { font-size: 24px; }
    .promo-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .promo-card-footer { flex-direction: column; align-items: stretch; }
    .btn-claim-promo { width: 100%; }
}
</style>
</head>
<body>

<div class="sparkles">
    <span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span>
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
            <a href="game-list.php">Games</a>
            <a href="promosi.php" class="active">Promosi</a>
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
            <button class="btn-deposit" type="button" onclick="location.href='member.php'">+ Deposit</button>
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
                <a href="profile.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Profil Saya</a>
                <a href="#" onclick="openHistoryModal(event)"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Riwayat Transaksi</a>
                <a href="#" onclick="openBetHistoryModal(event)"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg> Riwayat Taruhan</a>
                <a href="#" onclick="openBonusModal(event)"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg> Bonus & Promo</a>
                <a href="settings.php"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Pengaturan</a>
                <a href="#" onclick="confirmLogout(event)"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Keluar</a>
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

<!-- HERO -->
<section class="promo-hero">
    <div class="promo-hero-inner">
        <div class="promo-hero-content">
            <h1>Promo & <span>Bonus Spesial</span></h1>
            <p>Klaim bonus harian, cashback, turnamen, dan promo eksklusif untuk member Royal Knight's.</p>

            <div class="tabs" id="promoTabs">
                <button class="tab active" data-cat="all">Semua</button>
                <button class="tab" data-cat="deposit">Deposit</button>
                <button class="tab" data-cat="cashback">Cashback</button>
                <button class="tab" data-cat="tournament">Turnamen</button>
                <button class="tab" data-cat="vip">VIP</button>
                <button class="tab" data-cat="referral">Referral</button>
            </div>
        </div>
    </div>
</section>

<!-- PROMO GRID -->
<section class="promo-section">
    <div class="promo-grid" id="promoGrid"></div>
</section>

<footer>
    © 2026 Royal Knight's • Bermain secara bertanggung jawab • 18+
</footer>

<!-- SEARCH MODAL -->
<div class="search-modal" id="searchModal">
    <div class="search-box">
        <div class="search-header">
            <button class="search-back" onclick="backSearch()">←</button>
            <input type="text" id="popupSearch" placeholder="Cari permainan...">
            <button class="search-close" onclick="closeSearch()">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="search-result" id="searchResult"></div>
    </div>
</div>

<!-- ========== RIWAYAT TRANSAKSI MODAL ========== -->
<div id="historyModal" class="modal history-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Riwayat Transaksi
            </h3>
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

<!-- ========== RIWAYAT TARUHAN MODAL ========== -->
<div id="betHistoryModal" class="modal history-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>
                Riwayat Taruhan
            </h3>
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

<!-- ========== BONUS & PROMO MODAL ========== -->
<div id="bonusModal" class="modal bonus-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                Bonus &amp; Promo
            </h3>
            <span class="close" onclick="closeBonusModal()">&times;</span>
        </div>
        <div class="bonus-body" id="bonusBody"></div>
    </div>
</div>

<!-- LOGOUT MODAL -->
<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <h3>
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="logout-actions">
            <button class="cancel-btn" onclick="closeLogout()">Batal</button>
            <button class="logout-btn" onclick="logoutNow()">Keluar</button>
        </div>
    </div>
</div>

<script>
const promos = <?= json_encode($promos, JSON_UNESCAPED_UNICODE); ?>;
const games  = <?= json_encode($games, JSON_UNESCAPED_UNICODE); ?>;

const gameImages = {
    "Big Bass Bonanza":"../assets/game/big.png",
    "The Dog House Megaways":"../assets/game/dog.jpg",
    "Fruit Party":"../assets/game/fruit.jpg",
    "Dragon Gold":"../assets/game/dragon.png",
    "Gates of Olympus":"../assets/game/gates.jpg",
    "Sweet Bonanza":"../assets/game/sweet.jpg",
    "Starlight Princess":"../assets/game/starlight.jpg",
    "Wild Bandito":"../assets/game/wild.jpg",
    "Wanted Dead or a Wild":"../assets/game/wanted.png",
    "Mahjong Ways 2":"../assets/game/mahjong.png",
    "Lucky Neko":"../assets/game/lucky.png",
    "Aviator":"../assets/game/aviator.jpg",
    "JetX":"../assets/game/jetx.jpg",
    "Plinko":"../assets/game/plinko.jpg",
    "Mines":"../assets/game/mines.jpg",
    "Spaceman":"../assets/game/space.jpg",
    "Buffalo King":"../assets/game/bufallo.png",
    "Sugar Rush":"../assets/game/sugar.png"
};
games.forEach(g => {
    g.img = gameImages[g.name] ?? "../assets/game/default.jpg";
});

const icoGift = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>`;
const icoEmpty = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`;

/* ========== PROMO RENDER + SMOOTH TAB ANIMATION ========== */
let isSwitching = false;

function renderPromos(cat = "all", animate = true) {
    const grid = document.getElementById("promoGrid");
    let list = promos;
    if (cat !== "all") list = promos.filter(p => p.cat === cat);

    const doRender = () => {
        if (!list.length) {
            grid.innerHTML = `
                <div class="promo-empty">
                    ${icoEmpty}
                    <strong>Tidak ada promo di kategori ini</strong>
                    <p>Coba pilih kategori lain atau cek lagi nanti.</p>
                </div>`;
            requestAnimationFrame(() => {
                grid.querySelector(".promo-empty")?.classList.add("show");
            });
            return;
        }

        grid.innerHTML = list.map(p => `
            <div class="promo-card" data-id="${p.id}">
                ${p.badge ? `<div class="promo-badge ${p.badge}">${p.badge.toUpperCase()}</div>` : ""}
                <div class="promo-card-top">
                    <div class="promo-card-icon">${icoGift}</div>
                    <div class="promo-card-info">
                        <div class="tag">${p.tag}</div>
                        <h3>${p.title}</h3>
                        <p>${p.desc}</p>
                    </div>
                </div>
                <div class="promo-card-body">
                    <div class="promo-terms">
                        <span class="promo-terms-label">Syarat &amp; Ketentuan</span>
                        ${p.terms}
                    </div>
                </div>
                <div class="promo-card-footer">
                    <span class="promo-expiry">${p.expiry}</span>
                    ${p.claimed
                        ? `<button type="button" class="btn-claim-promo btn-claimed" disabled>Sudah Diklaim</button>`
                        : `<button type="button" class="btn-claim-promo" onclick="claimPromo(${p.id}, this)">Klaim Sekarang</button>`
                    }
                </div>
            </div>
        `).join("");

        // Stagger entrance
        const cards = grid.querySelectorAll(".promo-card");
        cards.forEach((card, i) => {
            setTimeout(() => card.classList.add("show"), 40 + i * 55);
        });
    };

    if (!animate) {
        doRender();
        return;
    }

    // Fade out → swap → fade in
    if (isSwitching) return;
    isSwitching = true;
    grid.classList.add("is-switching");

    setTimeout(() => {
        doRender();
        grid.classList.remove("is-switching");
        isSwitching = false;
    }, 280);
}

function claimPromo(id, btn) {
    const item = promos.find(p => p.id === id);
    if (!item || item.claimed) return;
    item.claimed = true;
    btn.textContent = "Sudah Diklaim";
    btn.disabled = true;
    btn.classList.add("btn-claimed");
    showToast("Berhasil", `Promo "${item.title}" berhasil diklaim!`, "success");
}

document.querySelectorAll("#promoTabs .tab").forEach(tab => {
    tab.addEventListener("click", () => {
        if (tab.classList.contains("active")) return;
        document.querySelectorAll("#promoTabs .tab").forEach(t => t.classList.remove("active"));
        tab.classList.add("active");
        renderPromos(tab.dataset.cat, true);
    });
});

/* ========== SEARCH (sama member.php) ========== */
function openSearch(e) {
    e.preventDefault();
    const modal = document.getElementById("searchModal");
    modal.classList.add("show");
    modal.classList.remove("searching");
    popupSearch.value = "";
    renderSearch(games);
    popupSearch.focus();
}
function closeSearch() {
    document.getElementById("searchModal").classList.remove("show");
}
function backSearch() {
    popupSearch.value = "";
    const modal = document.getElementById("searchModal");
    modal.classList.remove("searching");
    renderSearch(games);
    document.querySelectorAll(".hero-pill").forEach(x => x.classList.remove("active"));
    popupSearch.focus();
}

const popupSearch = document.getElementById("popupSearch");
const searchResult = document.getElementById("searchResult");

popupSearch.addEventListener("input", () => {
    const modal = document.getElementById("searchModal");
    const key = popupSearch.value.toLowerCase().trim();
    if (key.length > 0) modal.classList.add("searching");
    else modal.classList.remove("searching");

    renderSearch(games.filter(g => g.name.toLowerCase().includes(key)));
});

function renderSearch(list) {
    // Initial hero state
    if (popupSearch.value === "" && list.length === games.length) {
        searchResult.innerHTML = `
        <div class="search-hero">
            <div class="search-hero-icon">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg>
            </div>
            <h2>Temukan Kemenangan Terbesarmu</h2>
            <p>Jelajahi <strong>2.000+</strong> permainan dari provider terbaik dunia.</p>
            <div class="hero-tags">
                <div class="hero-pill" data-cat="slots">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="8" y2="16"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="16" y1="8" x2="16" y2="16"/><circle cx="8" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="10" r="1.2" fill="currentColor" stroke="none"/><circle cx="16" cy="14" r="1.2" fill="currentColor" stroke="none"/></svg> Slots
                </div>
                <div class="hero-pill" data-cat="live">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg> Live
                </div>
                <div class="hero-pill" data-cat="crash">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg> Crash
                </div>
                <div class="hero-pill" data-cat="table">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="12" height="16" rx="2"/><path d="M9 3v16"/><path d="M17 7l2 1v11a2 2 0 0 1-2 2H9"/></svg> Table
                </div>
                <div class="hero-pill" data-cat="all">
                    <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M12 23c-4.4 0-8-3-8-7.5 0-2.5 1.2-4.7 3-6.2.5 2.2 1.8 3.4 3 4-1-4 2-8 5-10 1 3 3 5 5 6.5 1.5 1.2 2.5 2.8 2.5 5.2C22.5 20 18.4 23 12 23z"/></svg> Semua
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
                    <img src="${g.img}" alt="">
                    <div class="search-card-info">
                        <h4>${g.name}</h4>
                        <div class="search-card-provider">${g.provider}</div>
                        <div class="search-card-rtp">RTP ${Number(g.rtp).toFixed(2)}%</div>
                    </div>
                </div>
            `).join("")}
        </div>`;

    const cards = document.querySelectorAll(".search-card");
    cards.forEach((card, i) => {
        setTimeout(() => card.classList.add("show"), i * 45);
    });
}

document.addEventListener("click", function(e) {
    const pill = e.target.closest(".hero-pill");
    if (!pill) return;
    const cat = pill.dataset.cat;
    popupSearch.value = "";
    if (cat === "all") {
        renderSearch(games);
        return;
    }
    renderSearch(games.filter(g => g.cat === cat));
});
document.addEventListener("click", function(e) {
    const pill = e.target.closest(".hero-pill");
    if (!pill) return;
    document.querySelectorAll(".hero-pill").forEach(x => x.classList.remove("active"));
    pill.classList.add("active");
});

document.addEventListener("keydown", e => {
    if (e.key === "Escape") closeSearch();
});

const searchModal = document.getElementById("searchModal");
searchModal.addEventListener("click", function(e) {
    if (e.target === searchModal) closeSearch();
});

function playGame(id) {
    window.location.href = "game.php?id=" + id;
}

/* ========== COMMON ========== */
function toggleUserMenu() {
    document.getElementById("userMenu").classList.toggle("show");
}
document.addEventListener("click", e => {
    if (!e.target.closest(".user-dropdown")) {
        document.getElementById("userMenu")?.classList.remove("show");
    }
});
window.addEventListener("scroll", () => {
    document.querySelector("header").classList.toggle("scrolled", window.scrollY > 20);
});

function confirmLogout(e) {
    e.preventDefault();
    document.body.classList.add("modal-open");
    document.getElementById("logoutModal").classList.add("show");
}
function closeLogout() {
    document.body.classList.remove("modal-open");
    document.getElementById("logoutModal").classList.remove("show");
}
function logoutNow() {
    window.location.href = "/casino/member/logout.php";
}
document.getElementById("logoutModal").onclick = function(e) {
    if (e.target === this) closeLogout();
};

function showToast(title, message, type = "success") {
    const toast = document.getElementById("toast");
    const icon = document.getElementById("toastIcon");
    toast.querySelector(".toast-title").innerText = title;
    toast.querySelector(".toast-message").innerText = message;
    if (type === "error") {
        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        icon.classList.add("error");
    } else {
        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
        icon.classList.remove("error");
    }
    toast.classList.remove("hide");
    toast.classList.add("show");
    setTimeout(() => {
        toast.classList.remove("show");
        toast.classList.add("hide");
    }, 3000);
}

/* ========== HISTORY / BET / BONUS MODALS ========== */
const sampleTransactions = [
    { type: "deposit",  title: "Deposit Bank BCA",   date: "10 Agu 2026 · 14:32", amount: 500000,  status: "ok",      statusText: "Berhasil" },
    { type: "withdraw", title: "Withdraw E-Wallet",  date: "09 Agu 2026 · 21:10", amount: 250000,  status: "pending", statusText: "Diproses" },
    { type: "deposit",  title: "Deposit QRIS",       date: "08 Agu 2026 · 11:05", amount: 100000,  status: "ok",      statusText: "Berhasil" },
    { type: "withdraw", title: "Withdraw Bank BNI",  date: "07 Agu 2026 · 16:48", amount: 750000,  status: "ok",      statusText: "Berhasil" },
    { type: "deposit",  title: "Deposit DANA",       date: "05 Agu 2026 · 09:20", amount: 200000,  status: "fail",    statusText: "Gagal" },
    { type: "deposit",  title: "Deposit Bank Mandiri", date: "03 Agu 2026 · 19:55", amount: 1000000, status: "ok",    statusText: "Berhasil" }
];
const sampleBets = [
    { type: "win",  title: "Gates of Olympus",  date: "10 Agu 2026 · 15:12", amount: 1250000, stake: 25000 },
    { type: "lose", title: "Sweet Bonanza",     date: "10 Agu 2026 · 14:58", amount: 50000,   stake: 50000 },
    { type: "win",  title: "Starlight Princess",date: "09 Agu 2026 · 22:41", amount: 340000,  stake: 20000 },
    { type: "lose", title: "Aviator",           date: "09 Agu 2026 · 20:03", amount: 100000,  stake: 100000 },
    { type: "win",  title: "Mahjong Ways 2",    date: "08 Agu 2026 · 18:27", amount: 890000,  stake: 15000 },
    { type: "lose", title: "Big Bass Bonanza",  date: "08 Agu 2026 · 12:15", amount: 30000,   stake: 30000 }
];
const sampleBonuses = [
    { id: 1, tag: "WELCOME", title: "Bonus Deposit 100%", desc: "Min deposit Rp50.000 · Maks bonus Rp1.000.000", expiry: "Berlaku hingga 17 Agu 2026", claimed: false },
    { id: 2, tag: "HARIAN", title: "Cashback 10%", desc: "Cashback kekalahan slot setiap hari, maks Rp500.000", expiry: "Reset setiap hari 00:00", claimed: false },
    { id: 3, tag: "MINGGUAN", title: "Reload Bonus 50%", desc: "Bonus reload setiap Senin, min Rp100.000", expiry: "Berlaku Senin saja", claimed: true },
    { id: 4, tag: "VIP", title: "Extra Spin 50x", desc: "Free spin untuk member Gold ke atas", expiry: "Berlaku 3 hari setelah klaim", claimed: false }
];

const icoArrowDown = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>`;
const icoArrowUp = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>`;
const icoDice = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/></svg>`;

function fmtRp(n){
    return "Rp " + Number(n).toLocaleString("id-ID");
}
function closeUserMenu(){
    document.getElementById("userMenu")?.classList.remove("show");
}
function openHistoryModal(e){
    if(e) e.preventDefault();
    closeUserMenu();
    renderTransactions("all");
    document.getElementById("historyModal").style.display = "flex";
}
function closeHistoryModal(){
    document.getElementById("historyModal").style.display = "none";
}
function openBetHistoryModal(e){
    if(e) e.preventDefault();
    closeUserMenu();
    renderBets("all");
    document.getElementById("betHistoryModal").style.display = "flex";
}
function closeBetHistoryModal(){
    document.getElementById("betHistoryModal").style.display = "none";
}
function openBonusModal(e){
    if(e) e.preventDefault();
    closeUserMenu();
    renderBonuses();
    document.getElementById("bonusModal").style.display = "flex";
}
function closeBonusModal(){
    document.getElementById("bonusModal").style.display = "none";
}
function renderTransactions(filter){
    const body = document.getElementById("historyBody");
    let list = sampleTransactions;
    if(filter !== "all") list = list.filter(t => t.type === filter);
    if(!list.length){
        body.innerHTML = `<div class="history-empty">${icoEmpty}<strong>Belum ada transaksi</strong><p>Riwayat akan muncul di sini.</p></div>`;
        return;
    }
    body.innerHTML = list.map(t => {
        const isDep = t.type === "deposit";
        const iconCls = isDep ? "deposit" : "withdraw";
        const icon = isDep ? icoArrowDown : icoArrowUp;
        const sign = isDep ? "+" : "−";
        const valCls = isDep ? "plus" : "minus";
        return `
        <div class="history-item">
            <div class="history-item-icon ${iconCls}">${icon}</div>
            <div class="history-item-info">
                <strong>${t.title}</strong>
                <span>${t.date}</span>
            </div>
            <div class="history-item-amount">
                <span class="val ${valCls}">${sign}${fmtRp(t.amount)}</span>
                <div class="status ${t.status}">${t.statusText}</div>
            </div>
        </div>`;
    }).join("");
}
function renderBets(filter){
    const body = document.getElementById("betHistoryBody");
    let list = sampleBets;
    if(filter !== "all") list = list.filter(t => t.type === filter);
    if(!list.length){
        body.innerHTML = `<div class="history-empty">${icoEmpty}<strong>Belum ada taruhan</strong><p>Riwayat taruhan akan muncul di sini.</p></div>`;
        return;
    }
    body.innerHTML = list.map(t => {
        const isWin = t.type === "win";
        const iconCls = isWin ? "win" : "lose";
        const sign = isWin ? "+" : "−";
        const valCls = isWin ? "plus" : "minus";
        const statusText = isWin ? "Menang" : "Kalah";
        const statusCls = isWin ? "ok" : "fail";
        return `
        <div class="history-item">
            <div class="history-item-icon ${iconCls}">${icoDice}</div>
            <div class="history-item-info">
                <strong>${t.title}</strong>
                <span>${t.date} · Stake ${fmtRp(t.stake)}</span>
            </div>
            <div class="history-item-amount">
                <span class="val ${valCls}">${sign}${fmtRp(t.amount)}</span>
                <div class="status ${statusCls}">${statusText}</div>
            </div>
        </div>`;
    }).join("");
}
function renderBonuses(){
    const body = document.getElementById("bonusBody");
    if(!sampleBonuses.length){
        body.innerHTML = `<div class="history-empty">${icoEmpty}<strong>Tidak ada promo</strong><p>Cek lagi nanti ya.</p></div>`;
        return;
    }
    body.innerHTML = sampleBonuses.map(b => `
        <div class="bonus-card" data-id="${b.id}">
            <div class="bonus-card-top">
                <div class="bonus-card-icon">${icoGift}</div>
                <div class="bonus-card-info">
                    <div class="tag">${b.tag}</div>
                    <h4>${b.title}</h4>
                    <p>${b.desc}</p>
                </div>
            </div>
            <div class="bonus-card-meta">
                <span class="expiry">${b.expiry}</span>
                ${b.claimed
                    ? `<button type="button" class="btn-claim-sm btn-claimed" disabled>Sudah Diklaim</button>`
                    : `<button type="button" class="btn-claim-sm" onclick="claimBonus(${b.id}, this)">Klaim</button>`
                }
            </div>
        </div>
    `).join("");
}
function claimBonus(id, btn){
    const item = sampleBonuses.find(b => b.id === id);
    if(!item || item.claimed) return;
    item.claimed = true;
    btn.textContent = "Sudah Diklaim";
    btn.disabled = true;
    btn.classList.add("btn-claimed");
    showToast("Berhasil", "Bonus berhasil diklaim!", "success");
}
document.getElementById("historyTabs")?.addEventListener("click", function(e){
    const tab = e.target.closest(".history-tab");
    if(!tab) return;
    this.querySelectorAll(".history-tab").forEach(t => t.classList.remove("active"));
    tab.classList.add("active");
    renderTransactions(tab.dataset.filter);
});
document.getElementById("betHistoryTabs")?.addEventListener("click", function(e){
    const tab = e.target.closest(".history-tab");
    if(!tab) return;
    this.querySelectorAll(".history-tab").forEach(t => t.classList.remove("active"));
    tab.classList.add("active");
    renderBets(tab.dataset.filter);
});
window.addEventListener("click", function(e){
    if(e.target === document.getElementById("historyModal")) closeHistoryModal();
    if(e.target === document.getElementById("betHistoryModal")) closeBetHistoryModal();
    if(e.target === document.getElementById("bonusModal")) closeBonusModal();
});

// Init
renderPromos("all", false);
</script>
</body>
</html>