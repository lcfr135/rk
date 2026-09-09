<?php
session_start();

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../rtp/rtp_update.php";

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

if (!isset($_SESSION['id'])) {
    header("Location: /casino/auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];


/*
    Ambil data terbaru langsung dari database
*/
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
        total_withdraw,
        created_at,
        last_login,
        last_seen,
        password_updated_at
    FROM users 
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$online = false;

if(!empty($user['last_seen'])){

    $diff = time() - strtotime($user['last_seen']);

    if($diff <= 60){
        $online = true;
    }

}

$activity_query = $conn->prepare("
    SELECT *
    FROM user_activity
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 2
");

$activity_query->bind_param("i",$user_id);
$activity_query->execute();

$activities = $activity_query->get_result();

if (!$user) {

    session_destroy();

    header("Location: /casino/member/logout.php");
    exit;

}



/*
    Data profil realtime
*/

$username       = $user['username'];
$fullname       = $user['fullname'];
$email          = $user['email'];
$phone          = $user['phone'];

$balance        = $user['balance'];
$level          = $user['level'];
$points         = $user['points'];

$total_deposit  = $user['total_deposit'];
$total_withdraw = $user['total_withdraw'];


$avatar = strtoupper(
    mb_substr($username,0,2)
);

/*
    Foto profil (jika ada)
*/
$avatar_file = $user['avatar'] ?? null;
$avatar_url  = $avatar_file ? "../uploads/avatars/" . $avatar_file : null;


$join_date = date(
    "d F Y",
    strtotime($user['created_at'])
);


$last_time = !empty($user['last_seen'])
    ? strtotime($user['last_seen'])
    : strtotime($user['last_login']);
"Belum pernah login";

if (!empty($user['last_login'])) {

    $last_login_time = strtotime($user['last_login']);
    $diff = time() - $last_login_time;

    if ($diff < 60) {

        $last_active = "Baru saja";

    } elseif ($diff < 3600) {

        $last_active = floor($diff / 60) . " menit lalu";

    } elseif ($diff < 86400) {

        $last_active = floor($diff / 3600) . " jam lalu";

    } else {

        $last_active = floor($diff / 86400) . " hari lalu";

    }

} else {

    $last_active = "Belum pernah login";

}

if(!empty($user['password_updated_at'])){


    $password_update = strtotime(
        $user['password_updated_at']
    );


    $diff = time() - $password_update;


    if($diff < 60){

        $password_changed = "Baru saja";

    }
    elseif($diff < 3600){

        $password_changed = floor($diff / 60)." menit yang lalu";

    }
    elseif($diff < 86400){

        $password_changed = floor($diff / 3600)." jam yang lalu";

    }
    elseif($diff < 2592000){

        $password_changed = floor($diff / 86400)." hari yang lalu";

    }
    else{

        $password_changed = date(
            "d M Y",
            $password_update
        );

    }


}else{

    $password_changed = "Belum pernah diubah";

}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya | Royal Knight's</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
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
}

/* ========== HEADER (sama seperti casino.php) ========== */
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
    content:"";
    position:absolute;
    left:0;
    bottom:0;
    width:100%;
    height:1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,.45), transparent);
    opacity:0;
    transition:.35s;
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

.member-area{
    display:flex;
    align-items:center;
    justify-self:end;
    gap:12px;
    height:100%;
}
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
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
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
    width:38px;
    height:38px;
    border-radius:50%;
    background: linear-gradient(145deg, #FFD700, #e6a800);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    color:#111;
    font-size:14px;
    box-shadow: 0 0 0 2px rgba(255,215,0,.2);
    background-size:cover;
    background-position:center;
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
    transition: transform .25s;
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
.dropdown-menu a:last-child:hover { 
    background: rgba(255,59,92,.08); 
    color:var(--danger); 
}
/* Responsive header */
@media (max-width: 960px) {
    header { padding: 0 16px; }
    .nav-menu { display:none; }
    .balance-card { padding:6px 6px 6px 14px; }
    .balance-info .amount { font-size:14.5px; }
}
/* ========== PROFILE LAYOUT ========== */
.profile-wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 24px 80px;
}

