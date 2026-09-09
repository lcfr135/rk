<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../rtp/rtp_update.php';

$games = [];

$result = $conn->query("
SELECT *
FROM rtp_live
ORDER BY rtp DESC
");

while($row = $result->fetch_assoc()){

    $games[] = [
        "id"       => $row["id"],
        "name"     => $row["game_name"],
        "provider" => $row["provider"],
        "cat"      => strtolower($row["category"]),
        "rtp" => round($row["rtp"],2),
        "badge"    => strtolower($row["status"]),
        "trend"    => $row["trend"]
    ];

}

// Support both session keys (login flag / user id)
if (!isset($_SESSION['login']) && !isset($_SESSION['id'])) {
    header("Location: /casino/auth/login.php");
    exit;
}

$showWelcome = false;

if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;
    $showWelcome = true;
}

/*
    Ambil data user realtime dari database (sama seperti profile.php)
*/
$user_id = $_SESSION['id'] ?? null;

// Jika id belum ada di session, coba dari login (kalau numeric) atau username
if (!$user_id && isset($_SESSION['login']) && is_numeric($_SESSION['login'])) {
    $user_id = (int) $_SESSION['login'];
}

$user = null;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT
            id,
            username,
            fullname,
            email,
            phone,
            avatar,
            balance,
            level,
            points,
            total_deposit,
            total_withdraw
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
            id,
            username,
            fullname,
            email,
            phone,
            avatar,
            balance,
            level,
            points,
            total_deposit,
            total_withdraw
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

// Simpan id ke session biar konsisten dengan profile.php
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Casino Lobby | Royal Knight's</title>
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

    background:rgba(18,18,22,.55);
    backdrop-filter:blur(22px);

    border-bottom:1px solid rgba(255,255,255,.08);

    z-index:9999;
}
header.scrolled{
    background:rgba(10,10,10,.72);
    border-bottom:1px solid rgba(255,215,0,.18);
}
header::after {
    content:""; position:absolute; left:0; bottom:0; width:100%; height:1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,.45), transparent);
    opacity:0; transition:.35s;
}
header.scrolled::after { opacity:1; }

.logo{ 
    width:138px; 
    max-height:88px; 
    object-fit:contain; 
    filter: drop-shadow(0 2px 8px rgba(255,215,0,.15)); }

.logo img{
    width:130px;
    display:block;
    position:relative;
    top:4px;   /* turunkan logo */
}

.navbar{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
}

.nav-menu{
    display:flex;
    align-items:center;
    gap:14px;
    height:100%;
}

.nav-menu a{
    display:flex;
    align-items:center;
    justify-content:center;

    height:42px;
    padding:0 18px;

    border-radius:20px;

    color:#e8e8e8;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
    letter-spacing:.3px;

    border:1px solid transparent;

    transition:.25s ease;
}

.nav-menu a:hover{
    background:#202020;
    border-color:rgba(255,215,0,.25);
    color:#FFD700;
    transform:translateY(-2px);
}

.nav-menu a.active{
    background:linear-gradient(180deg,#FFD700,#d9a500);
    color:#111;
    border:1px solid #FFD700;
    box-shadow:
        0 0 15px rgba(255,215,0,.35),
        inset 0 1px 0 rgba(255,255,255,.3);
}

.nav-action{
    display:flex;
    align-items:center;
    gap:12px;
}

/* ================= MEMBER AREA ================= */

.member-area{
    display:flex;
    align-items:center;
    justify-self:end;
    gap:12px;
    height:100%;
}

/* Wallet */
.balance-card{
    display:flex;
    align-items:center;
    gap:10px;

    height:44px;
    padding:0 6px 0 14px;

    background:rgba(255,255,255,.05);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
   border:1px solid rgba(255,255,255,.08);
    border-radius:12px;

    transition:.25s;
}

.balance-card:hover{
    border-color:rgba(255,215,0,.35);
    box-shadow:0 0 18px rgba(255,215,0,.12);
}

.balance-info{
    display:flex;
    flex-direction:column;
    line-height:1.1;
}

.balance-info .label{
    font-size:9px;
    color:#888;
    letter-spacing:1px;
    text-transform:uppercase;
}

.balance-info .amount{
    font-size:14px;
    font-weight:700;
    color:#FFD700;
}

/* Deposit Button */
.btn-deposit{
    height:34px;
    padding:0 14px;

    border:none;
    border-radius:9px;

    background:#FFD700;
    color:#111;

    font-size:12px;
    font-weight:700;

    cursor:pointer;
    transition:.2s;
}

.btn-deposit:hover{
    background:#ffe24d;
    transform:translateY(-1px);
}

.user-dropdown { position:relative; }
.user-btn {
    display:flex; align-items:center; gap:10px; cursor:pointer;
    background: #141418;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 40px;
    padding: 5px 16px 5px 5px;
    transition: all .25s ease;
}
.user-btn:hover{
    background:rgba(255,255,255,.08);
    border-color:rgba(255,215,0,.3);
}

.avatar {
    width:38px; height:38px; border-radius:50%;
    background: linear-gradient(145deg, #FFD700, #e6a800);
    display:flex; align-items:center; justify-content:center;
    font-weight:700; color:#111; font-size:14px;
    box-shadow: 0 0 0 2px rgba(255,215,0,.2);
    background-size: cover;
    background-position: center;
}
.user-meta { line-height:1.25; }
.user-meta .name { font-size:13px; font-weight:600; }
.user-meta .level { font-size:11px; color:var(--gold); font-weight:500; }

.dropdown-menu{
    position:absolute;
    top:56px;
    right:0;
    width:230px;

    background:#141418;
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    overflow:hidden;

    box-shadow:0 24px 60px rgba(0,0,0,.55);
    z-index:100;

    opacity:0;
    visibility:hidden;

    transform:translateY(-12px) scale(.96);

    transition:
        opacity .25s ease,
        transform .25s ease,
        visibility .25s;
}

.dropdown-menu.show{
    opacity:1;
    visibility:visible;
    transform:translateY(0) scale(1);
}
.dropdown-menu a{
    position:relative;

    display:flex;
    align-items:center;
    gap:12px;

    padding:14px 20px;

    color:#e0e0e8;
    text-decoration:none;
    font-size:13.5px;

    border-bottom:1px solid rgba(255,255,255,.04);

    overflow:hidden;

    transition:
        color .25s,
        padding-left .25s,
        background .25s;
}
.dropdown-menu a::before{

    content:"";

    position:absolute;

    left:0;
    top:0;

    width:3px;
    height:100%;

    background:#FFD700;

    transform:scaleY(0);

    transition:
        transform .25s;
}
.dropdown-menu a:last-child { border:none; color:var(--danger); }
.dropdown-menu a:last-child:hover{

    background:rgba(255,59,92,.08);

    color:#ff4a67;
}
.dropdown-menu a:last-child::before{

    background:#ff3b5c;
}
.dropdown-menu a:hover{

    background:rgba(255,215,0,.06);

    color:#FFD700;

    padding-left:28px;
}
.dropdown-menu a:hover::before{

    transform:scaleY(1);
}
.dropdown-menu a:last-child:hover { background: rgba(255,59,92,.08); color:var(--danger); }

/* ========== HERO ========== */
.member-hero {
    padding: 12px 7% 24px;
    background: transparent;
    position: relative;
    overflow: visible;
}

.member-hero::before {
    content: none;
}

.hero-card {
    position: relative;
    overflow: hidden;
    max-width: 1400px;
    margin: auto;
    padding: 36px 40px 28px;

    background:
        linear-gradient(
            145deg,
            rgba(22, 22, 28, 0.92) 0%,
            rgba(14, 14, 18, 0.96) 100%
        );

    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 28px;

    box-shadow:
        0 4px 6px rgba(0, 0, 0, 0.12),
        0 24px 48px rgba(0, 0, 0, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.06);

    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}

/* soft mesh glow */
.hero-card::before {
    content: "";
    position: absolute;
    width: 520px;
    height: 520px;
    right: -160px;
    top: -120px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(255, 215, 0, 0.13) 0%,
        rgba(255, 180, 0, 0.04) 40%,
        transparent 70%
    );
    filter: blur(20px);
    animation: heroGlow 12s ease-in-out infinite alternate;
    pointer-events: none;
}

.hero-card::after {
    content: "";
    position: absolute;
    width: 380px;
    height: 380px;
    left: -120px;
    bottom: -100px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(120, 80, 255, 0.1) 0%,
        rgba(255, 60, 100, 0.06) 45%,
        transparent 70%
    );
    filter: blur(24px);
    animation: heroGlow 14s ease-in-out infinite alternate-reverse;
    pointer-events: none;
}

.hero-card > * {
    position: relative;
    z-index: 2;
}

@keyframes heroGlow {
    0%   { transform: scale(1) translate(0, 0); opacity: 0.85; }
    100% { transform: scale(1.15) translate(-18px, 12px); opacity: 1; }
}

/* ===== Welcome ===== */
.welcome-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 28px;
    margin-bottom: 28px;
}

.welcome-text h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: -0.4px;
    line-height: 1.2;
    color: #f8f8fa;
}

