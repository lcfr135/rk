<?php
/**
 * Royal Knight's — Shared Navbar
 * Include: require_once __DIR__ . '/includes/navbar.php';
 *
 * Expected variables (set before include):
 *   $username, $balance, $level, $avatar_url (optional), $avatar_initials (optional)
 *   $active_nav = 'lobby' | 'games' | 'promosi' | 'vip' | 'kontak' (optional)
 */
if (!isset($username))  $username = $_SESSION['username'] ?? 'User';
if (!isset($balance))   $balance  = 0;
if (!isset($level))     $level    = 'Bronze';
if (!isset($avatar_initials)) {
    $avatar_initials = strtoupper(mb_substr($username, 0, 2));
}
$avatar_url  = $avatar_url  ?? null;
$active_nav  = $active_nav  ?? 'lobby';
?>
<style>
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
    -webkit-backdrop-filter:blur(22px);
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
    filter: drop-shadow(0 2px 8px rgba(255,215,0,.15));
}
.logo a{ display:block; }
.logo img{
    width:130px;
    display:block;
    position:relative;
    top:4px;
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
    background-size: cover;
    background-position: center;
}
.user-meta { line-height:1.25; }
.user-meta .name { font-size:13px; font-weight:600; }
.user-meta .level { font-size:11px; color:var(--gold, #FFD700); font-weight:500; }

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
.dropdown-menu a .nav-ico{
    width:18px;
    height:18px;
    flex-shrink:0;
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
.dropdown-menu a:last-child { border:none; color:var(--danger, #ff3b5c); }
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
    color:var(--danger, #ff3b5c);
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
    text-decoration:none;
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

/* Navbar modals */
.nav-modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    backdrop-filter:blur(8px);
    z-index:99999;
    align-items:center;
    justify-content:center;
}
.nav-modal-content{
    width:520px;
    max-width:94%;
    max-height:85vh;
    background:#15151c;
    border:1px solid rgba(255,215,0,.25);
    border-radius:24px;
    box-shadow:0 25px 70px rgba(0,0,0,.6);
    display:flex;
    flex-direction:column;
    overflow:hidden;
    animation:navModalShow .25s ease;
}
@keyframes navModalShow{
    from{ opacity:0; transform:scale(.92); }
    to{ opacity:1; transform:scale(1); }
}
.nav-modal-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 22px;
    border-bottom:1px solid rgba(255,255,255,.06);
    flex-shrink:0;
}
.nav-modal-header h3{
    display:flex;
    align-items:center;
    gap:10px;
    color:#FFD700;
    font-size:16px;
    font-weight:700;
    margin:0;
}
.nav-modal-header h3 .nav-ico{ width:20px; height:20px; }
.nav-modal-close{
    width:36px;
    height:36px;
    border:none;
    border-radius:10px;
    background:transparent;
    color:#888;
    font-size:22px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:.2s;
}
.nav-modal-close:hover{
    color:#FFD700;
    background:rgba(255,215,0,.08);
}
.nav-modal-tabs{
    display:flex;
    gap:6px;
    padding:12px 18px;
    border-bottom:1px solid rgba(255,255,255,.04);
    overflow-x:auto;
}
.nav-modal-tab{
    padding:8px 14px;
    border-radius:10px;
    border:none;
    background:transparent;
    color:#8a8a96;
    font-size:12.5px;
    font-weight:500;
    cursor:pointer;
    white-space:nowrap;
    transition:.2s;
}
.nav-modal-tab:hover{ color:#e8e0c0; background:rgba(255,255,255,.04); }
.nav-modal-tab.active{
    background:rgba(255,215,0,.12);
    color:#FFD700;
    font-weight:600;
}
.nav-modal-body{
    flex:1;
    overflow-y:auto;
    padding:16px 18px 22px;
    min-height:240px;
    max-height:52vh;
}
.nav-history-item{
    display:flex;
    align-items:center;
    gap:14px;
    padding:14px 12px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.05);
    background:rgba(255,255,255,.02);
    margin-bottom:10px;
    transition:.2s;
}
.nav-history-item:hover{
    border-color:rgba(255,215,0,.2);
    background:rgba(255,215,0,.04);
}
.nav-history-icon{
    width:42px;
    height:42px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    background:rgba(255,215,0,.1);
    color:#FFD700;
}
.nav-history-icon.plus{ background:rgba(45,255,107,.1); color:#2dff6b; }
.nav-history-icon.minus{ background:rgba(255,59,92,.1); color:#ff3b5c; }
.nav-history-icon .nav-ico{ width:20px; height:20px; }
.nav-history-info{ flex:1; min-width:0; }
.nav-history-info strong{
    display:block;
    font-size:13.5px;
    font-weight:600;
    color:#eee;
    margin-bottom:3px;
}
.nav-history-info span{ font-size:11.5px; color:#777; }
.nav-history-amount{ text-align:right; flex-shrink:0; }
.nav-history-amount .val{ display:block; font-size:14px; font-weight:700; }
.nav-history-amount .val.plus{ color:#2dff6b; }
.nav-history-amount .val.minus{ color:#ff3b5c; }
.nav-history-amount .st{ font-size:10.5px; color:#888; margin-top:2px; }
.nav-history-amount .st.ok{ color:#2dff6b; }
.nav-history-amount .st.pending{ color:#FFD700; }

.nav-bonus-card{
    padding:16px;
    border-radius:16px;
    border:1px solid rgba(255,215,0,.14);
    background:linear-gradient(135deg, rgba(255,215,0,.06), rgba(255,215,0,.02));
    margin-bottom:12px;
}
.nav-bonus-top{ display:flex; gap:14px; margin-bottom:12px; }
.nav-bonus-icon{
    width:48px; height:48px; border-radius:14px;
    background:linear-gradient(145deg,#FFE566,#FFD700 40%,#E0A800);
    color:#111; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.nav-bonus-icon .nav-ico{ width:24px; height:24px; }
.nav-bonus-info .tag{
    font-size:10px; font-weight:700; letter-spacing:1px;
    text-transform:uppercase; color:#FFD700; margin-bottom:4px;
}
.nav-bonus-info h4{ font-size:15px; font-weight:700; color:#fff; margin-bottom:4px; }
.nav-bonus-info p{ font-size:12px; color:#8a8a96; line-height:1.4; }
.nav-bonus-meta{
    display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.nav-bonus-meta .expiry{ font-size:11px; color:#777; }
.nav-bonus-meta button{
    height:34px; padding:0 16px; border:none; border-radius:10px;
    background:linear-gradient(135deg,#FFD700,#f0c000);
    color:#111; font-size:12px; font-weight:700; cursor:pointer; transition:.2s;
}
.nav-bonus-meta button:hover{ transform:translateY(-1px); box-shadow:0 4px 14px rgba(255,215,0,.3); }
.nav-bonus-meta button:disabled{
    background:rgba(255,255,255,.08); color:#888; cursor:default; box-shadow:none; transform:none;
}

.nav-empty{
    text-align:center; padding:48px 20px; color:#666; font-size:13px;
}

.logout-modal{
    position:fixed;
    inset:0;
    display:flex;
    justify-content:center;
    align-items:center;
    background:rgba(0,0,0,.45);
    backdrop-filter:blur(10px);
    opacity:0;
    visibility:hidden;
    transition:.3s ease;
    z-index:99999;
}
.logout-modal.show{ opacity:1; visibility:visible; }
.logout-box{
    width:360px;
    max-width:92%;
    background:#17171c;
    border:1px solid rgba(255,215,0,.25);
    border-radius:20px;
    padding:28px;
    text-align:center;
}
.logout-box h3{
    color:#FFD700;
    margin-bottom:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.logout-box h3 .nav-ico{ width:20px; height:20px; }
.logout-box p{ color:#bbb; margin-bottom:24px; font-size:14px; }
.logout-actions{ display:flex; gap:12px; }
.logout-actions button{
    flex:1;
    height:42px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
    font-size:13.5px;
}
.logout-cancel{ background:#333; color:#fff; }
.logout-confirm{ background:#ff3b5c; color:#fff; }
.logout-confirm:hover{ background:#ff2147; }

@media (max-width: 960px) {
    header { padding: 0 16px; }
    .nav-menu { display:none; }
    .balance-card { padding:6px 6px 6px 14px; }
    .balance-info .amount { font-size:14.5px; }
}
</style>

<!-- HEADER -->
<header>
    <div class="logo">
        <a href="member.php">
            <img src="../assets/logo/rk.png" alt="Royal Knight's">
        </a>
    </div>

    <nav class="navbar">
        <div class="nav-menu">
            <a href="member.php" class="<?= $active_nav === 'lobby' ? 'active' : '' ?>">Lobby</a>
            <a href="member.php" class="<?= $active_nav === 'games' ? 'active' : '' ?>">Games</a>
            <a href="#" class="<?= $active_nav === 'promosi' ? 'active' : '' ?>">Promosi</a>
            <a href="#" class="<?= $active_nav === 'vip' ? 'active' : '' ?>">VIP</a>
            <a href="#" class="<?= $active_nav === 'kontak' ? 'active' : '' ?>">Kontak</a>
            <a href="#" onclick="typeof openSearch==='function' ? openSearch(event) : event.preventDefault()" class="search-btn" title="Cari">
                <img src="../assets/icons/find.png" alt="">
            </a>
        </div>
    </nav>

    <div class="member-area">
        <div class="balance-card">
            <div class="balance-info">
                <div class="label">Saldo</div>
                <div class="amount">Rp <?= number_format((float)$balance, 0, ',', '.') ?></div>
            </div>
            <button class="btn-deposit" type="button" onclick="typeof openDepositModal==='function' ? openDepositModal() : location.href='member.php'">+ Deposit</button>
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
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profil Saya
                </a>
                <a href="#" onclick="openNavHistory(event,'transaksi')">
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Riwayat Transaksi
                </a>
                <a href="#" onclick="openNavHistory(event,'bet')">
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>
                    Riwayat Taruhan
                </a>
                <a href="#" onclick="openNavBonus(event)">
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                    Bonus &amp; Promo
                </a>
                <a href="settings.php">
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Pengaturan
                </a>
                <a href="#" onclick="confirmLogout(event)">
                    <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<!-- History Modal -->
<div id="navHistoryModal" class="nav-modal">
    <div class="nav-modal-content">
        <div class="nav-modal-header">
            <h3 id="navHistoryTitle">
                <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Riwayat
            </h3>
            <button type="button" class="nav-modal-close" onclick="closeNavHistory()">&times;</button>
        </div>
        <div class="nav-modal-tabs" id="navHistoryTabs">
            <button type="button" class="nav-modal-tab active" data-tab="transaksi" onclick="switchNavHistory('transaksi')">Transaksi</button>
            <button type="button" class="nav-modal-tab" data-tab="bet" onclick="switchNavHistory('bet')">Taruhan</button>
        </div>
        <div class="nav-modal-body" id="navHistoryBody"></div>
    </div>
</div>

<!-- Bonus Modal -->
<div id="navBonusModal" class="nav-modal">
    <div class="nav-modal-content">
        <div class="nav-modal-header">
            <h3>
                <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                Bonus &amp; Promo
            </h3>
            <button type="button" class="nav-modal-close" onclick="closeNavBonus()">&times;</button>
        </div>
        <div class="nav-modal-body" id="navBonusBody"></div>
    </div>
</div>

<!-- Logout Modal -->
<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <h3>
            <svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </h3>
        <p>Apakah Anda yakin ingin keluar dari akun?</p>
        <div class="logout-actions">
            <button type="button" class="logout-cancel" onclick="closeLogout()">Batal</button>
            <button type="button" class="logout-confirm" onclick="logoutNow()">Keluar</button>
        </div>
    </div>
</div>

<script>
(function(){
    const icoDown = `<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>`;
    const icoUp = `<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>`;
    const icoDice = `<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor" stroke="none"/></svg>`;
    const icoGift = `<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>`;

    const sampleTx = [
        { type:'deposit',  title:'Deposit Bank BCA',  date:'10 Agu 2026 · 14:32', amount:500000,  st:'ok', stText:'Berhasil' },
        { type:'withdraw', title:'Withdraw E-Wallet', date:'09 Agu 2026 · 21:10', amount:250000,  st:'pending', stText:'Diproses' },
        { type:'deposit',  title:'Deposit QRIS',      date:'08 Agu 2026 · 11:05', amount:100000,  st:'ok', stText:'Berhasil' },
        { type:'withdraw', title:'Withdraw Bank BNI', date:'07 Agu 2026 · 16:48', amount:750000,  st:'ok', stText:'Berhasil' }
    ];
    const sampleBets = [
        { type:'win',  title:'Gates of Olympus',   date:'10 Agu 2026 · 15:12', amount:1250000, stake:25000 },
        { type:'lose', title:'Sweet Bonanza',      date:'10 Agu 2026 · 14:58', amount:50000,   stake:50000 },
        { type:'win',  title:'Starlight Princess', date:'09 Agu 2026 · 22:41', amount:340000,  stake:20000 },
        { type:'lose', title:'Aviator',            date:'09 Agu 2026 · 20:03', amount:100000,  stake:100000 }
    ];
    const sampleBonus = [
        { id:1, tag:'WELCOME', title:'Bonus Deposit 100%', desc:'Min Rp50.000 · Maks Rp1.000.000', expiry:'Hingga 17 Agu 2026', claimed:false },
        { id:2, tag:'HARIAN',  title:'Cashback 10%', desc:'Cashback kekalahan slot setiap hari', expiry:'Reset 00:00', claimed:false },
        { id:3, tag:'VIP',     title:'Extra Spin 50x', desc:'Free spin member Gold ke atas', expiry:'3 hari setelah klaim', claimed:true }
    ];

    function fmt(n){ return 'Rp ' + Number(n).toLocaleString('id-ID'); }

    window.toggleUserMenu = function(){
        document.getElementById('userMenu')?.classList.toggle('show');
    };

    document.addEventListener('click', e => {
        if (!e.target.closest('.user-dropdown')) {
            document.getElementById('userMenu')?.classList.remove('show');
        }
    });

    window.addEventListener('scroll', () => {
        document.querySelector('header')?.classList.toggle('scrolled', window.scrollY > 20);
    });

    window.confirmLogout = function(e){
        if(e) e.preventDefault();
        document.getElementById('userMenu')?.classList.remove('show');
        document.getElementById('logoutModal')?.classList.add('show');
    };
    window.closeLogout = function(){
        document.getElementById('logoutModal')?.classList.remove('show');
    };
    window.logoutNow = function(){
        window.location.href = '/casino/member/logout.php';
    };
    document.getElementById('logoutModal')?.addEventListener('click', function(e){
        if(e.target === this) closeLogout();
    });

    window.openNavHistory = function(e, tab){
        if(e) e.preventDefault();
        document.getElementById('userMenu')?.classList.remove('show');
        document.getElementById('navHistoryModal').style.display = 'flex';
        switchNavHistory(tab || 'transaksi');
    };
    window.closeNavHistory = function(){
        document.getElementById('navHistoryModal').style.display = 'none';
    };
    window.switchNavHistory = function(tab){
        document.querySelectorAll('#navHistoryTabs .nav-modal-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.tab === tab);
        });
        const body = document.getElementById('navHistoryBody');
        const title = document.getElementById('navHistoryTitle');

        if(tab === 'bet'){
            title.innerHTML = icoDice + ' Riwayat Taruhan';
            if(!sampleBets.length){
                body.innerHTML = '<div class="nav-empty">Belum ada riwayat taruhan</div>';
                return;
            }
            body.innerHTML = sampleBets.map(b => {
                const win = b.type === 'win';
                return `<div class="nav-history-item">
                    <div class="nav-history-icon ${win?'plus':'minus'}">${icoDice}</div>
                    <div class="nav-history-info"><strong>${b.title}</strong><span>${b.date} · Stake ${fmt(b.stake)}</span></div>
                    <div class="nav-history-amount">
                        <span class="val ${win?'plus':'minus'}">${win?'+':'−'}${fmt(b.amount)}</span>
                        <div class="st ${win?'ok':''}">${win?'Menang':'Kalah'}</div>
                    </div>
                </div>`;
            }).join('');
        } else {
            title.innerHTML = `<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Riwayat Transaksi`;
            if(!sampleTx.length){
                body.innerHTML = '<div class="nav-empty">Belum ada transaksi</div>';
                return;
            }
            body.innerHTML = sampleTx.map(t => {
                const dep = t.type === 'deposit';
                return `<div class="nav-history-item">
                    <div class="nav-history-icon ${dep?'plus':'minus'}">${dep?icoDown:icoUp}</div>
                    <div class="nav-history-info"><strong>${t.title}</strong><span>${t.date}</span></div>
                    <div class="nav-history-amount">
                        <span class="val ${dep?'plus':'minus'}">${dep?'+':'−'}${fmt(t.amount)}</span>
                        <div class="st ${t.st}">${t.stText}</div>
                    </div>
                </div>`;
            }).join('');
        }
    };

    window.openNavBonus = function(e){
        if(e) e.preventDefault();
        document.getElementById('userMenu')?.classList.remove('show');
        const body = document.getElementById('navBonusBody');
        body.innerHTML = sampleBonus.map(b => `
            <div class="nav-bonus-card">
                <div class="nav-bonus-top">
                    <div class="nav-bonus-icon">${icoGift}</div>
                    <div class="nav-bonus-info">
                        <div class="tag">${b.tag}</div>
                        <h4>${b.title}</h4>
                        <p>${b.desc}</p>
                    </div>
                </div>
                <div class="nav-bonus-meta">
                    <span class="expiry">${b.expiry}</span>
                    <button type="button" ${b.claimed?'disabled':''} onclick="navClaimBonus(${b.id}, this)">${b.claimed?'Sudah Diklaim':'Klaim'}</button>
                </div>
            </div>
        `).join('');
        document.getElementById('navBonusModal').style.display = 'flex';
    };
    window.closeNavBonus = function(){
        document.getElementById('navBonusModal').style.display = 'none';
    };
    window.navClaimBonus = function(id, btn){
        const item = sampleBonus.find(b => b.id === id);
        if(!item || item.claimed) return;
        item.claimed = true;
        btn.disabled = true;
        btn.textContent = 'Sudah Diklaim';
        if(typeof showToast === 'function') showToast('Berhasil', 'Bonus berhasil diklaim!', 'success');
    };

    window.addEventListener('click', function(e){
        if(e.target === document.getElementById('navHistoryModal')) closeNavHistory();
        if(e.target === document.getElementById('navBonusModal')) closeNavBonus();
    });

    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape'){
            closeNavHistory();
            closeNavBonus();
            closeLogout();
        }
    });
})();
</script>