/* Profile Hero Card */
.profile-hero {
    background: linear-gradient(145deg, #15151c 0%, #101014 100%);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 28px;
    padding: 36px 40px;
    display: flex;
    align-items: center;
    gap: 32px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 16px 50px rgba(0,0,0,.35);
}
.profile-hero::before {
    content:"";
    position:absolute;
    width:320px; height:320px;
    right:-80px; top:-100px;
    border-radius:50%;
    background: radial-gradient(circle, rgba(255,215,0,.1), transparent 70%);
    filter: blur(40px);
}
.profile-hero::after{
    content:"";
    position:absolute;

    width:310px;
    height:310px;

    right:-60px;
    bottom:-57px;

    background:url("../assets/icons/crown1.png") no-repeat center;
    background-size:contain;

    transform:rotate(12deg);

    opacity:.10;

    pointer-events:none;
    z-index:0;
}

.profile-avatar-lg {
    width: 110px; height: 110px;
    border-radius: 50%;
    background: linear-gradient(145deg, #FFD700, #e6a800);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; font-weight: 700; color: #111;
    box-shadow: 0 0 0 4px rgba(255,215,0,.2), 0 12px 30px rgba(255,215,0,.2);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    background-size:cover;
    background-position:center;
}
.profile-avatar-lg .edit-avatar {
    position: absolute;
    bottom: 4px; right: 4px;
    width: 32px; height: 32px;
    background: #1a1a22;
    border: 2px solid var(--gold);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all .25s;
    color: #FFD700;
}
.profile-avatar-lg .edit-avatar svg {
    width: 15px;
    height: 15px;
    display: block;
}
.profile-avatar-lg .edit-avatar:hover {
    background: var(--gold);
    color: #111;
}

.profile-info{
    flex:1;
    position:relative;
    z-index:1;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:flex-start;
}
.profile-info .username{
    margin-top:2px;
    margin-bottom:2px;
    font-size:16px;
    color:var(--gold);
    font-weight:600;
}
.member-card{
    display:inline-flex;      /* jangan flex biasa */
    align-items:center;
    justify-content:space-between;

    width:auto;
    min-width:150px;
    max-width:90px;
    height: 40px;
    padding:10px 12px;

    margin:8px 0;

    background:linear-gradient(145deg,#17171d,#101014);
    border:1px solid rgba(255,255,255,.06);
    border-radius:14px;

    overflow:hidden;
}
.member-icon{
    width:36px;
    height:36px;
    font-size:15px;
}

.member-card h4{
    font-size:11px;
    margin-bottom:2px;
}

.member-card h3{
    font-size:15px;
}

.copy-member{
    width:34px;
    height:34px;
}

.member-card:hover{

    border-color:rgba(255,215,0,.25);

    box-shadow:
        0 10px 35px rgba(0,0,0,.35),
        0 0 20px rgba(255,215,0,.08);

}

.member-card-left{

    display:flex;
    flex-direction:column;
    gap:-2px;

}

.member-label{

    font-size:10px;

    letter-spacing:1px;

    color:#8d8d95;

    text-transform:uppercase;

}

.member-number{

    color:#FFD700;

    font-weight:700;

    font-size:12px;

    letter-spacing:1px;

}

.member-divider{

    width:3px;
    align-self:stretch;

    background:
    linear-gradient(
        transparent,
        rgba(255,255,255,.12),
        transparent
    );

    margin:2 10px;

}

.copy-btn{

    width:34px;
    height:34px;

    display:flex;
    align-items:center;
    justify-content:center;

    border:none;

    background:#111116;

    border-radius:10px;

    color:#8b8b92;

    cursor:pointer;

    transition:.25s;

}

.copy-btn:hover{

    color:#FFD700;

    background:rgba(255,215,0,.08);

    transform:translateY(-2px);

}

.copy-btn svg{

    width:15px;
    height:15px;

}
.copy-btn:hover{

    background:rgba(255,215,0,.12);

    color:#FFD700;
}

.copy-btn svg{

    width:18px;
    height:18px;
}
.profile-info h1{
    font-size:32px;
    font-weight:700;
    margin:0;
    line-height:1.05;
}
.profile-info-row{
    display:flex;
    align-items:center;
    gap:22px;
    flex-wrap:wrap;
    font-size:12px;
    color:#9b9b9b;
}
.profile-info-row span{
    display:flex;
    align-items:center;
    gap:8px;
}
.profile-info-row svg{

    width:17px;
    height:17px;

    color:#FFD700;

    flex-shrink:0;
}
.profile-meta span {
    display: flex; align-items: center; gap: 6px;
}
.profile-badges{
    display:flex;
    align-items:center;
    gap:8px;

    margin-top:6px;      /* sebelumnya 10px */
    margin-bottom:8px;

    position:relative;
    top:-6px;            /* naik ke atas */
}
.profile-meta .badge-level {
    background: linear-gradient(135deg, rgba(255,215,0,.15), rgba(255,215,0,.05));
    border: 1px solid var(--gold-border);
    color: var(--gold);
    padding: 4px 14px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
}
.badge-level{
    display:inline-flex;
    align-items:center;
    gap:6px;

    padding:4px 10px;

    border-radius:999px;

    background:rgba(255,215,0,.08);
    border:1px solid rgba(255,215,0,.18);

    color:#FFD700;

    font-size:11px;
    font-weight:600;

    line-height:1;

    backdrop-filter:blur(10px);
box-shadow:0 0 15px rgba(255,215,0,.08);
}
.online-status{

    display:inline-flex;
    align-items:center;
    gap:6px;

    padding:4px 10px;

    border-radius:999px;

    background:rgba(45,255,107,.08);

    border:1px solid rgba(45,255,107,.15);

    color:#59ff92;

    font-size:11px;
    font-weight:600;

    line-height:1;

    backdrop-filter:blur(10px);

}

.online-status::before{
    content:"";
    width:6px;
    height:6px;
    border-radius:50%;
    background:#2dff6b;
    box-shadow:0 0 10px #2dff6b;
    animation:pulseOnline 1.5s infinite;
}

@keyframes pulseOnline{
    0%{
        transform:scale(1);
        opacity:1;
    }
    50%{
        transform:scale(1.4);
        opacity:.5;
    }
    100%{
        transform:scale(1);
        opacity:1;
    }
}

.profile-actions{
    display:flex;
    flex-direction:column;
    gap:12px;

    position:relative;
    z-index:2;

    align-items:center;
    justify-content:center;

    margin-right:20px;       /* geser ke kiri */
    transform:translateX(-15px);
}
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

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background: linear-gradient(145deg, #15151c, #101014);
    border: 1px solid rgba(255,255,255,.05);
    border-radius: 20px;
    padding: 22px 20px;
    transition: all .3s;
}
.stat-card:hover {
    border-color: var(--gold-border);
    transform: translateY(-4px);
}
.stat-card .label {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 8px;
    letter-spacing: .3px;
}
.stat-card .value {
    font-size: 22px;
    font-weight: 700;
    color: var(--gold);
}
.stat-card .sub {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

/* ========== CONTENT GRID ========== */
.content-grid {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;

    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 16px;

    align-items: stretch; /* kolom kiri & kanan tinggi sama */
}

.content-grid > .left-column,
.content-grid > .right-column {
    width: 100%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
    height: 100%;
}

.profile-grid-card {
    width: 100%;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

/* Personal & Security di kiri → bagi tinggi merata */
.left-column > .profile-grid-card {
    flex: 1 1 0;
}

/* Quick & VIP di kanan → ukuran natural (tidak dipaksa kecil) */
.right-column > .profile-grid-card {
    flex: 0 0 auto;
}

.profile-grid-card > .card {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 18px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}
.left-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.right-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}


/* =========================
   TITLE
========================= */

.content-grid .card-title {
    margin-bottom: 16px;
    font-size: 15px;
}

.content-grid .card-title svg {
    width: 18px;
    height: 18px;
}


/* =========================
   PERSONAL INFO
========================= */

.personal-card .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.personal-card .form-group label {
    font-size: 12px;
    margin-bottom: 6px;
}

.personal-card .form-group input {
    height: 40px;
    padding: 0 12px;
    font-size: 13px;
}

.personal-card .form-actions {
    margin-top: 14px;
    gap: 8px;
}


/* =========================
   QUICK ACTION
========================= */

.quick-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.quick-action-buttons a {
    min-height: 40px;
    padding: 10px 12px;
    font-size: 13px;
}


/* =========================
   SECURITY
========================= */

.security-card .security-item {
    padding: 10px 0;
    gap: 8px;
}

.security-card .security-icon {
    width: 34px;
    height: 34px;
    font-size: 15px;
}

.security-card .security-text h4 {
    font-size: 13px;
    margin-bottom: 3px;
}

.security-card .security-text p {
    font-size: 11px;
}

.security-card .btn-sm {
    padding: 6px 10px;
    font-size: 11px;
}


/* =========================
   TABLET
========================= */

@media (max-width: 900px) {

    .content-grid {
        max-width: 100%;
        gap: 14px;
    }

}


/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {

    .content-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .content-grid > .profile-grid-card > .card {
        padding: 16px;
    }

    .personal-card .form-row {
        grid-template-columns: 1fr;
    }

}

/* Card dasar */
.card {
    background: linear-gradient(145deg, #15151c, #101014);
    border: 1px solid rgba(255,255,255,.05);
    border-radius: 22px;
    padding: 24px 26px;
    margin-bottom: 0; /* biar gap dari parent yang atur */
    height: 100%; /* biar stretch */
    display: flex;
    flex-direction: column;
}
.card-title {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-title span {
    background: linear-gradient(135deg, #FFD700, #e6b800);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Form */
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-size: 12.5px;
    color: var(--muted);
    margin-bottom: 7px;
    font-weight: 500;
}
.form-group input,
.form-group select {
    width: 100%;
    height: 48px;
    padding: 0 16px;
    background: #0e0e12;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
    transition: all .25s;
}
.form-group input:focus,
.form-group select:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 3px rgba(255,215,0,.08);
}
.form-group input:disabled {
    opacity: .6;
    cursor: not-allowed;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}

/* Security List */
.security-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.security-item:last-child { border-bottom: none; }
.security-info {
    display: flex;
    align-items: center;
    gap: 14px;
}
.security-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(255,215,0,.08);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: #FFD700;
}
.security-icon svg {
    width: 18px;
    height: 18px;
    display: block;
}
.security-text h4 { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
.security-text p { font-size: 12px; color: var(--muted); }

.btn-sm {
    padding: 8px 16px;
    border-radius: 12px;
    border: 1px solid var(--gold-border);
    background: transparent;
    color: var(--gold);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all .25s;
}
.btn-sm:hover {
    background: rgba(255,215,0,.1);
}

/* Activity */
.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,.04);
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--gold);
    margin-top: 5px;
    flex-shrink: 0;
    box-shadow: 0 0 8px rgba(255,215,0,.5);
}
.activity-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: #FFD700;
    display: inline-block;
    vertical-align: -4px;
    margin-right: 5px;
}
.activity-content h4 { font-size: 13.5px; font-weight: 500; margin-bottom: 3px; }
.activity-content p { font-size: 12px; color: var(--muted); }

/* Footer */
footer {
    text-align: center;
    padding: 32px 20px;
    background: #0c0c0e;
    color: #555;
    font-size: 13px;
    border-top: 1px solid rgba(255,255,255,.04);
}

/* Responsive */
@media (max-width: 900px) {
    .hero-divider{
    width:100%;
    height:1px;
    margin:20px 0;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,215,0,.25),
            transparent
        );
}
    header { padding: 0 16px; }
    .nav-menu { display: none; }
    .profile-hero {
        flex-direction: column;
        text-align: center;
        padding: 28px 24px;
    }
    .profile-meta { justify-content: center; }
    .profile-actions { width: 100%; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .content-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
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
.offline-status{

    display:inline-flex;
    align-items:center;
    gap:6px;

    padding:4px 10px;

    border-radius:999px;

    background:rgba(255,59,92,.08);

    border:1px solid rgba(255,59,92,.15);

    color:#ff7d95;

    font-size:11px;
    font-weight:600;

    line-height:1;

}

.offline-status::before{

    content:"";

    width:6px;
    height:6px;

    border-radius:50%;

    background:#ff3b5c;

}
.hero-divider{
    width:1px;
    align-self:stretch;

    margin:0 65px;

    background:
        linear-gradient(
            transparent,
            rgba(255,255,255,.12),
            rgba(255,215,0,.18),
            rgba(255,255,255,.12),
            transparent
        );

    flex-shrink:0;
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
.search-hero-icon svg{
    width:26px;
    height:26px;
    display:block;
}
.hero-pill svg{
    width:14px;
    height:14px;
    vertical-align:-2px;
    margin-right:4px;
}
.badge-level svg{
    width:12px;
    height:12px;
    margin-right:4px;
    vertical-align:-1px;
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
.search-modal.searching .search-back{
    opacity:1;
    pointer-events:auto;
}
.confirm-actions{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:14px;
    margin-top:25px;
}

.confirm-actions .btn-primary,
.confirm-actions .btn-outline{
    min-width:140px;
    height:46px;
}
.card-title svg{
    width:20px;
    height:20px;
    color:#FFD700;
    flex-shrink:0;
}
.card-title{
    display:flex;
    align-items:center;
    gap:10px;
}
//* CROP BULAT */
.avatar-crop-round .cropper-view-box {
    border-radius: 50%;
    outline: none;
    box-shadow: 0 0 0 9999px rgba(10,10,12,.85);
}
.avatar-crop-round .cropper-face {
    border-radius: 50%;
}
.avatar-crop-round .cropper-dashed,
.avatar-crop-round .cropper-line,
.avatar-crop-round .cropper-point {
    display: none;
}
.avatar-crop-round .cropper-crop-box,
.avatar-crop-round .cropper-container {
    border-radius: 50%;
}

/* TOMBOL CROP MINIMALIS */
.crop-tools {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 18px;
}
.crop-tool-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1px solid rgba(255,215,0,.22);
    background: #111116;
    color: #FFD700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .25s;
}
.crop-tool-btn:hover {
    background: rgba(255,215,0,.12);
    transform: translateY(-2px);
}
.crop-tool-btn svg {
    width: 18px;
    height: 18px;
}
/* Security + VIP side by side */
.security-vip-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    align-items: stretch; /* tinggi sama */
}

.security-vip-row .card {
    height: 100%;
    margin-bottom: 0;
}

/* =========================================================
   VIP STATUS CARD - COMPACT / REFINED
========================================================= */

.vip-status-card {
    position: relative;
    overflow: hidden;

    background: linear-gradient(
        145deg,
        #1a1608 0%,
        #12100a 100%
    );

    border: 1px solid rgba(255, 215, 0, 0.25);

    /* lebih pendek */
    padding: 14px 18px;
}


/* Glow decoration */
.vip-status-card::before {
    content: "";

    position: absolute;

    top: -40px;
    right: -40px;

    width: 110px;
    height: 110px;

    background: radial-gradient(
        circle,
        rgba(255, 215, 0, 0.18),
        transparent 70%
    );

    border-radius: 50%;

    pointer-events: none;
}


/* =========================================================
   VIP HEADER
========================================================= */

.vip-header {
    display: flex;
    align-items: center;

    gap: 12px;

    margin-top: 0;
    margin-bottom: 8px;
}


/* Crown / VIP icon */
.vip-icon {
    width: 40px;
    height: 40px;

    border-radius: 12px;

    background: linear-gradient(
        135deg,
        #FFD700,
        #e6a800
    );

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 18px;
    color: #111;

    box-shadow:
        0 3px 12px rgba(255, 215, 0, 0.35);
}
.vip-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}

    flex-shrink: 0;
}


/* =========================================================
   VIP LABEL
========================================================= */

.vip-info .label {
    color: #a89b6a;

    font-size: 10px;
    font-weight: 600;

    letter-spacing: 0.7px;

    text-transform: uppercase;

    margin-bottom: 2px;
}


/* VIP Level */
.vip-level {
    font-size: 17px;
    font-weight: 700;

    color: #FFD700;

    line-height: 1.15;
}


/* =========================================================
   VIP POINTS
========================================================= */

.vip-points {
    font-size: 12px;

    color: #c5b87a;

    margin-top: 4px;
    margin-bottom: 8px;
}