.welcome-text h1 span {
    background: linear-gradient(135deg, #FFE566 0%, #FFD700 45%, #E8A800 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-text p {
    color: var(--muted);
    font-size: 14px;
    max-width: 380px;
    line-height: 1.55;
    font-weight: 400;
}

/* ===== Stats ===== */
.quick-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.stat-pill {
    position: relative;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 16px;
    padding: 14px 20px 14px 18px;
    min-width: 118px;
    backdrop-filter: blur(12px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.stat-pill::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 215, 0, 0.08) 0%,
        transparent 60%
    );
    opacity: 0;
    transition: opacity 0.3s;
}

.stat-pill:hover {
    border-color: rgba(255, 215, 0, 0.28);
    background: rgba(255, 215, 0, 0.05);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.stat-pill:hover::before {
    opacity: 1;
}

.stat-pill .val {
    font-size: 19px;
    font-weight: 700;
    color: #FFD700;
    letter-spacing: -0.3px;
    position: relative;
}

.stat-pill .lbl {
    font-size: 10.5px;
    color: #7a7a86;
    margin-top: 4px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    font-weight: 500;
    position: relative;
}

/* ===== Promo Banner ===== */
.promo-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    width: 100%;
    margin: 0 auto 24px;
    padding: 18px 22px 18px 18px;

    background:
        linear-gradient(
            120deg,
            rgba(255, 215, 0, 0.07) 0%,
            rgba(255, 215, 0, 0.02) 35%,
            rgba(18, 18, 24, 0.6) 100%
        );

    border: 1px solid rgba(255, 215, 0, 0.14);
    border-radius: 20px;
    position: relative;
    overflow: hidden;

    box-shadow:
        0 8px 28px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.promo-banner::before {
    content: "";
    position: absolute;
    right: -80px;
    top: -80px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(255, 215, 0, 0.18),
        transparent 70%
    );
    filter: blur(8px);
    pointer-events: none;
}

.promo-banner > * {
    position: relative;
    z-index: 2;
}

.promo-content {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
    min-width: 0;
}

.promo-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 26px;
    background: linear-gradient(145deg, #FFE566, #FFD700 40%, #E0A800);
    color: #111;
    box-shadow:
        0 6px 20px rgba(255, 215, 0, 0.32),
        inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

.promo-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
    color: #FFD700;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.8px;
    text-transform: uppercase;
}

.promo-info h2 {
    margin-bottom: 3px;
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.3px;
    line-height: 1.25;
}

.promo-info p {
    color: #8a8a96;
    font-size: 13px;
    line-height: 1.4;
}

.promo-timer {
    min-width: 148px;
    text-align: center;
    padding: 10px 16px;
    background: rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 14px;
}

.promo-timer small {
    display: block;
    margin-bottom: 4px;
    color: #777;
    font-size: 11px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    font-weight: 500;
}

.countdown {
    font-size: 22px;
    font-weight: 700;
    color: #FFD700;
    letter-spacing: 1.5px;
    font-variant-numeric: tabular-nums;
}

.promo-left h2 {
    font-size: 18px;
    margin-bottom: 2px;
    font-weight: 700;
}

.promo-left p {
    font-size: 12px;
    color: #8d8d98;
}

.btn-claim {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    padding: 0 26px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #FFE566 0%, #FFD700 50%, #E8B000 100%);
    color: #111;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.2px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow:
        0 4px 16px rgba(255, 215, 0, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
}

.btn-claim:hover {
    transform: translateY(-2px);
    box-shadow:
        0 8px 24px rgba(255, 215, 0, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

.btn-claim:active {
    transform: translateY(0);
}

/* ===== Tabs ===== */
.tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 0;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
    padding: 4px;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.04);
    width: fit-content;
    max-width: 100%;
}

.tab {
    padding: 9px 18px;
    border-radius: 12px;
    border: none;
    background: transparent;
    color: #8a8a96;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.22s ease;
    white-space: nowrap;
}

.tab:hover {
    color: #e8e0c0;
    background: rgba(255, 255, 255, 0.04);
}

.tab.active {
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    font-weight: 600;
    box-shadow: 0 2px 12px rgba(255, 215, 0, 0.35);
}

/* ========== SECTION TITLE ========== */
.section-title {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:18px 7% 14px;
}
.section-title h2{
    display:flex;
    align-items:center;
    gap:10px;

    font-size:22px;
    font-weight:700;
    color:#FFD700;
}
.section-title a {
    color: var(--muted); font-size:13.5px; text-decoration:none;
    transition: color .25s;
}
.section-title a:hover { color: var(--gold); 
}
.title-icon{
    width:24px;
    height:24px;
    object-fit:contain;
}

/* ========== GAMES GRID ========== */
.games-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(170px,1fr));
    gap:16px;
    padding:0 7% 20px;
}

.game-card{
    position:relative;
    height:210px;
    border-radius:18px;
    overflow:hidden;
    cursor:pointer;

    background:#181818;

    transition:.35s;
    border:1px solid rgba(255,255,255,.05);
}

.game-card:hover{
    transform:translateY(-10px) scale(1.02);

    border-color:rgba(255,215,0,.35);

    box-shadow:
    0 18px 40px rgba(0,0,0,.45),
    0 0 25px rgba(255,215,0,.12);
}

.game-thumb{
    position:absolute;
    inset:0;

    width:100%;
    height:100%;

    object-fit:cover;

    opacity:1;

    filter:
        brightness(.82)
        saturate(1.05);

    transition:.5s ease;
}

.game-card:hover .game-thumb{

    filter:
        brightness(.45)
        saturate(.9);

    transform:scale(1.12);
}
.game-card::before{

    content:"";

    position:absolute;

    top:0;
    left:-120%;

    width:60%;
    height:100%;

    background:
    linear-gradient(
        120deg,
        transparent,
        rgba(255,255,255,.18),
        transparent
    );

    transform:skewX(-25deg);

    transition:.7s;

    z-index:3;

}


.game-card:hover::before{

    left:140%;

}

.game-card::after{

    content:"";

    position:absolute;

    inset:0;

    background:
    linear-gradient(
        to top,
        rgba(0,0,0,.85) 0%,
        rgba(0,0,0,.35) 45%,
        rgba(0,0,0,.05) 100%
    );

    opacity:.7;

    transition:.35s ease;

    z-index:2;
}
.game-card:hover::after{

    background:
    linear-gradient(
        to top,
        rgba(0,0,0,.95),
        rgba(0,0,0,.55)
    );

}
.game-info{
    position:absolute;

    left:14px;
    right:14px;
    bottom:5px;

    z-index:4;

    padding-top:40px;
    transition:.35s ease;

}
.game-card:hover .game-info{

transform:translateY(-3px);

}

.game-info h3{

    font-size:16px;
    color:#fff;
    margin-bottom:8px;

    text-shadow:
        0 2px 4px rgba(0,0,0,.9),
        0 4px 12px rgba(0,0,0,.75);
}

.provider{

    color:#FFD700;
    font-size:10px;

    letter-spacing:1.5px;
    text-transform:uppercase;

    text-shadow:
        0 2px 6px rgba(0,0,0,.8);
}

.rtp-bar{

    margin:12px 0;

    height:5px;
    background:#333;
    border-radius:20px;
    overflow:hidden;
}