.vip-points span {
    font-size: 17px;
    font-weight: 700;

    color: #FFD700;
}


/* =========================================================
   VIP PROGRESS
========================================================= */

.vip-progress {
    margin-top: 0;
}

.vip-progress-bar {
    width: 100%;
    height: 5px;

    background: rgba(255, 215, 0, 0.12);

    border-radius: 99px;

    overflow: hidden;

    margin-bottom: 5px;
}

.vip-progress-fill {
    height: 100%;

    background: linear-gradient(
        90deg,
        #FFD700,
        #f0c000
    );

    border-radius: 99px;

    box-shadow:
        0 0 10px rgba(255, 215, 0, 0.45);
}

.vip-progress-text {
    display: flex;

    justify-content: space-between;

    font-size: 10px;

    color: #8a7f55;
}
.vip-card {
    margin-top: 0;
    flex: 0 0 auto; /* ukuran natural */
}

.quick-card {
    flex: 0 0 auto; /* ukuran natural */
}

/* =========================================================
   VIP CARD TITLE
========================================================= */

.vip-status-card .card-title {
    margin-bottom: 8px;
    font-size: 14px;
}
.activity-horizontal {
    margin-top: 0;
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    height: auto;
}

.activity-horizontal-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 0 0 auto;
    overflow-y: visible;
}

.activity-horizontal-list::-webkit-scrollbar {
    width: 5px;
}

.activity-horizontal-list::-webkit-scrollbar-thumb {
    background: rgba(255,215,0,.35);
    border-radius: 10px;
}

.activity-item-h {
    width: 100%;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.05);
    border-radius: 12px;
    transition: all .25s;
}

.activity-item-h:hover {
    border-color: rgba(255,215,0,.25);
    background: rgba(255,215,0,.04);
    transform: translateY(-2px);
}

.activity-item-h .activity-dot {
    margin-top: 4px;
}

.activity-item-h .activity-content h4 {
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.activity-item-h .activity-content p {
    font-size: 12px;
    color: var(--muted);
}

/* Responsive */
@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .security-vip-row {
        grid-template-columns: 1fr;
    }
}
/* =========================================
   QUICK ACTION - GRID 2 x 2
========================================= */

/* =========================
   QUICK ACTIONS (lebih pendek)
========================= */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;                    /* lebih rapat */
}

.quick-actions a {
    text-align: center;
    padding: 10px 16px !important;   /* lebih pendek */
    font-size: 13px !important;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.right-column .card {
    height: 100%;
    padding: 18px 20px;
    flex: 1 1 0;
}
.right-column .quick-card .card {
    padding: 14px 16px;
    height: auto;
    flex: 0 0 auto;
}
.right-column .vip-card .card {
    padding: 14px 18px;
    height: auto;
    flex: 0 0 auto;
}
.right-column .card.activity-horizontal {
    height: auto;
    flex: 0 0 auto;
    padding: 14px 16px;
}
.quick-action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;

    margin-top: 10px;

    width: 100%;
}

.quick-action-item {
    width: 100%;
    min-width: 0;
    height: 46px;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;

    padding: 6px 10px;

    box-sizing: border-box;

    text-decoration: none;
    color: #bdbdbd;

    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 9px;

    transition:
        background .2s ease,
        border-color .2s ease,
        color .2s ease,
        transform .2s ease;
}

.quick-action-item:hover {
    color: #FFD700;
    background: rgba(255,215,0,.05);
    border-color: rgba(255,215,0,.3);
    transform: translateY(-1px);
}

.quick-action-icon {
    width: 20px;
    height: 20px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;
}
.quick-action-icon svg {
    width: 16px;
    height: 16px;
}

.quick-action-item span {
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
}
.quick-card .card {
    padding: 14px 16px;
}

.quick-card .card-title {
    margin-bottom: 0;
    font-size: 14px;
}
.quick-card .card-title svg {
    width: 16px;
    height: 16px;
}


@media (max-width: 600px) {

    .quick-action-grid {
        gap: 6px;
    }

    .quick-action-item {
        height: 44px;
        padding: 6px 8px;
    }

    .quick-action-item span {
        font-size: 10px;
    }

    .quick-action-icon svg {
        width: 15px;
        height: 15px;
    }

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

/* ========== LOGOUT MODAL ========== */
.logout-modal{
    position:fixed;
    inset:0;
    display:flex;
    justify-content:center;
    align-items:center;
    background:rgba(0,0,0,.55);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    opacity:0;
    visibility:hidden;
    transition:.3s ease;
    z-index:999999;
}
.logout-modal.show{
    opacity:1;
    visibility:visible;
}
.logout-box{
    width:360px;
    max-width:90%;
    background:#17171c;
    border:1px solid rgba(255,215,0,.25);
    border-radius:20px;
    padding:28px;
    text-align:center;
    box-shadow:0 25px 70px rgba(0,0,0,.6);
    animation:modalShow .25s ease;
}
.logout-box h3{
    color:#FFD700;
    margin-bottom:12px;
    font-size:18px;
}
.logout-box p{
    color:#bbb;
    margin-bottom:24px;
    font-size:14px;
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
    font-size:13.5px;
    transition:.2s;
}
.logout-cancel{
    background:#333;
    color:#fff;
}
.logout-cancel:hover{
    background:#444;
}
.logout-confirm{
    background:#ff3b5c;
    color:#fff;
}
.logout-confirm:hover{
    background:#ff2147;
}
/* =========================================================
   WITHDRAW MODAL
========================================================= */

.withdraw-modal .modal-content{
    width:320px;
    max-width:88%;
    padding:18px 16px 16px;
    border-radius:16px;
}

.withdraw-modal .close{
    position:absolute;
    top:16px;
    right:20px;
    margin-left:0;
    font-size:26px;
    line-height:1;
    color:#888;
    cursor:pointer;
    transition:.2s;
    z-index:2;
}

.withdraw-modal .close:hover{
    color:#FFD700;
}

.withdraw-title{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:4px;
}

.withdraw-title h3{
    color:#FFD700;
    font-size:16px;
    font-weight:700;
    margin:0;
}

.withdraw-title svg{
    width:19px;
    height:19px;
    color:#FFD700;
}

.withdraw-sub{
    color:#8a8a96;
    font-size:10.5px;
    margin-bottom:12px;

}

/* Saldo tersedia */

.withdraw-balance{
    display:flex;
    align-items:center;
    justify-content:space-between;
     padding:9px 11px;
    margin-bottom:12px;

    background:rgba(255,215,0,.06);
    border:1px solid rgba(255,215,0,.16);
    border-radius:11px;
}

.withdraw-balance-label{
    color:#888;
    font-size:9.5px;
}

.withdraw-balance-value{
    color:#FFD700;
    font-size:12px;
    font-weight:700;
}

/* Amount */

.withdraw-amount-label{
    font-size:10px;
    color:var(--muted);
    font-weight:500;
    margin-bottom:5px;
}

.withdraw-amount-input{
    position:relative;
    margin-bottom:8px;

}

.withdraw-amount-input .prefix{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);

    color:#FFD700;
    font-weight:700;
    font-size:12px;

    pointer-events:none;
}

.withdraw-amount-input input{
    width:100%;
    height:42px;
    padding:0 16px 0 48px;
    padding-left:40px;
    background:#0e0e12;
    border:1px solid rgba(255,255,255,.08);
    border-radius:11px;

    color:var(--text);
    font-size:15px;
    font-weight:700;

    outline:none;
    transition:.25s;
}

.withdraw-amount-input input:focus{
    border-color:var(--gold-border);
    box-shadow:0 0 0 3px rgba(255,215,0,.08);
}

/* Preset */

.withdraw-presets{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
    margin-bottom:15px;
}

.withdraw-preset{
    height:40px;

    border:1px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.03);
    border-radius:11px;

    color:#c8c8d0;
    font-size:12px;
    font-weight:600;

    cursor:pointer;
    transition:.2s;
}

.withdraw-preset:hover{
    border-color:rgba(255,215,0,.35);
    color:#FFD700;
    background:rgba(255,215,0,.06);
}

.withdraw-preset.active{
    border-color:#FFD700;
    background:rgba(255,215,0,.12);
    color:#FFD700;
    box-shadow:0 0 14px rgba(255,215,0,.15);
}

/* Metode */

.withdraw-method-label{
    font-size:10px;
    color:var(--muted);
    font-weight:500;
    margin-bottom:6px;
}

.withdraw-methods{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:6px;
    margin-bottom:12px;
}

.withdraw-method{
    display:flex;
    align-items:center;
    gap:7px;

    padding:8px 9px;

    border:1px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.025);
    border-radius:10px;

    cursor:pointer;
    transition:.2s;
}

.withdraw-method:hover{
    border-color:rgba(255,215,0,.3);
    background:rgba(255,215,0,.05);
}

.withdraw-method.active{
    border-color:#FFD700;
    background:rgba(255,215,0,.1);
    box-shadow:0 0 16px rgba(255,215,0,.12);
}

.withdraw-method input{
    display:none;
}

.withdraw-method-icon{
    width:27px;
    height:27px;

    border-radius:7px;
    background:rgba(255,215,0,.1);

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:13px;
    flex-shrink:0;
    color:#FFD700;
}
.withdraw-method-icon svg{
    width:14px;
    height:14px;
    display:block;
}

.withdraw-method-text strong{
    display:block;
    color:#eee;
    font-size:10px;
    font-weight:600;
    margin-bottom:2px;
}

.withdraw-method-text span{
    font-size:8px;
    color:#888;
}

.withdraw-method.active .withdraw-method-text strong{
    color:#FFD700;
}

/* Nomor rekening */

.withdraw-account{
    margin-bottom:11px;
}

.withdraw-account label{
    display:block;
    font-size:10px;
    color:var(--muted);
    margin-bottom:5px;
}

.withdraw-account input{
    width:100%;
    height:38px;
    padding:0 11px;

    background:#0e0e12;
    border:1px solid rgba(255,255,255,.08);
    border-radius:10px;

    color:white;
    font-size:11px;

    outline:none;
}

.withdraw-account input:focus{
    border-color:var(--gold-border);
    box-shadow:0 0 0 3px rgba(255,215,0,.08);
}

/* Buttons */

.withdraw-actions{
    display:flex;
    gap:6px;
    margin-top:4px;
}

.withdraw-actions .btn-primary,
.withdraw-actions .btn-outline{
    flex:1;
    height:38px;

    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:9px;
    font-size:11px;
}

/* Note */

.withdraw-note{
    text-align:center;
    font-size:8.5px;
    color:#666;
    margin-top:8px;
}

@media(max-width:480px){

    .withdraw-modal .modal-content{
        padding:24px 18px 20px;
    }

    .withdraw-presets{
        gap:5px;
    }

    .withdraw-preset{
        height:32px;
        border-radius:8px;
        font-size:10px;
    }

    .withdraw-methods{
        grid-template-columns:1fr;
    }

}

/* ========== HISTORY MODAL ========== */
.history-modal .modal-content {
    width: 320px;
    max-width: 88%;
    padding: 18px 16px 16px;
    position: relative;
    border-radius: 16px;
}

.history-modal .close {
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

.history-modal .close:hover { color: #FFD700; }

.history-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.history-title h3 {
    color: #FFD700;
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.history-title svg {
    width: 19px;
    height: 19px;
    color: #FFD700;
}

.history-sub {
    color: #8a8a96;
    font-size: 11px;
    margin-bottom: 14px;
}

.history-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 14px;
    padding: 3px;
    background: rgba(0,0,0,.28);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.04);
}

.history-tab {
    flex: 1;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #8a8a96;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.history-tab:hover { color: #e8e0c0; }

.history-tab.active {
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    box-shadow: 0 2px 10px rgba(255,215,0,.3);
}

.history-list {
    max-height: 280px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
    padding-right: 2px;
}

.history-list::-webkit-scrollbar { width: 4px; }
.history-list::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 10px;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 12px;
    transition: .2s;
}

.history-item:hover {
    border-color: rgba(255,215,0,.2);
    background: rgba(255,215,0,.04);
}

.history-item-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(255,215,0,.1);
    color: #FFD700;
}

.history-item-icon svg {
    width: 16px;
    height: 16px;
}

.history-item-icon.out {
    background: rgba(255,59,92,.1);
    color: #ff3b5c;
}

.history-item-icon.in {
    background: rgba(45,255,107,.1);
    color: #2dff6b;
}

.history-item-body {
    flex: 1;
    min-width: 0;
}

.history-item-body strong {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #eee;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.history-item-body span {
    font-size: 10px;
    color: #777;
}

.history-item-meta {
    text-align: right;
    flex-shrink: 0;
}

.history-item-meta .amount {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 2px;
}