.rtp-fill{

    height:100%;
    width:0;
    transition:1s;
    background:linear-gradient(90deg,#FFD700,#ffe95a);
}

.rtp-text{

    font-size:10px;
    color:#aaa;
}
.play-overlay{

    position:absolute;

    top:42%;
    left:50%;

    transform:translate(-50%, -50%) scale(.85);

    opacity:0;

    transition:.35s ease;

    z-index:10;

    pointer-events:none;
}

.game-card:hover .play-overlay{

    opacity:1;

    transform:
    translate(-50%, -50%)
    scale(1);

}

.play-btn{
display:flex;
    align-items:center;
    justify-content:center;

    min-width:110px;
    height:30px;

    padding:0 18px;

    border-radius:999px;

    background:linear-gradient(135deg,#FFD700,#f4c400);

    color:#111;

    font-size:11px;
    font-weight:700;

    white-space:nowrap;      /* supaya PLAY NOW tidak turun */
    gap:6px;

    box-shadow:0 8px 18px rgba(255,215,0,.28);
}

/* GAME BADGE */
.badge {
    position: absolute;
    top: 14px;
    left: 14px;

    padding: 6px 14px;
    border-radius: 20px;

    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;

    z-index: 6;

    backdrop-filter: blur(10px);

    box-shadow:
        0 5px 15px rgba(0,0,0,.35);

    animation: badgePop .3s ease;
}

/* FORCE BADGE COLORS - SAMA DENGAN GAME-LIST */
.badge.hot {
    background: linear-gradient(135deg, #ff3b5c, #ff1744) !important;
    color: #fff !important;
}

.badge.new {
    background: linear-gradient(135deg, #00e676, #00c853) !important;
    color: #06210f !important;
}

.badge.cold {
    background: linear-gradient(135deg, #42a5f5, #1e88e5) !important;
    color: #fff !important;
}

.badge.normal {
    background: linear-gradient(135deg, #7e57c2, #5e35b1) !important;
    color: #fff !important;
}

@keyframes badgePop {
    from {
        opacity: 0;
        transform: scale(.7) translateY(-5px);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ========== LIVE CASINO ========== */
.live-section { padding:5px 7% 60px; }
.live-section .section-title{
    margin:0 0 8px !important;
}
.live-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 20px;
}

.live-card {
    background: linear-gradient(180deg, #15151a, #111114);
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.05);
    transition: all .35s cubic-bezier(.4,0,.2,1);
    position: relative;
}
.live-card:hover {
    border-color: var(--gold-border);
    transform: translateY(-8px);
    box-shadow: 0 24px 50px rgba(0,0,0,.45);
}
.live-card:hover .live-thumb img{
    transform:scale(1.08);
    filter:brightness(.7);
}

.live-thumb{
    position:relative;
    height:180px;
    overflow:hidden;
}
.live-thumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.4s;
}
.live-thumb::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(
        to top,
        rgba(0,0,0,.75),
        rgba(0,0,0,.1)
    );
}

.live-tag{
    position:absolute;
    top:14px;
    left:14px;
    z-index:5;

    padding:6px 14px;

    border-radius:20px;

    background:#ff2d55;
    color:#fff;

    font-size:11px;
    font-weight:700;

    box-shadow:0 0 15px rgba(255,0,0,.45);
}

.live-info{
    padding:18px 20px 22px;
    text-align:center;
}
.live-info h3 { font-size:16.5px; margin-bottom:5px; font-weight:600; }
.live-info .players { font-size:12.5px; color:var(--muted); }

.live-info .btn-join{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    margin:14px auto 0;

    padding:9px 22px;

    background:transparent;
    border:1px solid var(--gold-border);
    color:var(--gold);

    border-radius:25px;

    font-size:12.5px;
    font-weight:600;

    text-decoration:none;

    transition:all .3s ease;
}
.live-info .btn-join:hover {
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    border-color: transparent;
    box-shadow: 0 4px 18px rgba(255,215,0,.3);
}

/* ========== FOOTER ========== */
footer {
    text-align:center;
    padding: 36px 20px;
    background: #0c0c0e;
    color: #555;
    font-size:13px;
    border-top: 1px solid rgba(255,255,255,.04);
}

/* ========== CHAT ========== */
.chat-button {
    position:fixed; bottom:24px; right:24px;
    width:56px; height:56px; border-radius:50%;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color:#111;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; cursor:pointer;
        z-index:1000;
    transition: all .3s cubic-bezier(.4,0,.2,1);
}
.chat-button:hover {
    transform: translateY(-4px) scale(1.06);
    
}

.chat-box {
    position:fixed; right:24px; bottom:94px;
    width:310px; height:400px;
    background: #121216;
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 22px;
    overflow:hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.55);
    z-index:999;
    opacity:0; visibility:hidden;
    transform: translateY(16px) scale(.96);
    transition: all .3s cubic-bezier(.4,0,.2,1);
}
.chat-box.show {
    opacity:1; visibility:visible;
    transform: translateY(0) scale(1);
}

.chat-header {
    display:flex; justify-content:space-between; align-items:center;
    height:58px; padding:0 18px;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color:#111; font-weight:600; font-size:14.5px;
}
.chat-control button {
    width:30px; height:30px; border:none; border-radius:10px;
    background: rgba(0,0,0,.1); color:#111; cursor:pointer; font-size:16px;
    transition: background .2s;
}
.chat-control button:hover { background: rgba(0,0,0,.18); }

.chat-body { height:280px; overflow-y:auto; padding:16px; }
.message-admin {
    background:#1e1e24; padding:11px 15px; border-radius:14px 14px 14px 4px;
    margin-bottom:10px; width:fit-content; max-width:85%; font-size:13.5px;
}
.message-user {
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color:#111; padding:11px 15px; border-radius:14px 14px 4px 14px;
    margin:10px 0 10px auto; width:fit-content; max-width:85%; font-size:13.5px;
}

.chat-footer {
    height:62px; display:flex; align-items:center;
    border-top:1px solid rgba(255,255,255,.06); background:#0e0e12;
}
.chat-footer input {
    flex:1; border:none; background:transparent; color:#fff;
    padding:0 16px; font-size:14px; outline:none;
}
.chat-footer button {
    width:68px; height:42px; margin-right:12px;
    border:none; border-radius:12px;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color:#111; font-weight:700; cursor:pointer;
    transition: transform .2s;
}
.chat-footer button:hover { transform: scale(1.04); }

/* ==========================
        HERO SLIDER
========================== */

.hero-slider{
    width:86%;
    max-width:1400px;
    margin:15px auto 20px;
    position:relative;
}

.slides{

    position:relative;

    height:360px;

    overflow:hidden;

    border-radius:28px;

    border:1px solid rgba(255,215,0,.15);

    box-shadow:
        0 12px 28px rgba(0,0,0,.28),
        0 2px 8px rgba(0,0,0,.12),
        inset 0 1px 0 rgba(255,255,255,.05);
}

.slide{

    position:absolute;

    inset:0;

    opacity:0;

    transition:.7s;

}

.slide.active{

    opacity:1;

}

.slide img{

    width:100%;
    height:100%;

    object-fit:cover;

}

.slide::after{

    content:"";

    position:absolute;

    inset:0;

    background:

    linear-gradient(
        90deg,
        rgba(0,0,0,.78),
        rgba(0,0,0,.15)
    );

}

.slide-content{

    position:absolute;

    left:60px;
    top:50%;

    transform:translateY(-50%);

    z-index:5;

    max-width:500px;

}

.slide-content span{

    color:#FFD700;

    font-size:13px;

    letter-spacing:3px;

    font-weight:700;

}

.slide-content h2{

    font-size:46px;

    margin:12px 0;

}

.slide-content p{

    color:#ddd;

    margin-bottom:25px;

    line-height:1.6;

}

.slide-content button{

    height:46px;

    padding:0 28px;

    border:none;

    border-radius:999px;

    background:linear-gradient(135deg,#FFD700,#ffbf00);

    font-weight:700;

    cursor:pointer;

}

.slider-dots{

    position:absolute;

    bottom:20px;

    left:50%;

    transform:translateX(-50%);

    display:flex;

    gap:10px;

}

.dot{

    width:7px;
    height:7px;

    border-radius:50%;

    background:#666;

    cursor:pointer;

    transition:.3s ease;

}

.dot.active{

    width:20px;
    height:7px;

    border-radius:20px;

    background:#FFD700;

}

/* ========== RESPONSIVE ========== */
@media (max-width: 960px) {
    header { padding: 0 16px; }
    .nav-menu { display:none; }
    .member-hero, .games-grid, .live-section, .section-title {
        padding-left: 20px; padding-right: 20px;
    }
    .section-title { margin-left:0; margin-right:0; }
    .hero-card { padding: 28px 22px 22px; border-radius: 22px; }
    .welcome-row { gap: 18px; margin-bottom: 22px; }
    .welcome-text h1 { font-size: 24px; }
    .promo-banner {
        flex-wrap: wrap;
        gap: 16px;
        padding: 16px;
    }
    .promo-info h2 { font-size: 18px; }
    .promo-timer { min-width: 120px; }
    .countdown { font-size: 18px; }
    .tabs { width: 100%; overflow-x: auto; }
    .balance-card { padding:6px 6px 6px 14px; }
    .balance-info .amount { font-size:14.5px; }
    .games-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap:14px;
    }
}

@media (max-width: 640px) {
    .promo-content { width: 100%; }
    .promo-timer { flex: 1; }
    .btn-claim { width: 100%; }
    .quick-stats { width: 100%; }
    .stat-pill { flex: 1; min-width: 0; padding: 12px 14px; }
    .stat-pill .val { font-size: 16px; }
}
/* ===========================
   Floating Gold Sparkles
=========================== */

.sparkles{
    position:fixed;
    inset:0;
    overflow:hidden;
    pointer-events:none;
    z-index:1;
}

body{
    position:relative;
}

.sparkles span{
    position:absolute;
    display:block;
    border-radius:50%;
    background:radial-gradient(circle,#fff 0%,#FFD700 60%,transparent 100%);
    box-shadow:
        0 0 8px rgba(255,215,0,.8),
        0 0 20px rgba(255,215,0,.45);

    animation:
        floatSpark linear infinite,
        twinkle ease-in-out infinite;
}

/* ukuran */
.sparkles span:nth-child(odd){
    width:3px;
    height:3px;
}

.sparkles span:nth-child(even){
    width:5px;
    height:5px;
}

/* posisi */
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

/* naik perlahan */
@keyframes floatSpark{

    from{
        transform:translateY(0) translateX(0) scale(.8);
    }

    25%{
        transform:translateY(-25vh) translateX(12px);
    }

    50%{
        transform:translateY(-50vh) translateX(-10px);
    }

    75%{
        transform:translateY(-75vh) translateX(15px);
    }

    to{
        transform:translateY(-120vh) translateX(-8px);
    }

}

/* berkedip */
@keyframes twinkle{

    0%,100%{
        opacity:.15;
        filter:blur(.3px);
        transform:scale(.8);
    }

    50%{
        opacity:1;
        filter:blur(0);
        transform:scale(1.6);
    }

}
.favorite{

    position:absolute;

    top:14px;
    right:14px;

    width:36px;
    height:36px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:
    rgba(0,0,0,.55);

    color:white;

    font-size:20px;

    cursor:pointer;

    z-index:7;

    backdrop-filter:blur(8px);

    transition:.25s ease;

}


.favorite:hover{

    background:#FFD700;

    color:#111;

    transform:scale(1.15);

}


.favorite.active{

    background:#ff3b5c;

    color:white;

}
.winner-toast{

    position:fixed;

    left:24px;
    bottom:24px;

    width:330px;

    display:flex;
    align-items:center;

    gap:15px;

    padding:15px 18px;


    background:
    linear-gradient(
        135deg,
        #16161c,
        #0e0e12
    );


    border:

    1px solid rgba(255,215,0,.25);


    border-radius:20px;


    box-shadow:

    0 20px 50px rgba(0,0,0,.55),

    0 0 25px rgba(255,215,0,.12);


    transform:
    translateX(-120%);


    opacity:0;


    transition:
    .45s cubic-bezier(.4,0,.2,1);


    z-index:9999;

}


.winner-toast.show{

    transform:
    translateX(0);

    opacity:1;

}



.winner-icon{

    width:52px;
    height:52px;

    border-radius:50%;


    display:flex;

    align-items:center;

    justify-content:center;


    font-size:25px;


    background:

    linear-gradient(
        135deg,
        #FFD700,
        #dba800
    );


    color:#111;

}



.winner-user{

    color:#FFD700;

    font-size:14px;

    font-weight:700;

}



.winner-text{

    color:#aaa;

    font-size:12px;

    margin-top:2px;

}



.winner-money{

    color:#2dff6b;

    font-size:16px;

    font-weight:700;

    margin-top:3px;

}



@media(max-width:600px){

    .winner-toast{

        left:15px;
        right:15px;
        width:auto;

    }

}
.badge.normal{
background:#3498db;
color:#fff;
}

.logout-modal{
    position:fixed;
    inset:0;
    filter:none !important;
    display:flex;
    justify-content:center;
    align-items:center;

    background:rgba(0,0,0,.25);

    /* Blur seluruh halaman */
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);

    opacity:0;
    visibility:hidden;

    transition:.3s ease;

    z-index:99999;
}

.logout-modal.show{
    opacity:1;
    visibility:visible;
}

.logout-box{
    width:360px;
    background:#17171c;
    border:1px solid rgba(255,215,0,.25);
    border-radius:20px;
    padding:28px;
    text-align:center;
}

.logout-box h3{
    color:#FFD700;
    margin-bottom:12px;
}

.logout-box p{
    color:#bbb;
    margin-bottom:24px;
}

.logout-actions{
    display:flex;
    gap:12px;
}

.logout-actions button{
    flex:1;
    height:42px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}

.cancel-btn{
    background:#333;
    color:#fff;
}

.logout-btn{
    background:#ff3b5c;
    color:#fff;
}

.logout-btn:hover{
    background:#ff2147;
}
body.modal-open > *:not(.logout-modal){
    filter:blur(6px);
    transition:filter .3s ease;
    pointer-events:none;
    user-select:none;
}
.search-modal{

    position:fixed;
    inset:0;

    background:rgba(0,0,0,.55);

    backdrop-filter:blur(12px);

    display:flex;
    justify-content:center;
    align-items:center;

    padding-top:25px;

    opacity:0;
    visibility:hidden;

    transition:.25s;

    z-index:999999;

}

.search-modal.show{

    opacity:1;
    visibility:visible;

}

.search-box{

    width:900px;
    max-width:95vw;

    height:75vh;       /* <-- hampir penuh layar */
    max-height:850px;

    display:flex;
    flex-direction:column;

    background:#16161c;

    border-radius:22px;
    overflow:hidden;

    border:1px solid rgba(255,255,255,.08);

}
.search-result{
    flex:1;
    overflow-y:auto;
    padding:20px;
}
.search-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(150px,1fr));
    gap:16px;
}

.search-card{
    background:#1a1a22;
    border:1px solid rgba(255,255,255,.06);
    border-radius:16px;
    overflow:hidden;
    cursor:pointer;
    transition:
        opacity .35s,
        transform .35s,
        filter .35s;

    opacity:0;
    transform:translateY(20px) scale(.96);
    filter:blur(6px);
}
.search-card.show{
    opacity:1;

    transform:
        translateY(0)
        scale(1);

    filter:blur(0);
}
.search-card:hover{
    transform:translateY(-5px) scale(1.02);
    border-color:#FFD700;
    box-shadow:0 10px 25px rgba(255,215,0,.18);
}
.search-card img{
    width:100%;
    height:140px;
    object-fit:cover;
    display:block;
}
.search-card-info{
    padding:12px;
}

.search-card-info h4{
    color:#fff;
    font-size:14px;
    margin-bottom:5px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.search-card-provider{
    color:#999;
    font-size:11px;
}

.search-card-rtp{
    margin-top:6px;
    color:#2dff6b;
    font-size:12px;
    font-weight:600;
}
.search-header{

    display:flex;
    align-items:center;
    
    border-bottom:1px solid rgba(255,255,255,.06);

}

.search-header input{

    flex:1;

    height:58px;

    background:none;
    border:none;

    padding:0 20px;

    color:white;

    font-size:16px;

    outline:none;

}

.search-header button{

    width:58px;
    height:58px;

    border:none;

    background:none;

    color:white;

    cursor:pointer;

    font-size:22px;

}

.search-section{
    padding:18px;
}

.search-title{
    color:#FFD700;
    font-size:12px;
    font-weight:700;
    letter-spacing:1px;
    margin-bottom:12px;
    text-transform:uppercase;
}

.search-tags{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.search-tag{
    padding:9px 15px;
    border-radius:999px;
    background:#23232c;
    color:#ddd;
    font-size:13px;
    cursor:pointer;
    transition:.25s;
}

.search-tag:hover{
    background:#FFD700;
    color:#111;
}

.search-item{
    display:flex;
    gap:15px;
    align-items:center;
    padding:12px 18px;
    transition:.25s;
    cursor:pointer;
}

.search-item:hover{
    background:#212129;
}

.search-item img{
    width:72px;
    height:72px;
    border-radius:12px;
    object-fit:cover;
}

.search-meta{
    flex:1;
}

.search-meta h4{
    color:#fff;
    margin-bottom:4px;
}

.search-provider{
    color:#999;
    font-size:12px;
}

.search-rtp{
    color:#2dff6b;
    font-size:12px;
    margin-top:5px;
}

.search-empty{
    padding:50px;
    text-align:center;
    color:#777;
}

.search-item{

    display:flex;
    align-items:center;

    gap:15px;

    padding:12px 18px;

    cursor:pointer;

    transition:.2s;

}

.search-item:hover{

    background:#202028;

}

.search-item img{

    width:70px;
    height:70px;

    object-fit:cover;

    border-radius:10px;

}

.search-item h4{

    color:white;

    font-size:15px;

}

.search-item p{

    color:#999;

    font-size:12px;

}
.search-link{
    display:flex;
    align-items:center;
    gap:8px;
}

.search-icon{
    width:18px;
    height:18px;
    object-fit:contain;

    /* ubah icon hitam jadi putih */
    filter: brightness(0) invert(1);

    transition:.25s;
}

.search-link:hover .search-icon{
    filter:
        brightness(0)
        invert(79%)
        sepia(99%)
        saturate(620%)
        hue-rotate(358deg)
        brightness(102%)
        contrast(103%);
}
.search-btn{
    width:42px;
    height:42px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:50%;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);

    transition:.25s;
}

.search-btn img{
    width:18px;
    height:18px;

    filter:brightness(0) invert(1);
}

.search-btn:hover{
    background:#FFD700;
    border-color:#FFD700;
    transform:translateY(-2px);
}

.search-btn:hover img{
    filter:none;
}
.search-hero{
    width:100%;
    padding:95px 30px;
    text-align:center;

    background:
        radial-gradient(circle at top,
        rgba(255,215,0,.12),
        transparent 70%),
        linear-gradient(180deg,#1a1a22,#141419);

    border-bottom:1px solid rgba(255,255,255,.06);
    border-radius:16px;
    box-sizing:border-box;
}


.search-hero-icon{
    width:55px;
    height:55px;

    margin:0 auto 12px;

    border-radius:16px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:26px;

    background:linear-gradient(135deg,#FFD700,#e6b800);

    color:#111;
}

.search-hero h2{
    color:#fff;
    font-size:21px;
    margin-bottom:6px;
}

.search-hero p{

    color:#999;

    font-size:13px;

    margin-bottom:22px;

}

.hero-tags{

    display:flex;

    justify-content:center;

    flex-wrap:wrap;

    gap:10px;

}

.hero-pill{

    padding:10px 16px;

    border-radius:999px;

    background:#23232d;

    color:#ddd;

    font-size:13px;

    transition:.25s;

}
.hero-pill.active{

    background:#FFD700;

    color:#111;

    font-weight:700;

}

.hero-pill:hover{

    background:#FFD700;

    color:#111;

}
.search-back,
.search-close{

    width:58px;
    height:58px;

    border:none;
    background:none;

    color:white;

    cursor:pointer;

    font-size:22px;

    display:flex;
    align-items:center;
    justify-content:center;

    transition:.25s;
}


.search-back:hover,
.search-close:hover{

    color:#FFD700;

}
.search-back{
    opacity:0;
    pointer-events:none;
}

.search-modal.searching .search-back{
    opacity:1;
    pointer-events:auto;
}
/* ==========================
   WELCOME MEMBER POPUP
========================== */

.welcome-modal{
    position:fixed;
    inset:0;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:20px;

    background:rgba(0,0,0,.68);

    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);

    opacity:0;
    visibility:hidden;

    transition:.35s ease;

    z-index:9999999;
}

.welcome-modal.show{
    opacity:1;
    visibility:visible;
}


/* POPUP */

.welcome-popup{
    position:relative;

    width:430px;
    max-width:100%;

    padding:38px 34px 30px;

    text-align:center;

    background:
        radial-gradient(
            circle at top,
            rgba(255,215,0,.12),
            transparent 40%
        ),
        linear-gradient(
            145deg,
            #19191f,
            #101014
        );

    border:1px solid rgba(255,215,0,.28);

    border-radius:28px;

    box-shadow:
        0 30px 80px rgba(0,0,0,.65),
        0 0 45px rgba(255,215,0,.08);

    transform:
        translateY(30px)
        scale(.92);

    transition:
        .4s cubic-bezier(.4,0,.2,1);

    overflow:hidden;
}


/* gold glow */

.welcome-popup::before{
    content:"";

    position:absolute;

    width:280px;
    height:280px;

    top:-190px;
    left:50%;

    transform:translateX(-50%);

    background:#FFD700;

    filter:blur(100px);

    opacity:.16;

    pointer-events:none;
}


.welcome-modal.show .welcome-popup{

    transform:
        translateY(0)
        scale(1);
}


/* CLOSE */

.welcome-close{

    position:absolute;

    top:16px;
    right:16px;

    width:34px;
    height:34px;

    border:none;
    border-radius:50%;

    background:rgba(255,255,255,.06);

    color:#999;

    font-size:15px;

    cursor:pointer;

    transition:.2s;
}

.welcome-close:hover{

    background:rgba(255,255,255,.12);

    color:white;

    transform:rotate(90deg);
}


/* CROWN */

.welcome-crown{

    width:72px;
    height:72px;

    margin:0 auto 18px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:38px;

    border-radius:50%;

    background:
        linear-gradient(
            145deg,
            #FFD700,
            #d49f00
        );

    box-shadow:
        0 10px 30px rgba(255,215,0,.25);
}


/* LABEL */

.welcome-label{

    margin-bottom:8px;

    color:#FFD700;

    font-size:10px;

    font-weight:700;

    letter-spacing:2.5px;
}


/* TITLE */

.welcome-popup h2{

    margin-bottom:10px;

    color:white;

    font-size:25px;

    font-weight:700;
}

.welcome-popup h2 span{

    color:#FFD700;
}


/* DESCRIPTION */

.welcome-desc{

    max-width:340px;

    margin:0 auto 24px;

    color:#92929d;

    font-size:13px;

    line-height:1.7;
}


/* PROMO */

.welcome-promo{

    display:flex;
    align-items:center;

    gap:15px;

    padding:16px;

    margin-bottom:20px;

    text-align:left;

    background:
        rgba(255,215,0,.055);

    border:
        1px solid rgba(255,215,0,.16);

    border-radius:16px;
}


.welcome-gift{

    width:52px;
    height:52px;

    flex-shrink:0;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:14px;

    background:
        linear-gradient(
            145deg,
            #FFD700,
            #dca800
        );

    font-size:25px;
}


.welcome-promo-info{

    display:flex;
    flex-direction:column;
}


.welcome-promo-info small{

    color:#888;

    font-size:9px;

    font-weight:600;

    letter-spacing:1.5px;
}


.welcome-promo-info strong{

    margin-top:2px;

    color:white;

    font-size:16px;
}


.welcome-promo-info p{

    color:#FFD700;

    font-size:12px;
}


/* CLAIM BUTTON */

.welcome-claim{

    width:100%;
    height:48px;

    border:none;
    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            #FFD700,
            #e7b600
        );

    color:#111;

    font-size:13px;
    font-weight:700;

    cursor:pointer;

    box-shadow:
        0 8px 24px rgba(255,215,0,.18);

    transition:.25s;
}

.welcome-claim:hover{

    transform:translateY(-2px);

    box-shadow:
        0 12px 30px rgba(255,215,0,.28);
}


/* NANTI SAJA */

.welcome-later{

    margin-top:14px;

    border:none;
    background:none;

    color:#777;

    font-size:12px;

    cursor:pointer;

    transition:.2s;
}

.welcome-later:hover{
    color:#bbb;
}


/* MOBILE */

@media(max-width:500px){

    .welcome-popup{
        padding:34px 22px 26px;
    }

    .welcome-popup h2{
        font-size:21px;
    }

}
/* ==========================
   MODERN CUSTOM SCROLLBAR
========================== */

/* Chrome, Edge, Safari */
.search-result::-webkit-scrollbar,
.chat-body::-webkit-scrollbar{
    width:8px;
}

.search-result::-webkit-scrollbar-track,
.chat-body::-webkit-scrollbar-track{

    background:transparent;

}


.search-result::-webkit-scrollbar-thumb,
.chat-body::-webkit-scrollbar-thumb{

    background:
    linear-gradient(
    180deg,
    #888888,
    #444444
    );

    border-radius:20px;

    border:2px solid transparent;
    background-clip:padding-box;

}


.search-result::-webkit-scrollbar-thumb:hover,
.chat-body::-webkit-scrollbar-thumb:hover{

    background:
    linear-gradient(
    180deg,
    #aaaaaa,
    #666666
    );

}


/* Firefox */
.search-result,
.chat-body{

    scrollbar-width:thin;

    scrollbar-color:
    #aaaaaa
    transparent;

}
.toast{
    position:fixed;
    top:95px;
    right:30px;

    display:flex;
    align-items:center;
    gap:14px;

    min-width:320px;
    max-width:420px;

    padding:16px 18px;

    background:rgba(20,20,25,.88);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);

    border:1px solid rgba(255,215,0,.25);

    border-radius:18px;

    box-shadow:
        0 20px 45px rgba(0,0,0,.45),
        0 0 20px rgba(255,215,0,.08);

    transform:translateX(450px);

    opacity:0;

    transition:
        transform .45s cubic-bezier(.2,.9,.3,1),
        opacity .35s;

    z-index:999999;
}

.toast.show{

    transform:translateX(0);

    opacity:1;

}

.toast.hide{

    transform:translateX(450px);

    opacity:0;

}

.toast-icon{

    width:46px;
    height:46px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:linear-gradient(135deg,#FFD700,#ffbf00);

    color:#111;

    font-weight:700;

    font-size:20px;

    box-shadow:0 0 18px rgba(255,215,0,.45);

}

.toast-title{

    color:#fff;

    font-weight:700;

    margin-bottom:3px;

}

.toast-message{

    color:#bcbcbc;

    font-size:13px;

    line-height:1.4;

}
.toast-icon.error{

    background:linear-gradient(135deg,#ff3b5c,#ff1744);

    color:white;

    box-shadow:0 0 18px rgba(255,59,92,.45);

}

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


.modal-content{

    width:420px;
    max-width:90%;

    background:#15151c;

    border:1px solid rgba(255,215,0,.25);

    border-radius:24px;

    padding:30px;

    box-shadow:
    0 25px 70px rgba(0,0,0,.6);

    animation:modalShow .25s ease;

}


@keyframes modalShow{

    from{
        opacity:0;
        transform:scale(.9);
    }

    to{
        opacity:1;
        transform:scale(1);
    }

}


.close{

    position:absolute;

    margin-left:340px;

    font-size:30px;

    color:#aaa;

    cursor:pointer;

}


.close:hover{
    color:#FFD700;
}
.password-field{
    position: relative;
}

.password-field input{
    width:100%;
    padding-right:50px;
}

.eye{
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    color:#8d8d8d;
    transition:.25s;
}

.eye:hover{
    color:#FFD700;
}

.eye svg{
    width:22px;
    height:22px;
}

/* ========== GLOBAL BUTTON STYLES (MATCH PROFILE.PHP) ========== */
.btn-outline {
    padding: 11px 22px;
    border-radius: 14px;
    border: 1px solid var(--gold-border);
    background: transparent;
    color: var(--gold);
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
    transition: all .25s;
    text-align: center;
    text-decoration: none;
}
.btn-outline:hover {
    background: rgba(255,215,0,.1);
    transform: translateY(-2px);
}
.btn-primary {
    padding: 11px 22px;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    transition: all .25s;
    box-shadow: 0 4px 18px rgba(255,215,0,.25);
    text-align: center;
    text-decoration: none;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 26px rgba(255,215,0,.4);
}


/* ========== DEPOSIT MODAL ========== */
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
    width: 19px;
    height: 19px;
    color: #FFD700;
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
    width: 27px;
    height: 27px;
    border-radius: 7px;
    background: rgba(255,215,0,.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    color: #FFD700;
}
.deposit-method-icon svg {
    width: 14px;
    height: 14px;
    display: block;
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



/* ========== ICON SVGs ========== */

.title-icon.ico {
    width: 24px;
    height: 24px;
    color: #FFD700;
}

.ico {
    width: 1.15em;
    height: 1.15em;
    display: inline-block;
    vertical-align: -0.2em;
    flex-shrink: 0;
}
.dropdown-menu a .ico {
    width: 18px;
    height: 18px;
}
.promo-icon .ico,
.welcome-gift .ico,
.welcome-crown .ico,
.search-hero-icon .ico,
.winner-icon .ico,
.deposit-method-icon .ico {
    width: 28px;
    height: 28px;
}
.welcome-crown .ico { width: 36px; height: 36px; color: #111; }
.promo-icon .ico { color: #111; }
.welcome-gift .ico { color: #111; }
.chat-button .ico { width: 26px; height: 26px; }
.chat-header .ico { width: 18px; height: 18px; }
.section-title h2 .ico { width: 22px; height: 22px; }
.tab .ico { width: 14px; height: 14px; margin-right: 4px; }
.hero-pill .ico { width: 14px; height: 14px; margin-right: 4px; }
.play-btn .ico { width: 12px; height: 12px; }
.favorite .ico { width: 18px; height: 18px; }
.toast-icon .ico { width: 22px; height: 22px; }
.logout-box h3 .ico { width: 20px; height: 20px; margin-right: 6px; }
.deposit-method-icon .ico { width: 20px; height: 20px; color: #FFD700; }
.welcome-claim .ico { width: 16px; height: 16px; margin-right: 6px; vertical-align: -0.15em; }
.welcome-close .ico,
.search-close .ico,
.search-back .ico,
.chat-control button .ico,
.deposit-modal .close .ico {
    width: 16px;
    height: 16px;
}


/* ========== HISTORY / BONUS MODALS ========== */
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
.bonus-modal .modal-header h3 .ico {
    width: 20px;
    height: 20px;
}

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
.history-item-icon.bet { background: rgba(255,215,0,.1); color: #FFD700; }
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
.history-item-info span {
    font-size: 11.5px;
    color: #777;
}

.history-item-amount {
    text-align: right;
    flex-shrink: 0;
}
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
.bonus-card-meta .expiry {
    font-size: 11px;
    color: #777;
}
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

.history-body::-webkit-scrollbar,
.bonus-body::-webkit-scrollbar { width: 6px; }
.history-body::-webkit-scrollbar-thumb,
.bonus-body::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 10px;
}

@media (max-width: 480px) {
    .history-modal .modal-content,
    .bonus-modal .modal-content { max-height: 90vh; }
    .history-item { padding: 12px 10px; gap: 10px; }
    .history-item-amount .val { font-size: 13px; }
}

</style>
</head>

<body>

<div class="sparkles">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
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
            <a href="member.php" class="active">Lobby</a>
           <a href="game-list.php">Games</a>
            <a href="promosi.php">Promosi</a>
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

<div id="toast" class="toast"><div class="toast-icon" id="toastIcon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div class="toast-content"><div class="toast-title">Berhasil</div><div class="toast-message"></div></div></div>

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
                <input
                    type="text"
                    id="depositAmount"
                    name="amount"
                    inputmode="numeric"
                    placeholder="0"
                    autocomplete="off"
                    required
                >
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
                    <div class="deposit-method-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg></div>
                    <div class="deposit-method-text">
                        <strong>Transfer Bank</strong>
                        <span>BCA · Mandiri · BNI</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="ewallet">
                    <div class="deposit-method-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></div>
                    <div class="deposit-method-text">
                        <strong>E-Wallet</strong>
                        <span>DANA · OVO · GoPay</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="qris">
                    <div class="deposit-method-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M17 17h3v3h-3z"/><path d="M14 20h3"/></svg></div>
                    <div class="deposit-method-text">
                        <strong>QRIS</strong>
                        <span>Scan &amp; bayar instan</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="pulsa">
                    <div class="deposit-method-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20h.01"/><path d="M7 20v-4"/><path d="M12 20v-8"/><path d="M17 20V8"/><path d="M22 4v16"/></svg></div>
                    <div class="deposit-method-text">
                        <strong>Pulsa</strong>
                        <span>Telkomsel · XL · Indosat</span>
                    </div>
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


<!-- ================= HERO SLIDER ================= -->

<section class="hero-slider">

    <div class="slides">

        <div class="slide active">
            <img src="../assets/banner/banner1.png">

            <div class="slide-content">
                <span>WELCOME BONUS</span>
                <h2>Bonus Deposit 100%</h2>
                <p>Dapatkan bonus hingga Rp1.000.000 untuk member baru.</p>

                <button>CLAIM SEKARANG</button>
            </div>
        </div>

        <div class="slide">
            <img src="../assets/banner/banner2.png">

            <div class="slide-content">
                <span>PRAGMATIC PLAY</span>
                <h2>Turnamen Slot</h2>
                <p>Total hadiah Rp250.000.000 setiap minggu.</p>

                <button>IKUT TURNAMEN</button>
            </div>
        </div>

        <div class="slide">
            <img src="../assets/banner/banner3.png">

            <div class="slide-content">
                <span>LIVE CASINO</span>
                <h2>Main Live Dealer</h2>
                <p>Blackjack, Baccarat, Roulette dan lainnya.</p>

                <button>PLAY NOW</button>
            </div>
        </div>

    </div>

    <div class="slider-dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>

</section>

<!-- HERO -->
<section class="member-hero">

    <div class="hero-card">

        <div class="welcome-row">
            <div class="welcome-text">
                <h1>Selamat datang, <span><?= htmlspecialchars($fullname) ?></span></h1>
                <p>Siap meraih kemenangan hari ini? Pilih game favoritmu di bawah.</p>
            </div>

            <div class="quick-stats">
                <div class="stat-pill">
                    <div class="val"><?= number_format($points) ?></div>
                    <div class="lbl">VIP Points</div>
                </div>

                <div class="stat-pill">
                    <div class="val"><?= htmlspecialchars($level) ?></div>
                    <div class="lbl">Member Level</div>
                </div>

                <div class="stat-pill">
                    <div class="val">12.4K</div>
                    <div class="lbl">Online</div>
                </div>
            </div>
        </div>

        <div class="promo-banner">
            <div class="promo-content">
                <div class="promo-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg></div>
                <div class="promo-info">
                    <span class="promo-label">Bonus Hari Ini</span>
                    <h2>Deposit Bonus 100%</h2>
                    <p>Min Rp50.000 · Maks bonus Rp1.000.000</p>
                </div>
            </div>

            <div class="promo-timer">
                <small>Berakhir dalam</small>
                <div class="countdown">09 : 32 : 18</div>
            </div>

            <button class="btn-claim" type="button">Klaim Sekarang</button>
        </div>

        <div class="tabs">
            <button class="tab active" data-cat="all">Semua</button>
            <button class="tab" data-cat="slots">Slots</button>
            <button class="tab" data-cat="live">Live Casino</button>
            <button class="tab" data-cat="table">Table Games</button>
            <button class="tab" data-cat="crash">Crash & Instant</button>
            <button class="tab" data-cat="hot"><svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M12 23c-4.4 0-8-3-8-7.5 0-2.5 1.2-4.7 3-6.2.5 2.2 1.8 3.4 3 4-1-4 2-8 5-10 1 3 3 5 5 6.5 1.5 1.2 2.5 2.8 2.5 5.2C22.5 20 18.4 23 12 23z"/></svg> Hot</button>
        </div>

    </div>
</section>



<!-- GAMES -->
<div class="section-title">
    <h2>
    <svg class="ico title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg>
    Game Populer
    </h2>
    <a href="#">Lihat Semua →</a>
</div>

<div class="games-grid" id="gamesGrid"></div>

<!-- LIVE -->
<section class="live-section">

    <div class="section-title" style="margin:0 0 22px; padding:0;">
        <h2><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg> Live Casino</h2>
        <a href="#">Lihat Semua →</a>
    </div>

    <div class="live-grid">

        <div class="live-card">
            <div class="live-thumb">
                <img src="../assets/game/black.png" alt="">
                <span class="live-tag">● LIVE</span>
            </div>

            <div class="live-info">
                <h3>Blackjack VIP</h3>
                <div class="players">124 Players Online</div>
                <a href="#" class="btn-join">Join Table</a>
            </div>
        </div>

        <div class="live-card">
            <div class="live-thumb">
                <img src="../assets/game/roulette.jpg" alt="">
                <span class="live-tag">● LIVE</span>
            </div>

            <div class="live-info">
                <h3>Lightning Roulette</h3>
                <div class="players">287 Players Online</div>
                <a href="#" class="btn-join">Join Table</a>
            </div>
        </div>

        <div class="live-card">
            <div class="live-thumb">
                <img src="../assets/game/crazy.png" alt="">
                <span class="live-tag">● LIVE</span>
            </div>

            <div class="live-info">
                <h3>Crazy Time</h3>
                <div class="players">512 Players Online</div>
                <a href="#" class="btn-join">Join Table</a>
            </div>
        </div>

        <div class="live-card">
            <div class="live-thumb">
                <img src="../assets/game/bacc.png" alt="">
                <span class="live-tag">● LIVE</span>
            </div>

            <div class="live-info">
                <h3>Baccarat Squeeze</h3>
                <div class="players">89 Players Online</div>
                <a href="#" class="btn-join">Join Table</a>
            </div>
        </div>

    </div>

</section>

<div class="welcome-modal" id="welcomeModal">

    <div class="welcome-popup">

        <button class="welcome-close" onclick="closeWelcomePopup()">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="welcome-crown">
            <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17l2-10 5 5 2-8 2 8 5-5 2 10H3z"/><rect x="3" y="17" width="18" height="3" rx="1"/></svg>
        </div>

        <div class="welcome-label">
            WELCOME TO ROYAL KNIGHT'S
        </div>

        <h2>
            Selamat Datang,
            <span><?= htmlspecialchars($username) ?></span>
        </h2>

        <p class="welcome-desc">
            Senang melihat Anda kembali. Nikmati berbagai game
            dan promo spesial yang tersedia hari ini.
        </p>

        <div class="welcome-promo">

            <div class="welcome-gift">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
            </div>

            <div class="welcome-promo-info">
                <small>PROMO HARI INI</small>

                <strong>
                    Bonus Deposit 100%
                </strong>

                <p>
                    Hingga Rp1.000.000
                </p>
            </div>

        </div>

        <button
            class="welcome-claim"
            onclick="location.href='deposit.php'"
        >
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg> KLAIM BONUS
        </button>

        <button
            class="welcome-later"
            onclick="closeWelcomePopup()"
        >
            Nanti Saja
        </button>

    </div>

</div>

</section>

<footer>
    © 2026 Royal Knight's • Bermain secara bertanggung jawab • 18+
</footer>

<!-- CHAT -->
<div class="chat-button" onclick="toggleChat()"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
<div class="chat-box" id="chatBox">
    <div class="chat-header">
        <div><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Live Chat Admin</div>
        <div class="chat-control">
            <button onclick="minimizeChat()">−</button>
            <button onclick="closeChat()"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="message-admin">Halo <?= htmlspecialchars($username) ?>, ada yang bisa kami bantu?</div>
    </div>
    <div class="chat-footer">
        <input type="text" id="message" placeholder="Ketik pesan..." onkeypress="if(event.key==='Enter')sendMessage()">
        <button onclick="sendMessage()">Kirim</button>
    </div>
</div>

<div class="winner-toast" id="winnerToast">

    <div class="winner-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M17 4h2a2 2 0 0 1 2 2v1a4 4 0 0 1-4 4M7 4H5a2 2 0 0 0-2 2v1a4 4 0 0 0 4 4"/></svg></div>

    <div class="winner-info">
        <div class="winner-user"></div>
        <div class="winner-text"></div>
        <div class="winner-money"></div>
    </div>

</div>

<div class="search-modal" id="searchModal">

    <div class="search-box">

        <div class="search-header">

   <button class="search-back" onclick="backSearch()">
    ←
    </button>

    <input
        type="text"
        id="popupSearch"
        placeholder="Cari permainan..."
    >

    <button class="search-close" onclick="closeSearch()">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

</div>

        <div class="search-result" id="searchResult">

        </div>

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

<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <h3><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Logout</h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>

        <div class="logout-actions">
            <button class="cancel-btn" onclick="closeLogout()">Batal</button>
            <button class="logout-btn" onclick="logoutNow()">Keluar</button>
        </div>
    </div>
</div>

<script>
  const games = <?= json_encode($games, JSON_UNESCAPED_UNICODE); ?>;
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

games.forEach(g=>{

    g.img = gameImages[g.name] ?? "../assets/game/default.jpg";

});

function renderGames(cat = "all") {

    const grid = document.getElementById("gamesGrid");

    let filtered = games;

    if (cat === "hot") {
        filtered = games.filter(g => g.badge === "hot");
    } else if (cat !== "all") {
        filtered = games.filter(g => g.cat === cat);
    }

    grid.innerHTML = filtered.map(g => {

        let color;

       if (g.rtp >= 96.5) {
            color = "#2dff6b";
        } else if (g.rtp >= 95) {
            color = "#7CFF3B";
        } else if (g.rtp >= 92) {
            color = "#FFD700";
        } else if (g.rtp >= 88) {
            color = "#FFA726";
        } else {
            color = "#ff3b5c";
}

        return `
        <div class="game-card" onclick="playGame(${g.id})">

            ${g.badge ? `
                <div class="badge ${g.badge}">
                    ${g.badge.toUpperCase()}
                </div>
            ` : ""}

            <div class="favorite" onclick="toggleFavorite(event,this)">
                <svg class="ico heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </div>

            <img src="${g.img}" class="game-thumb">

            <div class="play-overlay">
                <div class="play-btn">
                    <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> PLAY
                </div>
            </div>

            <div class="game-info">

                <div class="provider">${g.provider}</div>

                <h3>${g.name}</h3>

                <div class="rtp-bar">
                    <div class="rtp-fill"
                         data-width="${g.rtp}"
                         style="width:0%; background:${color};">
                    </div>
                </div>

                <div class="rtp-text">
                    RTP ${Number(g.rtp).toFixed(2)}%
                </div>

            </div>

        </div>
        `;
    }).join("");

    animateRTP();
}
function animateRTP() {

    requestAnimationFrame(() => {

        document.querySelectorAll(".rtp-fill").forEach(bar => {
            bar.style.width = bar.dataset.width + "%";
        });

    }, 100);

}
document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener("click", () => {
        document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
        tab.classList.add("active");
        renderGames(tab.dataset.cat);
    });
});

function toggleUserMenu() {
    document.getElementById("userMenu").classList.toggle("show");
}
document.addEventListener("keydown",e=>{

if(e.key==="Escape")
closeSearch();

});
document.addEventListener("click", e => {
    if (!e.target.closest(".user-dropdown")) {
        document.getElementById("userMenu")?.classList.remove("show");
    }
});

window.addEventListener("scroll", () => {
    document.querySelector("header").classList.toggle("scrolled", window.scrollY > 20);
});

function toggleChat(){ document.getElementById("chatBox").classList.toggle("show"); }
function minimizeChat(){ document.getElementById("chatBox").classList.remove("show"); }
function closeChat(){ document.getElementById("chatBox").classList.remove("show"); }

function sendMessage(){
    const input = document.getElementById("message");
    const text = input.value.trim();
    if (!text) return;
    const body = document.getElementById("chatBody");
    body.innerHTML += `<div class="message-user">${text}</div><div class="message-admin">Terima kasih. Pesan Anda telah diterima.</div>`;
    input.value = "";
    body.scrollTop = body.scrollHeight;
}

function playGame(id) {
   window.location.href =
    "game.php?id="+id;
}

const DURATION = 10 * 60 * 60 * 1000; // 10 jam

let endTime = localStorage.getItem("promoEnd");

if(!endTime){
    endTime = Date.now() + DURATION;
    localStorage.setItem("promoEnd", endTime);
}

function updateTimer(){

    let diff = Math.floor((endTime - Date.now()) / 1000);

    if(diff < 0){
        diff = 0;
    }

    const h = Math.floor(diff/3600);
    const m = Math.floor((diff%3600)/60);
    const s = diff%60;

    document.querySelector(".countdown").textContent =
        `${String(h).padStart(2,"0")} : ${String(m).padStart(2,"0")} : ${String(s).padStart(2,"0")}`;

}

updateTimer();
setInterval(updateTimer,1000);

const usernames = [
"R***23",
"A***77",
"B***98",
"C***11",
"D***66",
"E***44",
"F***92",
"G***10",
"H***58",
"I***37",
"J***09",
"K***71",
"L***28",
"M***88",
"N***63",
"O***21",
"P***19",
"Q***82",
"R***45",
"S***99",
"T***52",
"U***13",
"V***75",
"W***31",
"X***84",
"Y***67",
"Z***55",
"King88",
"Lucky99",
"Boss777",
"DragonX",
"NightWolf",
"Royal88",
"SlotMan",
"SpinKing"
];

const winnerNames = [
    "R***23",
    "Lucky88",
    "King***",
    "A***77",
    "Boss99",
    "S***11",
    "Hoki123",
    "RajaSpin",
    "B***89",
    "GoldenX",
    "MegaWin",
    "Zeus99",
    "Sultan88",
    "Player01",
    "Mystic77",
    "K***55",
    "NightFox",
    "M***88",
    "SpinKing",
    "Fortuna"
];

// supaya tidak muncul nama yang sama 2x berturut-turut
let lastWinner = "";

function randomWinnerName(){

    let name;

    do{
        name = winnerNames[Math.floor(Math.random() * winnerNames.length)];
    }while(name === lastWinner);

    lastWinner = name;

    return name;
}

function randomMoney(){

    const amount =
        Math.floor(Math.random() * 45000000) + 500000;

    return "Rp " + amount.toLocaleString("id-ID");
}

function randomGame(){

    return games[Math.floor(Math.random() * games.length)].name;
}

function showWinner(){

    const toast = document.getElementById("winnerToast");

    toast.querySelector(".winner-user").innerHTML =
        randomWinnerName();

    toast.querySelector(".winner-text").innerHTML =
        "Menang di " + randomGame();

    toast.querySelector(".winner-money").innerHTML =
        randomMoney();

    toast.classList.add("show");

    setTimeout(()=>{
        toast.classList.remove("show");
    },5000);

}

// muncul pertama setelah 5 detik
setTimeout(showWinner,5000);

// setiap 12 detik
setInterval(showWinner,12000);

function toggleFavorite(e, el){

    e.stopPropagation();

    el.classList.toggle("active");

    el.innerHTML =
        el.classList.contains("active")
        ? `<svg class="ico heart" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
        : `<svg class="ico heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
}
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

let current = 0;

function showSlide(index){

    slides.forEach(slide=>slide.classList.remove("active"));
    dots.forEach(dot=>dot.classList.remove("active"));

    slides[index].classList.add("active");
    dots[index].classList.add("active");
}

let slider;

function autoSlide(){

slider=setInterval(()=>{

current++;

if(current>=slides.length)
current=0;

showSlide(current);

},5000);

}

autoSlide();

dots.forEach((dot,index)=>{

dot.onclick=()=>{

clearInterval(slider);

current=index;

showSlide(current);

autoSlide();

}

});

function confirmLogout(e){
    e.preventDefault();

    document.body.classList.add("modal-open");
    document.getElementById("logoutModal").classList.add("show");
}

function closeLogout(){
    document.body.classList.remove("modal-open");
    document.getElementById("logoutModal").classList.remove("show");
}

function logoutNow() {
    window.location.href = "/casino/member/logout.php";
}
logoutModal.onclick=function(e){

if(e.target===this)
closeLogout();

}
function openSearch(e){

    e.preventDefault();

    const modal = document.getElementById("searchModal");

    modal.classList.add("show");
    modal.classList.remove("searching");

    popupSearch.value="";

    renderSearch(games);

    popupSearch.focus();

}

function closeSearch(){

    document
        .getElementById("searchModal")
        .classList
        .remove("show");

}
function backSearch(){

    popupSearch.value = "";

    const modal = document.getElementById("searchModal");

    // sembunyikan tombol back
    modal.classList.remove("searching");

    // kembali ke tampilan awal search hero
    renderSearch(games);

    document.querySelectorAll(".hero-pill")
    .forEach(x=>{
        x.classList.remove("active");
    });

    popupSearch.focus();

}

const popupSearch=document.getElementById("popupSearch");

popupSearch.addEventListener("input",()=>{

    const modal = document.getElementById("searchModal");

    const key = popupSearch.value.toLowerCase().trim();


    if(key.length > 0){

        modal.classList.add("searching");

    }else{

        modal.classList.remove("searching");

    }


    renderSearch(
        games.filter(g =>
            g.name.toLowerCase().includes(key)
        )
    );

});

function renderSearch(list){

    // kondisi awal search dibuka
    if(popupSearch.value === "" && list.length === games.length){

        searchResult.innerHTML = `
        <div class="search-hero">

            <div class="search-hero-icon"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="3"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="18" cy="13" r="1" fill="currentColor" stroke="none"/></svg></div>

            <h2>Temukan Kemenangan Terbesarmu</h2>

            <p>
                Jelajahi <strong>2.000+</strong> permainan dari provider terbaik dunia.
            </p>

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

        </div>
        `;

        return;
    }


    // jika tidak ada hasil
    if(list.length === 0){

        searchResult.innerHTML = `

        <div class="search-empty">

            <h3>Tidak ada game ditemukan</h3>

            <p>Coba gunakan kata kunci lain.</p>

        </div>

        `;

        return;
    }


    // hasil search / filter kategori
    searchResult.innerHTML = `

    <div class="search-grid">

    ${list.map(g=>`

        <div class="search-card" onclick="playGame(${g.id})">

            <img src="${g.img}">

            <div class="search-card-info">

                <h4>${g.name}</h4>

                <div class="search-card-provider">
                    ${g.provider}
                </div>

                <div class="search-card-rtp">
                    RTP ${Number(g.rtp).toFixed(2)}%
                </div>

            </div>

        </div>

    `).join("")}

    </div>

    `;
const cards = document.querySelectorAll(".search-card");

cards.forEach((card,index)=>{

    setTimeout(()=>{

        card.classList.add("show");

    },index*45);

});

}
document.addEventListener("click", function(e){

    const pill = e.target.closest(".hero-pill");
    if(!pill) return;

    const cat = pill.dataset.cat;

    popupSearch.value = "";

    if(cat === "all"){
        renderSearch(games);
        return;
    }

    const result = games.filter(g => g.cat === cat);

    renderSearch(result);

});
document.addEventListener("click",function(e){

    const pill = e.target.closest(".hero-pill");
    if(!pill) return;

    document.querySelectorAll(".hero-pill")
        .forEach(x=>x.classList.remove("active"));

    pill.classList.add("active");

});
let timer;

popupSearch.addEventListener("input",()=>{

clearTimeout(timer);

timer=setTimeout(()=>{

// search

},200);

});
renderGames("all");

function openWelcomePopup(){

    const modal =
        document.getElementById("welcomeModal");

    modal.classList.add("show");
}


function closeWelcomePopup(){

    const modal =
        document.getElementById("welcomeModal");

    modal.classList.remove("show");
}
const searchModal = document.getElementById("searchModal");

searchModal.addEventListener("click", function(e){

    if(e.target === searchModal){

        closeSearch();

    }

});
</script>

<?php if ($showWelcome): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        openWelcomePopup();
    }, 700);
});
</script>
<?php endif; ?>


<script>
function showToast(title, message, type="success"){

    const toast = document.getElementById("toast");
    const icon = document.getElementById("toastIcon");


    toast.querySelector(".toast-title").innerText = title;
    toast.querySelector(".toast-message").innerText = message;


    if(type === "error"){

        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        icon.classList.add("error");

    }else{

        icon.innerHTML = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
        icon.classList.remove("error");

    }


    toast.classList.remove("hide");
    toast.classList.add("show");


    setTimeout(()=>{

        toast.classList.remove("show");
        toast.classList.add("hide");

    },3000);

}





// ========== HISTORY / BET / BONUS MODALS ==========

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
const icoGift = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>`;
const icoEmpty = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`;

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


// ========== DEPOSIT MODAL ==========

function openDepositModal(){
    const modal = document.getElementById("depositModal");
    modal.style.display = "flex";

    // reset form state
    const amountInput = document.getElementById("depositAmount");
    amountInput.value = "";
    document.querySelectorAll(".deposit-preset").forEach(b => b.classList.remove("active"));

    setTimeout(() => amountInput.focus(), 150);
}

function closeDepositModal(){
    document.getElementById("depositModal").style.display = "none";
}

window.addEventListener("click", function(e){
    const modal = document.getElementById("depositModal");
    if(e.target === modal){
        closeDepositModal();
    }
});

function formatDepositAmount(val){
    const num = String(val).replace(/\D/g, "");
    if(!num) return "";
    return Number(num).toLocaleString("id-ID");
}

function parseDepositAmount(val){
    return Number(String(val).replace(/\D/g, "")) || 0;
}

const depositAmountInput = document.getElementById("depositAmount");

if(depositAmountInput){
    depositAmountInput.addEventListener("input", function(){
        const raw = this.value.replace(/\D/g, "");
        this.value = formatDepositAmount(raw);

        // sync preset active state
        const amount = Number(raw) || 0;
        document.querySelectorAll(".deposit-preset").forEach(btn => {
            btn.classList.toggle("active", Number(btn.dataset.amount) === amount);
        });
    });
}

document.querySelectorAll(".deposit-preset").forEach(btn => {
    btn.addEventListener("click", function(){
        const amount = this.dataset.amount;
        depositAmountInput.value = formatDepositAmount(amount);

        document.querySelectorAll(".deposit-preset").forEach(b => b.classList.remove("active"));
        this.classList.add("active");
        depositAmountInput.focus();
    });
});

document.querySelectorAll(".deposit-method").forEach(label => {
    label.addEventListener("click", function(){
        document.querySelectorAll(".deposit-method").forEach(m => m.classList.remove("active"));
        this.classList.add("active");
        const radio = this.querySelector('input[type="radio"]');
        if(radio) radio.checked = true;
    });
});

function submitDeposit(e){
    e.preventDefault();

    const amount = parseDepositAmount(depositAmountInput.value);
    const method = document.querySelector('input[name="method"]:checked')?.value;

    if(amount < 50000){
        showToast("Gagal", "Minimal deposit adalah Rp 50.000", "error");
        depositAmountInput.focus();
        return false;
    }

    if(!method){
        showToast("Gagal", "Pilih metode pembayaran terlebih dahulu.", "error");
        return false;
    }

    // kirim amount numerik (tanpa format)
    depositAmountInput.value = amount;

    // TODO: ganti dengan fetch ke backend deposit jika perlu
    // sementara submit form biasa
    document.getElementById("depositForm").submit();
    return true;
}


</script>
</body>
</html>