.history-item-meta .amount.plus { color: #2dff6b; }
.history-item-meta .amount.minus { color: #ff3b5c; }

.history-item-meta .status {
    font-size: 9px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.history-item-meta .status.success {
    background: rgba(45,255,107,.12);
    color: #2dff6b;
}

.history-item-meta .status.pending {
    background: rgba(255,215,0,.12);
    color: #FFD700;
}

.history-item-meta .status.failed {
    background: rgba(255,59,92,.12);
    color: #ff3b5c;
}

.history-empty {
    text-align: center;
    padding: 36px 12px;
    color: #666;
    font-size: 12px;
}

.history-actions {
    display: flex;
    gap: 6px;
}

.history-actions .btn-outline,
.history-actions .btn-primary {
    flex: 1;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    border-radius: 9px;
}

/* ========== BONUS MODAL ========== */
.bonus-modal .modal-content {
    width: 320px;
    max-width: 88%;
    padding: 18px 16px 16px;
    position: relative;
    border-radius: 16px;
}

.bonus-modal .close {
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

.bonus-modal .close:hover { color: #FFD700; }

.bonus-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.bonus-title h3 {
    color: #FFD700;
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.bonus-title svg {
    width: 19px;
    height: 19px;
    color: #FFD700;
}

.bonus-sub {
    color: #8a8a96;
    font-size: 11px;
    margin-bottom: 14px;
}

.bonus-list {
    max-height: 300px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
    padding-right: 2px;
}

.bonus-list::-webkit-scrollbar { width: 4px; }
.bonus-list::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 10px;
}

.bonus-card {
    padding: 12px;
    background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(255,215,0,.02));
    border: 1px solid rgba(255,215,0,.14);
    border-radius: 14px;
    transition: .2s;
}

.bonus-card:hover {
    border-color: rgba(255,215,0,.3);
    box-shadow: 0 6px 18px rgba(0,0,0,.25);
}

.bonus-card-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
}

.bonus-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(145deg, #FFE566, #FFD700 40%, #E0A800);
    color: #111;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255,215,0,.25);
}

.bonus-card-icon svg {
    width: 18px;
    height: 18px;
}

.bonus-card-info {
    flex: 1;
    min-width: 0;
}

.bonus-card-info strong {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2px;
}

.bonus-card-info p {
    font-size: 10.5px;
    color: #8a8a96;
    line-height: 1.4;
}

.bonus-card-badge {
    font-size: 9px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .4px;
    flex-shrink: 0;
}

.bonus-card-badge.available {
    background: rgba(45,255,107,.12);
    color: #2dff6b;
}

.bonus-card-badge.claimed {
    background: rgba(255,255,255,.08);
    color: #888;
}

.bonus-card-badge.locked {
    background: rgba(255,215,0,.1);
    color: #FFD700;
}

.bonus-card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.bonus-card-foot .value {
    font-size: 14px;
    font-weight: 700;
    color: #FFD700;
}

.bonus-card-foot .btn-claim-sm {
    height: 30px;
    padding: 0 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #FFD700, #f0c000);
    color: #111;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.bonus-card-foot .btn-claim-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,215,0,.3);
}

.bonus-card-foot .btn-claim-sm:disabled {
    opacity: .45;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.bonus-actions {
    display: flex;
    gap: 6px;
}

.bonus-actions .btn-outline,
.bonus-actions .btn-primary {
    flex: 1;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    border-radius: 9px;
}

@media (max-width: 480px) {
    .history-modal .modal-content,
    .bonus-modal .modal-content {
        width: 320px;
        max-width: 88%;
        padding: 18px 16px 16px;
    }
}

</style>
</head>
<body>

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

        <!-- GAMES -->
        <a href="game-list.php">Games</a>

        <!-- PROMOSI -->
        <a href="game-list.php?cat=slots">Promosi</a>

        <!-- VIP -->
        <a href="game-list.php?cat=live">VIP</a>

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
            <button class="btn-deposit" onclick="openDepositModal()">+ Deposit</button>
        </div>

        <div class="user-dropdown">
            <div class="user-btn" onclick="toggleUserMenu()">
                <div class="avatar" <?= $avatar_url ? 'style="background-image:url(\''.htmlspecialchars($avatar_url).'\')"' : '' ?>>
                    <?= $avatar_url ? '' : $avatar ?>
                </div>
                <div class="user-meta">
                    <div class="name"><?= htmlspecialchars($username) ?></div>
                    <div class="level"><?= htmlspecialchars($level) ?></div>
                </div>
            </div>
            <div class="dropdown-menu" id="userMenu">
                <a href="profile.php">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Profil Saya
                </a>
                <a href="#" onclick="openHistoryModal(); return false;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><path d="M6 3h12v18H6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Riwayat Transaksi
                </a>
                <a href="#" onclick="openHistoryModal('bet'); return false;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Riwayat Taruhan
                </a>
                <a href="#" onclick="openBonusModal(); return false;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><rect x="3" y="8" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v13M3 12h18" stroke="currentColor" stroke-width="1.8"/><path d="M12 8H8.5a2.5 2.5 0 1 1 0-5C10.5 3 12 8 12 8Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 8h3.5a2.5 2.5 0 1 0 0-5C13.5 3 12 8 12 8Z" stroke="currentColor" stroke-width="1.8"/></svg>
                    Bonus & Promo
                </a>
                <a href="settings.php">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Pengaturan
                </a>
                <a href="#" onclick="confirmLogout(event)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><path d="M10 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M15 16l5-5-5-5M9 11h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<!-- PROFILE CONTENT -->
<div class="profile-wrapper">

    <!-- Hero -->
    <div class="profile-hero">
        <div class="profile-avatar-lg" <?= $avatar_url ? 'style="background-image:url(\''.htmlspecialchars($avatar_url).'\')"' : '' ?>>
            <?= $avatar_url ? '' : $avatar ?>

            <form id="avatarForm" action="/casino/auth/upload-avatar.php" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp">
            </form>

            <label for="avatarInput" class="edit-avatar" title="Ubah Avatar">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <circle cx="12" cy="13.5" r="3.5" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </label>
        </div>

          <div class="profile-info">

    <h1><?= htmlspecialchars($fullname) ?></h1>

    <div class="username">
        @<?= htmlspecialchars($username) ?>
    </div>

    <div class="member-card">

    <div class="member-card-left">

        <span class="member-label">
            Member ID
        </span>

        <div class="member-number" id="memberId">
            RK<?= str_pad($user['id'], 6, "0", STR_PAD_LEFT) ?>
        </div>

    </div>

    <div class="member-divider"></div>

    <button
        class="copy-btn"
        onclick="copyMemberID()"
        title="Salin Member ID">

        <svg viewBox="0 0 24 24" fill="none">
            <path
                d="M9 9H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-3"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"/>

            <rect
                x="9"
                y="4"
                width="11"
                height="11"
                rx="2"
                stroke="currentColor"
                stroke-width="2"/>
        </svg>

    </button>

</div>

    <div class="profile-badges">

        <span class="badge-level">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 8l4 3 5-7 5 7 4-3v10H3V8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($level) ?>
        </span>

        <?php if($online): ?>

        <span class="online-status">
            Aktif sekarang
        </span>

        <?php else: ?>

        <span class="offline-status">
            <?= htmlspecialchars($last_active) ?>
        </span>

        <?php endif; ?>

    </div>

    <div class="profile-info-row">

    <span>

        <svg viewBox="0 0 24 24" fill="none">
            <path d="M7 2v3M17 2v3M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"/>
        </svg>

        Bergabung <?= htmlspecialchars($join_date) ?>

    </span>

</div>

</div>  
        <div class="hero-divider"></div>

        <div class="profile-actions">
            <a href="#" class="btn-primary" onclick="openDepositModal(); return false;">+ Deposit</a>
            <a href="#" class="btn-outline" onclick="openWithdrawModal(); return false;">
                 Withdraw
            </a>
        </div>
    </div>

    <div id="toast" class="toast">
        <div class="toast-icon" id="toastIcon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>

        <div class="toast-content">
            <div class="toast-title">Berhasil</div>
            <div class="toast-message"></div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="label">Saldo Saat Ini</div>
            <div class="value">Rp <?= number_format($balance, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="label">VIP Points</div>
            <div class="value"><?= number_format($points) ?></div>
            <div class="sub">Level: <?= htmlspecialchars($level) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Deposit</div>
            <div class="value">Rp <?= number_format($total_deposit, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Withdraw</div>
            <div class="value">Rp <?= number_format($total_withdraw, 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- Content Grid -->
<!-- =========================================================
     CONTENT GRID - 2 x 2
========================================================= -->

<div class="content-grid">

    <div class="left-column">

    <!-- =====================================================
         1. INFORMASI PRIBADI
    ====================================================== -->
    <div class="profile-grid-card personal-card">

        <div class="card">

            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none">
                    <path
                        d="M20 21a8 8 0 0 0-16 0"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                        stroke="currentColor"
                        stroke-width="2"
                    />
                </svg>

                <span>Informasi Pribadi</span>
            </div>

            <form
                id="profileForm"
                action="/casino/auth/update-profile.php"
                method="POST"
            >

                <div class="form-row">

                    <div class="form-group">
                        <label>Username</label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($username) ?>"
                            disabled
                        >
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>

                        <input
                            type="text"
                            name="fullname"
                            required
                            maxlength="100"
                            value="<?= htmlspecialchars($fullname) ?>"
                        >
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>Email</label>

                        <input
                            type="email"
                            value="<?= htmlspecialchars($email) ?>"
                            disabled
                        >
                    </div>

                    <div class="form-group">
                        <label>No. Telepon</label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($phone) ?>"
                            disabled
                        >
                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Simpan Perubahan
                    </button>

                    <button
                        type="reset"
                        class="btn-outline"
                    >
                        Batal
                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         3. KEAMANAN AKUN
    ====================================================== -->
    <div class="profile-grid-card security-card">

        <div class="card">

            <div class="card-title">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <rect
                        x="5"
                        y="11"
                        width="14"
                        height="10"
                        rx="2"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <path
                        d="M8 11V8a4 4 0 1 1 8 0v3"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                </svg>

                <span>Keamanan Akun</span>

            </div>


            <!-- Password -->
            <div class="security-item">

                <div class="security-info">

                    <div class="security-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 8a4 4 0 1 1-4 4" stroke="currentColor" stroke-width="1.8"/><path d="M11 12H3v3h2v3h3v-3h3" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </div>

                    <div class="security-text">

                        <h4>Password</h4>

                        <p>
                            <?= htmlspecialchars($password_changed) ?>
                        </p>

                    </div>

                </div>

                <button
                    class="btn-sm"
                    onclick="openPasswordModal()"
                >
                    Ubah
                </button>

            </div>


            <!-- 2FA -->
            <div class="security-item">

                <div class="security-info">

                    <div class="security-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M11 18h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>

                    <div class="security-text">

                        <h4>Verifikasi 2 Langkah (2FA)</h4>

                        <p>
                            Belum diaktifkan
                        </p>

                    </div>

                </div>

                <button class="btn-sm">
                    Aktifkan
                </button>

            </div>


            <!-- Email -->
            <div class="security-item">

                <div class="security-info">

                    <div class="security-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 7l9 7 9-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>

                    <div class="security-text">

                        <h4>Email Terverifikasi</h4>

                        <p>
                            <?= htmlspecialchars($email) ?>
                        </p>

                    </div>

                </div>

                <button
                    class="btn-sm"
                    style="border-color:#2dff6b;color:#2dff6b;"
                >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="margin-right:4px;vertical-align:-1px"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Terverifikasi
                </button>

            </div>

        </div>

    </div>

    </div>
    <!-- end left-column -->

    <div class="right-column">

<div class="profile-grid-card quick-card">

    <div class="card">

        <div class="card-title">

            <!-- Lightning Icon -->
            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            <span>Aksi Cepat</span>

        </div>


        <div class="quick-action-grid">

            <!-- Deposit -->
            <a href="#" class="quick-action-item" onclick="openDepositModal(); return false;">

                <div class="quick-action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect
                            x="3"
                            y="6"
                            width="18"
                            height="13"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M3 10h18"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M12 13v4M10 15h4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>

                </div>

                <span>Deposit</span>

            </a>


            <!-- Withdraw -->
            <a href="#" class="quick-action-item" onclick="openWithdrawModal(); return false;">

                <div class="quick-action-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M3 10h18" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>

                <span>Withdraw</span>

            </a>


            <!-- History -->
            <a href="#" class="quick-action-item" onclick="openHistoryModal(); return false;">

                <div class="quick-action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >

                        <path
                            d="M6 3h12v18H6z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M9 8h6M9 12h6M9 16h4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                    </svg>

                </div>

                <span>Riwayat</span>

            </a>


            <!-- Bonus -->
            <a href="#" class="quick-action-item" onclick="openBonusModal(); return false;">

                <div class="quick-action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >

                        <rect
                            x="3"
                            y="8"
                            width="18"
                            height="13"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M12 8v13"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M3 12h18"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M12 8H8.5a2.5 2.5 0 1 1 0-5C10.5 3 12 8 12 8Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M12 8h3.5a2.5 2.5 0 1 0 0-5C13.5 3 12 8 12 8Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />

                    </svg>

                </div>

                <span>Bonus</span>

            </a>

        </div>

    </div>

</div>

            <!-- WITHDRAW MODAL -->
<div id="withdrawModal" class="modal withdraw-modal">

    <div class="modal-content">

        <div class="withdraw-title">

            <svg viewBox="0 0 24 24" fill="none">
                <rect
                    x="3"
                    y="6"
                    width="18"
                    height="13"
                    rx="2"
                    stroke="currentColor"
                    stroke-width="2"
                />

                <path
                    d="M3 10h18"
                    stroke="currentColor"
                    stroke-width="2"
                />

                <path
                    d="M9 15h6"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                />
            </svg>

            <h3>Withdraw Saldo</h3>

        </div>

        <p class="withdraw-sub">
            Masukkan jumlah saldo yang ingin Anda tarik.
        </p>


        <!-- SALDO -->

        <div class="withdraw-balance">

            <div class="withdraw-balance-label">
                Saldo tersedia
            </div>

            <div class="withdraw-balance-value">
                Rp <?= number_format($balance, 0, ',', '.') ?>
            </div>

        </div>


        <!-- FORM -->

        <form
            id="withdrawForm"
            action="/casino/auth/withdraw.php"
            method="POST"
            onsubmit="return submitWithdraw(event)"
        >

            <!-- AMOUNT -->

            <div class="withdraw-amount-label">
                Jumlah Withdraw
            </div>

            <div class="withdraw-amount-input">

                <span class="prefix">Rp</span>

                <input
                    type="text"
                    id="withdrawAmount"
                    name="amount"
                    inputmode="numeric"
                    placeholder="0"
                    autocomplete="off"
                    required
                >

            </div>


            <!-- PRESET -->

            <div class="withdraw-presets">

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="50000"
                >
                    50.000
                </button>

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="100000"
                >
                    100.000
                </button>

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="250000"
                >
                    250.000
                </button>

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="500000"
                >
                    500.000
                </button>

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="1000000"
                >
                    1.000.000
                </button>

                <button
                    type="button"
                    class="withdraw-preset"
                    data-amount="2000000"
                >
                    2.000.000
                </button>

            </div>


            <!-- METHOD -->

            <div class="withdraw-method-label">
                Metode Withdraw
            </div>

            <div class="withdraw-methods">

                <label class="withdraw-method active">

                    <input
                        type="radio"
                        name="method"
                        value="bank"
                        checked
                    >

                    <div class="withdraw-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Z" stroke="currentColor" stroke-width="1.8"/><path d="M3 10l9-6 9 6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>

                    <div class="withdraw-method-text">

                        <strong>Bank</strong>

                        <span>Transfer Bank</span>

                    </div>

                </label>


                <label class="withdraw-method">

                    <input
                        type="radio"
                        name="method"
                        value="ewallet"
                    >

                    <div class="withdraw-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M11 18h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>

                    <div class="withdraw-method-text">

                        <strong>E-Wallet</strong>

                        <span>Dompet Digital</span>

                    </div>

                </label>

            </div>


            <!-- ACCOUNT -->

            <div class="withdraw-account">

                <label for="withdrawAccount">
                    Nomor Rekening / E-Wallet
                </label>

                <input
                    type="text"
                    id="withdrawAccount"
                    name="account"
                    placeholder="Masukkan nomor rekening"
                    autocomplete="off"
                    required
                >

            </div>


            <!-- ACTION -->

            <div class="withdraw-actions">

                <button
                    type="button"
                    class="btn-outline"
                    onclick="closeWithdrawModal()"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Withdraw Sekarang
                </button>

            </div>


            <div class="withdraw-note">
                Minimal withdraw Rp 50.000
            </div>

        </form>

    </div>

</div>


    <!-- =====================================================
         4. VIP STATUS
    ====================================================== -->
    <div class="profile-grid-card vip-card">

        <div class="card vip-status-card">

            <div class="card-title">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                >

                    <path
                        d="M12 2l2.4 7.2H22l-6 4.8 2.3 7.2L12 16.4 5.7 21.2 8 14 2 9.2h7.6L12 2z"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linejoin="round"
                    />

                </svg>

                <span>VIP Status</span>

            </div>


            <div class="vip-header">

                <div class="vip-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 8l4 3 5-7 5 7 4-3v10H3V8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>

                <div class="vip-info">

                    <div class="label">
                        Current Level
                    </div>

                    <div class="vip-level">
                        <?= htmlspecialchars($level) ?>
                    </div>

                </div>

            </div>


            <div class="vip-points">

                <span>
                    <?= number_format($points) ?>
                </span>

                Points

            </div>


            <div class="vip-progress">

                <div class="vip-progress-bar">

                    <div
                        class="vip-progress-fill"
                        style="width: 65%;"
                    ></div>

                </div>

                <div class="vip-progress-text">

                    <span>
                        Next Level
                    </span>

                    <span>
                        65%
                    </span>

                </div>

            </div>

        </div>

    </div>

    <!-- =====================================================
         5. AKTIVITAS TERBARU (di bawah VIP Card)
    ====================================================== -->
    <div class="card activity-horizontal">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M3 12h4l2-5 4 10 2-5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Aktivitas Terbaru</span>
        </div>

        <div class="activity-horizontal-list" id="activityFeed">
            <?php while($act = $activities->fetch_assoc()): ?>
            <div class="activity-item-h">
                <div class="activity-dot"></div>
                <div class="activity-content">
                    <h4><?= $act['icon'] ?> <?= htmlspecialchars($act['activity']) ?></h4>
                    <p><?= date("d M Y, H:i", strtotime($act['created_at'])) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    </div>
    <!-- end right-column -->

</div>

<!-- =========================================================
     END CONTENT GRID
========================================================= -->

<div id="confirmProfileModal" class="modal">

    <div class="modal-content">

        <h3 style="margin-bottom:10px;color:#FFD700;">
            Simpan Perubahan
        </h3>

        <p style="color:#bbb;margin-bottom:25px;">
            Apakah Anda yakin ingin menyimpan perubahan profil?
        </p>

        <div class="confirm-actions">

            <button class="btn-outline"
                    onclick="closeProfileConfirm()">
                Batal
            </button>

            <button class="btn-primary"
                    onclick="submitProfile()">
                Ya, Simpan
            </button>

        </div>

    </div>

</div>

<!-- DEPOSIT MODAL -->
<div id="depositModal" class="modal deposit-modal">
    <div class="modal-content">

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
                    <div class="deposit-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Z" stroke="currentColor" stroke-width="1.8"/><path d="M3 10l9-6 9 6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                    <div class="deposit-method-text">
                        <strong>Transfer Bank</strong>
                        <span>BCA · Mandiri · BNI</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="ewallet">
                    <div class="deposit-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M11 18h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                    <div class="deposit-method-text">
                        <strong>E-Wallet</strong>
                        <span>DANA · OVO · GoPay</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="qris">
                    <div class="deposit-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 4h2v2h-2v-2zm4-4h2v2h-2v-2zm-4 0h2v2h-2v-2zm4 4h2v2h-2v-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="deposit-method-text">
                        <strong>QRIS</strong>
                        <span>Scan &amp; bayar instan</span>
                    </div>
                </label>

                <label class="deposit-method">
                    <input type="radio" name="method" value="pulsa">
                    <div class="deposit-method-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 12a4 4 0 0 1 8 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>
                    </div>
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

<!-- HISTORY MODAL -->
<div id="historyModal" class="modal history-modal">
    <div class="modal-content">

        <span class="close" onclick="closeHistoryModal()">&times;</span>

        <div class="history-title">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M6 3h12v18H6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <h3>Riwayat</h3>
        </div>
        <p class="history-sub">Lihat transaksi dan taruhan terbaru Anda.</p>

        <div class="history-tabs">
            <button type="button" class="history-tab active" data-tab="transaksi" onclick="switchHistoryTab('transaksi')">Transaksi</button>
            <button type="button" class="history-tab" data-tab="bet" onclick="switchHistoryTab('bet')">Taruhan</button>
        </div>

        <div class="history-list" id="historyListTransaksi">

            <div class="history-item">
                <div class="history-item-icon in">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Deposit</strong>
                    <span>Bank Transfer · Hari ini</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount plus">+Rp 250.000</span>
                    <span class="status success">Sukses</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon out">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Withdraw</strong>
                    <span>E-Wallet · Kemarin</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount minus">-Rp 100.000</span>
                    <span class="status success">Sukses</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon in">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Bonus Welcome</strong>
                    <span>Promo · 3 hari lalu</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount plus">+Rp 50.000</span>
                    <span class="status success">Sukses</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon out">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Withdraw</strong>
                    <span>Bank · 5 hari lalu</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount minus">-Rp 500.000</span>
                    <span class="status pending">Pending</span>
                </div>
            </div>

        </div>

        <div class="history-list" id="historyListBet" style="display:none;">

            <div class="history-item">
                <div class="history-item-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Gates of Olympus</strong>
                    <span>Slot · 2 jam lalu</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount plus">+Rp 1.250.000</span>
                    <span class="status success">Win</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon out">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Sweet Bonanza</strong>
                    <span>Slot · 5 jam lalu</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount minus">-Rp 50.000</span>
                    <span class="status failed">Lose</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Aviator</strong>
                    <span>Crash · Kemarin</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount plus">+Rp 320.000</span>
                    <span class="status success">Win</span>
                </div>
            </div>

            <div class="history-item">
                <div class="history-item-icon out">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div class="history-item-body">
                    <strong>Blackjack VIP</strong>
                    <span>Live · 2 hari lalu</span>
                </div>
                <div class="history-item-meta">
                    <span class="amount minus">-Rp 75.000</span>
                    <span class="status failed">Lose</span>
                </div>
            </div>

        </div>

        <div class="history-actions">
            <button type="button" class="btn-outline" onclick="closeHistoryModal()">Tutup</button>
            <button type="button" class="btn-primary" onclick="closeHistoryModal()">Lihat Semua</button>
        </div>

    </div>
</div>

<!-- BONUS MODAL -->
<div id="bonusModal" class="modal bonus-modal">
    <div class="modal-content">

        <span class="close" onclick="closeBonusModal()">&times;</span>

        <div class="bonus-title">
            <svg viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8v13M3 12h18" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8H8.5a2.5 2.5 0 1 1 0-5C10.5 3 12 8 12 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M12 8h3.5a2.5 2.5 0 1 0 0-5C13.5 3 12 8 12 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <h3>Bonus & Promo</h3>
        </div>
        <p class="bonus-sub">Klaim promo aktif yang tersedia untuk akun Anda.</p>

        <div class="bonus-list">

            <div class="bonus-card">
                <div class="bonus-card-top">
                    <div class="bonus-card-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l2.2 6.6H21l-5.4 3.9 2.1 6.5L12 16.6 6.3 20l2.1-6.5L3 9.6h6.8L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="bonus-card-info">
                        <strong>Welcome Bonus 100%</strong>
                        <p>Deposit pertama, max Rp1.000.000</p>
                    </div>
                    <span class="bonus-card-badge available">Tersedia</span>
                </div>
                <div class="bonus-card-foot">
                    <span class="value">s/d Rp1.000.000</span>
                    <button type="button" class="btn-claim-sm" onclick="claimBonus(this, 'Welcome Bonus')">Klaim</button>
                </div>
            </div>

            <div class="bonus-card">
                <div class="bonus-card-top">
                    <div class="bonus-card-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                    </div>
                    <div class="bonus-card-info">
                        <strong>Cashback 10%</strong>
                        <p>Cashback mingguan dari total loss</p>
                    </div>
                    <span class="bonus-card-badge available">Tersedia</span>
                </div>
                <div class="bonus-card-foot">
                    <span class="value">10% Loss</span>
                    <button type="button" class="btn-claim-sm" onclick="claimBonus(this, 'Cashback')">Klaim</button>
                </div>
            </div>

            <div class="bonus-card">
                <div class="bonus-card-top">
                    <div class="bonus-card-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                    </div>
                    <div class="bonus-card-info">
                        <strong>Daily Reload 50%</strong>
                        <p>Reload harian, min deposit Rp50.000</p>
                    </div>
                    <span class="bonus-card-badge locked">VIP</span>
                </div>
                <div class="bonus-card-foot">
                    <span class="value">50% · Max 500rb</span>
                    <button type="button" class="btn-claim-sm" disabled>Terkunci</button>
                </div>
            </div>

            <div class="bonus-card">
                <div class="bonus-card-top">
                    <div class="bonus-card-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="1.8"/><path d="M12 16V4M8 8l4-4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="bonus-card-info">
                        <strong>Referral Reward</strong>
                        <p>Dapatkan komisi dari referral aktif</p>
                    </div>
                    <span class="bonus-card-badge claimed">Diklaim</span>
                </div>
                <div class="bonus-card-foot">
                    <span class="value">Rp25.000</span>
                    <button type="button" class="btn-claim-sm" disabled>Selesai</button>
                </div>
            </div>

        </div>

        <div class="bonus-actions">
            <button type="button" class="btn-outline" onclick="closeBonusModal()">Tutup</button>
            <button type="button" class="btn-primary" onclick="closeBonusModal()">Lihat Semua</button>
        </div>

    </div>
</div>

<!-- AVATAR CROP MODAL -->
<div id="avatarPreviewModal" class="modal">
    <div class="modal-content" style="text-align:center; width:480px;">

        <h3 style="margin-bottom:16px;color:#FFD700;">
            Sesuaikan Foto Profil
        </h3>

        <div style="width:100%; height:320px; overflow:hidden; margin-bottom:16px; display:flex; align-items:center; justify-content:center;">
            <img id="avatarPreviewImg" src="" alt="Preview" style="display:block; max-width:100%;">
        </div>

        <div class="crop-tools">

            <button type="button" class="crop-tool-btn" title="Zoom In" onclick="cropper.zoom(0.1)">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21l-4.35-4.35M11 8v6M8 11h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <button type="button" class="crop-tool-btn" title="Zoom Out" onclick="cropper.zoom(-0.1)">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21l-4.35-4.35M8 11h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <button type="button" class="crop-tool-btn" title="Putar" onclick="cropper.rotate(-90)">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 12a9 9 0 1 1 3 6.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M3 17v-5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <button type="button" class="crop-tool-btn" title="Reset" onclick="cropper.reset()">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 4v6h6M20 20v-6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M20 10a8 8 0 0 0-14.3-4.9M4 14a8 8 0 0 0 14.3 4.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

        </div>

        <div class="confirm-actions">
            <button class="btn-outline" onclick="cancelAvatarUpload()">
                Batal
            </button>
            <button class="btn-primary" onclick="confirmAvatarUpload()">
                Ya, Upload
            </button>
        </div>

    </div>
</div>

 <!-- PASSWORD MODAL -->
<div id="passwordModal" class="modal">

    <div class="modal-content">

        <span class="close" onclick="closePasswordModal()">
            &times;
        </span>

        <div class="card-title">
            <span style="display:inline-flex;align-items:center;gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.8"/></svg>
                Ubah Password
            </span>
        </div>

       <form action="/casino/auth/update-password.php"
      method="POST"
      onsubmit="return validatePassword()">
            <!-- Password Lama -->
            <div class="form-group">
                <label>Password Lama</label>

                <div class="password-field">
                    <input
                        type="password"
                        id="old_password"
                        name="old_password"
                        placeholder="Masukkan password lama"
                        required>

                    <span class="eye" onclick="togglePassword('old_password', this)">
                        <svg width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor"
                            d="M12 5C5 5 1 12 1 12s4 7 11 7s11-7 11-7s-4-7-11-7zm0 12a5 5 0 1 1 0-10a5 5 0 0 1 0 10z"/>
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Password Baru -->
            <div class="form-group">
                <label>Password Baru</label>

                <div class="password-field">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Masukkan password baru"
                        required>

                    <span class="eye" onclick="togglePassword('new_password', this)">
                        <svg width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor"
                            d="M12 5C5 5 1 12 1 12s4 7 11 7s11-7 11-7s-4-7-11-7zm0 12a5 5 0 1 1 0-10a5 5 0 0 1 0 10z"/>
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>

                <div class="password-field">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Ulangi password baru"
                        required>

                    <span class="eye" onclick="togglePassword('confirm_password', this)">
                        <svg width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor"
                            d="M12 5C5 5 1 12 1 12s4 7 11 7s11-7 11-7s-4-7-11-7zm0 12a5 5 0 1 1 0-10a5 5 0 0 1 0 10z"/>
                        </svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%">
                Simpan Password
            </button>

        </form>

        </div>

    </div>
    
</div>

<!-- LOGOUT MODAL -->
<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <h3 style="display:flex;align-items:center;justify-content:center;gap:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M15 16l5-5-5-5M9 11h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Logout
        </h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="logout-actions">
            <button type="button" class="logout-cancel" onclick="closeLogout()">Batal</button>
            <button type="button" class="logout-confirm" onclick="logoutNow()">Keluar</button>
        </div>
    </div>
</div>

<footer>
    © 2026 Royal Knight's • Bermain secara bertanggung jawab • 18+
</footer>

<div id="searchModal" class="search-modal">

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

    <button class="search-close" onclick="closeSearch()" aria-label="Tutup">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>

</div>

        <div class="search-result" id="searchResult">

        </div>

    </div>

</div>

<script>

const games = <?= json_encode($games, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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

</script>

<script>

function toggleUserMenu() {
    document.getElementById("userMenu").classList.toggle("show");
}


document.addEventListener("click", e => {
    if (!e.target.closest(".user-dropdown")) {
        document.getElementById("userMenu")?.classList.remove("show");
    }
});


window.addEventListener("scroll", () => {
    document.querySelector("header")?.classList.toggle("scrolled", window.scrollY > 20);
});


function showToast(title, message, type="success"){

    const toast = document.getElementById("toast");
    const icon = document.getElementById("toastIcon");


    toast.querySelector(".toast-title").innerText = title;
    toast.querySelector(".toast-message").innerText = message;


    if(type === "error"){

        icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>';
        icon.classList.add("error");

    }else{

        icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        icon.classList.remove("error");

    }


    toast.classList.remove("hide");
    toast.classList.add("show");


    setTimeout(()=>{

        toast.classList.remove("show");
        toast.classList.add("hide");

    },3000);

}



// PASSWORD MODAL

function openPasswordModal(){

    document.getElementById("passwordModal").style.display="flex";

}


function closePasswordModal(){

    document.getElementById("passwordModal").style.display="none";

}


window.addEventListener("click",function(e){

    const modal=document.getElementById("passwordModal");

    if(e.target===modal){
        modal.style.display="none";
    }

});

// ========== LOGOUT MODAL ==========
function confirmLogout(e){
    if(e) e.preventDefault();
    document.getElementById("logoutModal").classList.add("show");
}

function closeLogout(){
    document.getElementById("logoutModal").classList.remove("show");
}

function logoutNow(){
    window.location.href = "/casino/member/logout.php";
}

document.getElementById("logoutModal").addEventListener("click", function(e){
    if(e.target === this) closeLogout();
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

// ========== HISTORY MODAL ==========
function openHistoryModal(tab){
    const modal = document.getElementById("historyModal");
    modal.style.display = "flex";
    switchHistoryTab(tab === "bet" ? "bet" : "transaksi");
}

function closeHistoryModal(){
    document.getElementById("historyModal").style.display = "none";
}

function switchHistoryTab(tab){
    document.querySelectorAll(".history-tab").forEach(t => {
        t.classList.toggle("active", t.dataset.tab === tab);
    });
    document.getElementById("historyListTransaksi").style.display = tab === "transaksi" ? "flex" : "none";
    document.getElementById("historyListBet").style.display = tab === "bet" ? "flex" : "none";
}

window.addEventListener("click", function(e){
    const modal = document.getElementById("historyModal");
    if(e.target === modal) closeHistoryModal();
});

// ========== BONUS MODAL ==========
function openBonusModal(){
    document.getElementById("bonusModal").style.display = "flex";
}

function closeBonusModal(){
    document.getElementById("bonusModal").style.display = "none";
}

function claimBonus(btn, name){
    btn.disabled = true;
    btn.textContent = "Diklaim";

    const card = btn.closest(".bonus-card");
    const badge = card?.querySelector(".bonus-card-badge");
    if(badge){
        badge.textContent = "Diklaim";
        badge.className = "bonus-card-badge claimed";
    }

    showToast("Berhasil", "Bonus " + name + " berhasil diklaim.", "success");
}

window.addEventListener("click", function(e){
    const modal = document.getElementById("bonusModal");
    if(e.target === modal) closeBonusModal();
});

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
/* =========================================================
   WITHDRAW MODAL
========================================================= */

function openWithdrawModal(){

    const modal = document.getElementById("withdrawModal");

    modal.style.display = "flex";

    const amountInput =
        document.getElementById("withdrawAmount");

    const accountInput =
        document.getElementById("withdrawAccount");

    amountInput.value = "";

    accountInput.value = "";

    document
        .querySelectorAll(".withdraw-preset")
        .forEach(btn => {
            btn.classList.remove("active");
        });

    document
        .querySelectorAll(".withdraw-method")
        .forEach(method => {
            method.classList.remove("active");
        });

    const defaultMethod =
        document.querySelector(
            '.withdraw-method input[value="bank"]'
        );

    if(defaultMethod){

        defaultMethod.checked = true;

        defaultMethod
            .closest(".withdraw-method")
            .classList.add("active");

    }

    setTimeout(() => {
        amountInput.focus();
    }, 150);

}


function closeWithdrawModal(){

    document.getElementById("withdrawModal")
        .style.display = "none";

}


function formatWithdrawAmount(val){

    const num =
        String(val).replace(/\D/g, "");

    if(!num) return "";

    return Number(num).toLocaleString("id-ID");

}


function parseWithdrawAmount(val){

    return Number(
        String(val).replace(/\D/g, "")
    ) || 0;

}


/* INPUT NOMINAL */

const withdrawAmountInput =
    document.getElementById("withdrawAmount");

if(withdrawAmountInput){

    withdrawAmountInput.addEventListener(
        "input",
        function(){

            const raw =
                this.value.replace(/\D/g, "");

            this.value =
                formatWithdrawAmount(raw);

            const amount =
                Number(raw) || 0;

            document
                .querySelectorAll(".withdraw-preset")
                .forEach(btn => {

                    btn.classList.toggle(
                        "active",
                        Number(btn.dataset.amount) === amount
                    );

                });

        }
    );

}


/* PRESET NOMINAL */

document
    .querySelectorAll(".withdraw-preset")
    .forEach(btn => {

        btn.addEventListener(
            "click",
            function(){

                const amount =
                    this.dataset.amount;

                withdrawAmountInput.value =
                    formatWithdrawAmount(amount);

                document
                    .querySelectorAll(".withdraw-preset")
                    .forEach(b => {
                        b.classList.remove("active");
                    });

                this.classList.add("active");

                withdrawAmountInput.focus();

            }
        );

    });


/* METODE WITHDRAW */

document
    .querySelectorAll(".withdraw-method")
    .forEach(label => {

        label.addEventListener(
            "click",
            function(){

                document
                    .querySelectorAll(".withdraw-method")
                    .forEach(m => {
                        m.classList.remove("active");
                    });

                this.classList.add("active");

                const radio =
                    this.querySelector(
                        'input[type="radio"]'
                    );

                if(radio){
                    radio.checked = true;
                }

            }
        );

    });


/* SUBMIT */

function submitWithdraw(e){

    e.preventDefault();

    const amount =
        parseWithdrawAmount(
            withdrawAmountInput.value
        );

    const method =
        document.querySelector(
            'input[name="method"]:checked'
        )?.value;

    const account =
        document.getElementById(
            "withdrawAccount"
        ).value.trim();


    /* MINIMAL */

    if(amount < 50000){

        showToast(
            "Gagal",
            "Minimal withdraw adalah Rp 50.000",
            "error"
        );

        withdrawAmountInput.focus();

        return false;

    }


    /* CEK SALDO */

    const balance =
        <?= (float)$balance ?>;

    if(amount > balance){

        showToast(
            "Gagal",
            "Saldo Anda tidak mencukupi.",
            "error"
        );

        withdrawAmountInput.focus();

        return false;

    }


    /* METHOD */

    if(!method){

        showToast(
            "Gagal",
            "Pilih metode withdraw terlebih dahulu.",
            "error"
        );

        return false;

    }


    /* ACCOUNT */

    if(!account){

        showToast(
            "Gagal",
            "Masukkan nomor rekening atau E-Wallet.",
            "error"
        );

        document
            .getElementById("withdrawAccount")
            .focus();

        return false;

    }


    /* KIRIM ANGKA MURNI */

    withdrawAmountInput.value = amount;


    document
        .getElementById("withdrawForm")
        .submit();

    return true;

}


/* KLIK LUAR POPUP */

window.addEventListener(
    "click",
    function(e){

        const modal =
            document.getElementById(
                "withdrawModal"
            );

        if(e.target === modal){

            closeWithdrawModal();

        }

    }
);

function togglePassword(id, icon) {

    const input = document.getElementById(id);

    const eyeOpen = `
    <svg width="22" height="22" viewBox="0 0 24 24">
        <path fill="currentColor"
        d="M12 5C5 5 1 12 1 12s4 7 11 7s11-7 11-7s-4-7-11-7zm0 12a5 5 0 1 1 0-10a5 5 0 0 1 0 10z"/>
    </svg>`;

    const eyeOff = `
    <svg width="22" height="22" viewBox="0 0 24 24">
        <path fill="currentColor"
        d="M2.3 1L1 2.3l4.1 4.1C2.9 8.1 1 12 1 12s4 7 11 7c2.3 0 4.3-.7 6-1.8l3.7 3.7L23 19.7L2.3 1zM12 17c-5.2 0-8.5-5-8.5-5c.6-.9 1.7-2.3 3.4-3.4l2 2A5 5 0 0 0 15.4 17c-1 .4-2.1.6-3.4.6zm0-10a5 5 0 0 1 5 5c0 .8-.2 1.6-.5 2.2l-1.6-1.6c0-.2.1-.4.1-.6a3 3 0 0 0-3-3c-.2 0-.4 0-.6.1L9.8 7.5c.7-.3 1.4-.5 2.2-.5zm9.5 5c-.4.7-1.3 2-2.8 3.2l-1.5-1.5c.9-.8 1.5-1.6 1.8-2.2c0 0-3.3-5-8.5-5c-.6 0-1.2.1-1.8.2L7.2 5.2C8.7 4.5 10.3 4 12 4c7 0 11 8 11 8z"/>
    </svg>`;

    if (input.type === "password") {

        input.type = "text";
        icon.innerHTML = eyeOff;

    } else {

        input.type = "password";
        icon.innerHTML = eyeOpen;

    }

}
function validatePassword(){

    const new_password = document.getElementById("new_password");
    const confirm_password = document.getElementById("confirm_password");

    if(new_password.value !== confirm_password.value){

        showToast(
            "Gagal",
            "Password baru dan konfirmasi password tidak sama.",
            "error"
        );

        confirm_password.focus();

        return false;
    }

    return true;
}
function copyMemberID(){

    const id = document.getElementById("memberId").innerText;

    navigator.clipboard.writeText(id)
.then(()=>{
    showToast(
        "Berhasil",
        "Member ID berhasil disalin"
    );
})
.catch(()=>{
    showToast(
        "Gagal",
        "Clipboard tidak diizinkan",
        "error"
    );
});

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

        g.name.toLowerCase().includes(key) ||

        g.provider.toLowerCase().includes(key)

    )

);

});
popupSearch.addEventListener("keydown",function(e){

    if(e.key==="Enter"){

        const hasil = games.filter(g=>

            g.name.toLowerCase().includes(
                popupSearch.value.toLowerCase()
            )

        );

        if(hasil.length){

            playGame(hasil[0].id);

        }

    }

});

function renderSearch(list){

    // kondisi awal search dibuka
    if(popupSearch.value === "" && list.length === games.length){

        searchResult.innerHTML = `
        <div class="search-hero">

            <div class="search-hero-icon">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 10v4M12 9v6M16 10v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 18v2M18 18v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>

            <h2>Temukan Kemenangan Terbesarmu</h2>

            <p>
                Jelajahi <strong>2.000+</strong> permainan dari provider terbaik dunia.
            </p>

            <div class="hero-tags">

                <div class="hero-pill" data-cat="slots">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="16" cy="12" r="1.5" fill="currentColor"/></svg>
                    Slots
                </div>

                <div class="hero-pill" data-cat="live">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 10l6-3v10l-6-3v-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Live
                </div>

                <div class="hero-pill" data-cat="crash">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 16l6-6 4 4 6-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 6h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Crash
                </div>

                <div class="hero-pill" data-cat="table">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18" stroke="currentColor" stroke-width="1.8"/><circle cx="8" cy="15" r="1.2" fill="currentColor"/><circle cx="12" cy="15" r="1.2" fill="currentColor"/></svg>
                    Table
                </div>

                <div class="hero-pill" data-cat="all">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Semua
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
function playGame(id){
    window.location.href = "game.php?id=" + id;
}


// Filter kategori
document.addEventListener("click", function(e){

    if(!e.target.classList.contains("hero-pill")) return;

    document.querySelectorAll(".hero-pill")
        .forEach(x => x.classList.remove("active"));

    e.target.classList.add("active");

    const cat = e.target.dataset.cat;

    popupSearch.value = "";

    if(cat === "all"){
        renderSearch(games);
        return;
    }

    const result = games.filter(g => g.cat === cat);

    renderSearch(result);

});


// Debounce search

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

        if (g.rtp >= 96) {
            color = "#2dff6b";
        } else if (g.rtp >= 90) {
            color = "#FFD700";
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
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" aria-hidden="true"><path d="M12 20s-7-4.4-9.5-8.2C.7 9.2 1.6 5.8 4.6 4.6c2-.8 4.1-.2 5.4 1.4C11.3 4.4 13.4 3.8 15.4 4.6c3 1.2 3.9 4.6 2.1 7.2C19 15.6 12 20 12 20z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </div>

            <img src="${g.img}" class="game-thumb">

            <div class="play-overlay">
                <div class="play-btn">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="margin-right:4px"><path d="M8 5v14l11-7L8 5z"/></svg>
                    PLAY
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


// Tampilkan tampilan awal
renderSearch(games);

</script>

<!-- AVATAR UPLOAD SCRIPT -->
<script>

const avatarInput = document.getElementById("avatarInput");
let cropper = null;

avatarInput.addEventListener("change", function () {

    const file = this.files[0];
    if (!file) return;

    // Validasi ukuran (2MB)
    if (file.size > 2 * 1024 * 1024) {
        showToast("Gagal", "Ukuran file maksimal 2MB.", "error");
        this.value = "";
        return;
    }

    // Validasi tipe file
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
        showToast("Gagal", "Format harus JPG, PNG, atau WEBP.", "error");
        this.value = "";
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {

    const img = document.getElementById("avatarPreviewImg");
    img.src = e.target.result;

    document.getElementById("avatarPreviewModal").style.display = "flex";

    if (cropper) {
        cropper.destroy();
    }

            cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 2,
            dragMode: "move",
            autoCropArea: 1,
            cropBoxResizable: true,
            background: false,
            minCropBoxWidth: 100,
            minCropBoxHeight: 100,
            ready: function () {
                document.getElementById("avatarPreviewModal")
                    .querySelector(".modal-content")
                    .classList.add("avatar-crop-round");
            }
        });

};

    reader.readAsDataURL(file);
});

function cancelAvatarUpload() {

    avatarInput.value = "";

    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    document.getElementById("avatarPreviewModal").style.display = "none";
}

function confirmAvatarUpload() {

    if (!cropper) return;

    // Ambil hasil crop sebagai canvas, ukuran output 400x400
    cropper.getCroppedCanvas({
        width: 400,
        height: 400,
        imageSmoothingQuality: "high",
    }).toBlob(function (blob) {

        const formData = new FormData();
        formData.append("avatar", blob, "avatar.jpg");

        document.getElementById("avatarPreviewModal").style.display = "none";

        fetch(document.getElementById("avatarForm").action, {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.success) {

    showToast("Berhasil", data.message || "Foto profil berhasil diperbarui.", "success");

    if (data.avatar_url) {

        document.querySelectorAll(".avatar, .profile-avatar-lg").forEach(el => {

            el.style.backgroundImage = `url('${data.avatar_url}')`;
            el.style.backgroundSize = "cover";
            el.style.backgroundPosition = "center";

            el.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = "";
                }
            });

        });

    }

    // Tambahkan aktivitas baru ke daftar "Aktivitas Terbaru"
if (data.activity) {

    const feed = document.getElementById("activityFeed");

    if (feed) {

        const item = document.createElement("div");
        item.className = "activity-item-h";

        item.innerHTML = `
            <div class="activity-dot"></div>
            <div class="activity-content">
                <h4>${data.activity.icon} ${data.activity.activity}</h4>
                <p>${data.activity.created_at}</p>
            </div>
        `;

        feed.prepend(item);

        // Jaga supaya cuma tampil maksimal 2 item (samain dengan LIMIT 2 di query PHP)
        const items = feed.querySelectorAll(".activity-item-h");
        if (items.length > 2) {
            items[items.length - 1].remove();
        }

    }

}

} else {

    showToast("Gagal", data.message || "Terjadi kesalahan saat upload.", "error");

}

        })
        .catch(() => {
            showToast("Gagal", "Tidak dapat terhubung ke server.", "error");
        });

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        avatarInput.value = "";

    }, "image/jpeg", 0.9);
}

// Klik di luar modal untuk menutup
window.addEventListener("click", function (e) {
    const modal = document.getElementById("avatarPreviewModal");
    if (e.target === modal) {
        cancelAvatarUpload();
    }
});

</script>

<?php if(isset($_SESSION['toast'])): ?>

<script>

window.addEventListener("load",()=>{

   showToast(
    <?= json_encode($_SESSION['toast']['title']) ?>,
    <?= json_encode($_SESSION['toast']['message']) ?>,
    <?= json_encode($_SESSION['toast']['type'] ?? 'success') ?>
    );

});

</script>

<?php 
unset($_SESSION['toast']);
endif; 
?>

<script>

function heartbeat(){

    fetch("/casino/auth/heartbeat.php")
    .catch(()=>{});

}

// kirim sekali saat halaman dibuka
heartbeat();

// kirim setiap 30 detik
setInterval(heartbeat,30000);

const profileForm = document.getElementById("profileForm");

profileForm.addEventListener("submit", function(e){

    e.preventDefault();

    document.getElementById("confirmProfileModal").style.display = "flex";

});

function closeProfileConfirm(){

    document.getElementById("confirmProfileModal").style.display = "none";

}

function submitProfile(){

    profileForm.submit();

}

</script>



</body>
</html>