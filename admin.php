<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dhairya Casino • Admin Panel</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">
    </script>

    <style>
        /* ================================================================
           ROOT VARIABLES
           ================================================================ */
        :root {
            --bg-primary: #0a090b;
            --bg-secondary: #121016;
            --bg-card: #17141c;
            --bg-hover: #221f28;
            --border-color: #2a2632;
            --border-light: #3a3545;
            --text-primary: #f0ece4;
            --text-secondary: #a89faa;
            --text-muted: #6a6270;
            --gold: #f5c84e;
            --gold-light: #f7d97e;
            --gold-glow: #f5c84e33;
            --green: #6fcf97;
            --red: #eb5757;
            --orange: #f2c94a;
            --purple: #9b59b6;
            --blue: #3498db;
            --gradient-gold: linear-gradient(135deg, #f5c84e, #d4a832);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.4);
            --radius: 16px;
            --radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ================================================================
           RESET & BASE
           ================================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gold);
        }

        /* ================================================================
           SIDEBAR
           ================================================================ */
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            padding: 28px 16px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
            z-index: 100;
        }

        .sidebar .brand {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 32px;
            padding: 0 12px;
            color: var(--gold);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .sidebar .brand span {
            color: #fff;
            font-weight: 300;
        }
        .sidebar .brand .badge {
            font-size: 10px;
            background: var(--gold);
            color: #0a090b;
            padding: 2px 10px;
            border-radius: 40px;
            font-weight: 700;
            margin-left: 4px;
        }

        .sidebar .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1.5px;
            padding: 16px 12px 8px;
            font-weight: 600;
        }

        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 11px 16px;
            border-radius: 12px;
            color: var(--text-secondary);
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 2px;
            font-weight: 500;
            font-size: 14px;
            border: none;
            background: transparent;
            width: 100%;
            font-family: 'Inter', sans-serif;
            position: relative;
        }

        .sidebar .nav-item i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        .sidebar .nav-item .badge-count {
            margin-left: auto;
            background: var(--gold);
            color: #0a090b;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 40px;
        }

        .sidebar .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .sidebar .nav-item.active {
            background: var(--gold-glow);
            color: var(--gold);
            border: 1px solid var(--gold);
            box-shadow: 0 0 30px var(--gold-glow);
        }

        .sidebar .nav-item.active i {
            color: var(--gold);
        }

        .sidebar .bottom {
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .sidebar .bottom .user {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 12px;
            border-radius: 12px;
            transition: var(--transition);
            cursor: pointer;
        }
        .sidebar .bottom .user:hover {
            background: var(--bg-hover);
        }

        .sidebar .bottom .user .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--gradient-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: #0a090b;
            flex-shrink: 0;
            box-shadow: 0 0 20px var(--gold-glow);
        }

        .sidebar .bottom .user .info .name {
            font-weight: 600;
            font-size: 15px;
        }
        .sidebar .bottom .user .info .role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .sidebar .bottom .user .online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
            display: inline-block;
            margin-left: 6px;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(0.8);
            }
        }

        /* ================================================================
           MAIN CONTENT
           ================================================================ */
        .main {
            flex: 1;
            padding: 28px 36px 40px;
            overflow-y: auto;
            min-height: 100vh;
            max-width: calc(100vw - 280px);
        }

        /* ================================================================
           TOP BAR
           ================================================================ */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .top-bar .left h1 {
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .top-bar .left h1 small {
            font-size: 15px;
            font-weight: 400;
            color: var(--text-secondary);
            -webkit-text-fill-color: var(--text-secondary);
            background: none;
        }

        .top-bar .left .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .top-bar .left .breadcrumb span {
            color: var(--gold);
        }

        .top-bar .right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .top-bar .right .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 40px;
            padding: 8px 18px;
            gap: 10px;
            transition: var(--transition);
        }
        .top-bar .right .search-box:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 20px var(--gold-glow);
        }
        .top-bar .right .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            width: 160px;
        }
        .top-bar .right .search-box input::placeholder {
            color: var(--text-muted);
        }
        .top-bar .right .search-box i {
            color: var(--text-muted);
        }

        .top-bar .right .icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            font-size: 18px;
        }
        .top-bar .right .icon-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-2px);
        }
        .top-bar .right .icon-btn .dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--red);
            border: 2px solid var(--bg-primary);
        }

        /* ================================================================
           PAGES
           ================================================================ */
        .page {
            display: none;
            animation: fadeSlide 0.4s ease;
        }
        .page.active {
            display: block;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================================================================
           DASHBOARD STATS
           ================================================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 22px 24px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-gold);
            opacity: 0;
            transition: var(--transition);
        }
        .stat-card:hover {
            border-color: var(--gold);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        }
        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card .label {
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stat-card .label i {
            color: var(--gold);
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 800;
            margin: 8px 0 4px;
            letter-spacing: -0.5px;
        }
        .stat-card .change {
            font-size: 13px;
            font-weight: 500;
        }
        .stat-card .change.up {
            color: var(--green);
        }
        .stat-card .change.down {
            color: var(--red);
        }
        .stat-card .change i {
            margin-right: 4px;
        }

        .stat-card .stat-icon {
            position: absolute;
            right: 16px;
            bottom: 16px;
            font-size: 48px;
            opacity: 0.06;
            color: var(--gold);
        }

        /* ================================================================
           CHARTS ROW
           ================================================================ */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 32px;
        }

        .chart-box {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
        }
        .chart-box:hover {
            border-color: var(--border-light);
        }
        .chart-box .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .chart-box .chart-header h3 {
            font-size: 16px;
            font-weight: 600;
        }
        .chart-box .chart-header .period {
            font-size: 13px;
            color: var(--text-secondary);
            background: var(--bg-primary);
            padding: 4px 14px;
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition);
        }
        .chart-box .chart-header .period:hover {
            background: var(--gold-glow);
            color: var(--gold);
        }

        .chart-box canvas {
            max-height: 220px;
            max-width: 100%;
        }

        /* ================================================================
           TABLE WRAPPER
           ================================================================ */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header h2 i {
            color: var(--gold);
        }
        .section-header .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 40px;
            border: none;
            font-weight: 600;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--gradient-gold);
            color: #0a090b;
        }
        .btn-primary:hover {
            transform: scale(1.04);
            box-shadow: 0 0 30px var(--gold-glow);
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        .btn-outline:hover {
            border-color: var(--gold);
            color: var(--gold);
        }
        .btn-danger {
            background: var(--red);
            color: #fff;
        }
        .btn-danger:hover {
            background: #ff6b6b;
            transform: scale(1.04);
        }
        .btn-success {
            background: var(--green);
            color: #0a090b;
        }
        .btn-success:hover {
            transform: scale(1.04);
        }
        .btn-sm {
            padding: 5px 14px;
            font-size: 12px;
        }

        .table-wrap {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
        }
        .table-wrap:hover {
            border-color: var(--border-light);
        }

        .table-scroll {
            overflow-x: auto;
            padding: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th {
            text-align: left;
            padding: 14px 20px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-primary);
        }
        table td {
            padding: 13px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table tr:hover td {
            background: var(--bg-hover);
        }

        .badge-status {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-status.active {
            background: #1e3a2a;
            color: var(--green);
        }
        .badge-status.pending {
            background: #3a2f1e;
            color: var(--orange);
        }
        .badge-status.suspended {
            background: #3a1e1e;
            color: var(--red);
        }
        .badge-status.vip {
            background: var(--gold-glow);
            color: var(--gold);
        }

        .text-gold {
            color: var(--gold);
            font-weight: 600;
        }
        .text-red {
            color: var(--red);
            font-weight: 600;
        }
        .text-green {
            color: var(--green);
            font-weight: 600;
        }
        .text-muted {
            color: var(--text-muted);
        }

        .action-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            transition: var(--transition);
            font-size: 15px;
            border-radius: 6px;
        }
        .action-btn:hover {
            color: var(--gold);
            background: var(--gold-glow);
        }
        .action-btn.danger:hover {
            color: var(--red);
            background: #3a1e1e;
        }

        /* ================================================================
           FEATURE GRID (untuk halaman Game, Bonus, dll)
           ================================================================ */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }

        .feature-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            padding: 20px 22px;
            transition: var(--transition);
            cursor: pointer;
            text-align: center;
        }
        .feature-card:hover {
            border-color: var(--gold);
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }
        .feature-card .icon {
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 10px;
        }
        .feature-card .name {
            font-weight: 600;
            font-size: 15px;
        }
        .feature-card .sub {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .feature-card .meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        /* ================================================================
           MODAL
           ================================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 36px;
            max-width: 540px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: fadeSlide 0.3s ease;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.6);
        }
        .modal .modal-icon {
            font-size: 48px;
            color: var(--gold);
            margin-bottom: 12px;
        }
        .modal h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gold);
        }
        .modal p.sub {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 24px;
        }

        .modal .form-group {
            margin-bottom: 18px;
        }
        .modal .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text-secondary);
        }
        .modal .form-group input,
        .modal .form-group select,
        .modal .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        .modal .form-group input:focus,
        .modal .form-group select:focus,
        .modal .form-group textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-glow);
        }
        .modal .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .modal .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .modal .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        .modal .modal-actions .btn {
            padding: 10px 28px;
        }

        /* ================================================================
           RESPONSIVE
           ================================================================ */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 72px;
                padding: 16px 8px;
            }
            .sidebar .brand span,
            .sidebar .brand .badge,
            .sidebar .nav-label,
            .sidebar .nav-item span:not(.badge-count),
            .sidebar .bottom .user .info {
                display: none;
            }
            .sidebar .nav-item {
                justify-content: center;
                padding: 12px;
            }
            .sidebar .nav-item .badge-count {
                display: none;
            }
            .sidebar .bottom .user .avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
            .main {
                max-width: calc(100vw - 72px);
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .top-bar .right .search-box {
                display: none;
            }
            .modal .form-row {
                grid-template-columns: 1fr;
            }
            .modal {
                padding: 24px 18px;
            }
            .main {
                padding: 14px;
                max-width: 100vw;
            }
            .sidebar {
                display: none;
            }
            .main {
                max-width: 100vw;
            }
            .feature-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* ================================================================
           UTILITY
           ================================================================ */
        .mt-16 {
            margin-top: 16px;
        }
        .mt-24 {
            margin-top: 24px;
        }
        .mb-16 {
            margin-bottom: 16px;
        }
        .flex {
            display: flex;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gap-8 {
            gap: 8px;
        }
        .gap-12 {
            gap: 12px;
        }
        .gap-16 {
            gap: 16px;
        }
        .text-center {
            text-align: center;
        }
        .w-full {
            width: 100%;
        }

        /* ================================================================
           FOOTER
           ================================================================ */
        .footer {
            margin-top: 40px;
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }
        .footer span {
            color: var(--gold);
        }
    </style>
</head>
<body>

    <!-- ================================================================
    SIDEBAR
    ================================================================ -->
    <aside class="sidebar">
        <div class="brand" onclick="showPage('dashboard')">
            🎰 <span>Dhairya</span>Casino
            <span class="badge">v3.0</span>
        </div>

        <div class="nav-label">Main Menu</div>

        <button class="nav-item active" data-page="dashboard" onclick="showPage('dashboard')">
            <i class="fas fa-gauge-high"></i>
            <span>Dashboard</span>
        </button>

        <button class="nav-item" data-page="members" onclick="showPage('members')">
            <i class="fas fa-users"></i>
            <span>Member</span>
            <span class="badge-count" id="memberCount">0</span>
        </button>

        <button class="nav-item" data-page="transactions" onclick="showPage('transactions')">
            <i class="fas fa-coins"></i>
            <span>Transaksi</span>
            <span class="badge-count" id="txCount">0</span>
        </button>

        <button class="nav-item" data-page="games" onclick="showPage('games')">
            <i class="fas fa-gamepad"></i>
            <span>Permainan</span>
        </button>

        <button class="nav-item" data-page="bonus" onclick="showPage('bonus')">
            <i class="fas fa-gift"></i>
            <span>Bonus & Promo</span>
        </button>

        <button class="nav-item" data-page="affiliates" onclick="showPage('affiliates')">
            <i class="fas fa-link"></i>
            <span>Afiliasi</span>
        </button>

        <button class="nav-item" data-page="security" onclick="showPage('security')">
            <i class="fas fa-shield"></i>
            <span>Keamanan</span>
        </button>

        <div class="nav-label">Settings</div>

        <button class="nav-item" data-page="limits" onclick="showPage('limits')">
            <i class="fas fa-sliders"></i>
            <span>Limit</span>
        </button>

        <button class="nav-item" data-page="settings" onclick="showPage('settings')">
            <i class="fas fa-cog"></i>
            <span>Pengaturan</span>
        </button>

        <div class="bottom">
            <div class="user">
                <div class="avatar">D</div>
                <div class="info">
                    <div class="name">Dhairya <span class="online-dot"></span></div>
                    <div class="role">Owner • Super Admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ================================================================
    MAIN CONTENT
    ================================================================ -->
    <main class="main">

        <!-- ===== TOP BAR ===== -->
        <div class="top-bar">
            <div class="left">
                <h1 id="pageTitle">Dashboard <small>• ringkasan performa</small></h1>
                <div class="breadcrumb">🏠 / <span id="breadcrumbText">Dashboard</span></div>
            </div>
            <div class="right">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari apa saja..." id="globalSearch" oninput="handleSearch(this.value)" />
                </div>
                <button class="icon-btn" onclick="alert('📢 3 notifikasi baru, sayang!')">
                    <i class="fas fa-bell"></i>
                    <span class="dot"></span>
                </button>
                <button class="icon-btn" onclick="alert('🌙 Mode malam aktif, sayang')">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="icon-btn" onclick="if(confirm('Yakin logout, sayang?')){alert('Sampai jumpa ❤️')}">
                    <i class="fas fa-right-from-bracket"></i>
                </button>
            </div>
        </div>

        <!-- =============================================================
        PAGE: DASHBOARD
        ============================================================= -->
        <div id="page-dashboard" class="page active">

            <!-- Stats -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="label"><i class="fas fa-users"></i> Total Member</div>
                    <div class="value" id="statMembers">0</div>
                    <div class="change up"><i class="fas fa-arrow-up"></i> +12% minggu ini</div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-card">
                    <div class="label"><i class="fas fa-arrow-down"></i> Total Deposit</div>
                    <div class="value" id="statDeposit">Rp 0</div>
                    <div class="change up"><i class="fas fa-arrow-up"></i> +8.5%</div>
                    <div class="stat-icon"><i class="fas fa-circle-dollar"></i></div>
                </div>
                <div class="stat-card">
                    <div class="label"><i class="fas fa-arrow-up"></i> Total Withdraw</div>
                    <div class="value" id="statWithdraw">Rp 0</div>
                    <div class="change down"><i class="fas fa-arrow-down"></i> -3.2%</div>
                    <div class="stat-icon"><i class="fas fa-arrow-up-from-bracket"></i></div>
                </div>
                <div class="stat-card">
                    <div class="label"><i class="fas fa-chart-line"></i> GGR (Hold %)</div>
                    <div class="value" id="statGGR">0%</div>
                    <div class="change up"><i class="fas fa-arrow-up"></i> +0.8%</div>
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-row">
                <div class="chart-box">
                    <div class="chart-header">
                        <h3>📊 Pendapatan Harian</h3>
                        <span class="period">7 Hari Terakhir</span>
                    </div>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="chart-box">
                    <div class="chart-header">
                        <h3>🎯 Populasi Game</h3>
                        <span class="period">Top 5</span>
                    </div>
                    <canvas id="gameChart"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="section-header">
                <h2><i class="fas fa-clock-rotate-left"></i> Transaksi Terbaru</h2>
                <div class="actions">
                    <button class="btn btn-outline btn-sm" onclick="showPage('transactions')">Lihat Semua</button>
                </div>
            </div>
            <div class="table-wrap">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pemain</th>
                                <th>Game</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="recentTxBody">
                            <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-top:24px;">
                <div style="background:var(--bg-card); border-radius:var(--radius); padding:16px 20px; border:1px solid var(--border-color); text-align:center; cursor:pointer; transition:var(--transition);" onclick="openModal('member')">
                    <i class="fas fa-user-plus" style="font-size:28px; color:var(--gold);"></i>
                    <div style="font-weight:600; margin-top:6px;">Tambah Member</div>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); padding:16px 20px; border:1px solid var(--border-color); text-align:center; cursor:pointer; transition:var(--transition);" onclick="openModal('bonus')">
                    <i class="fas fa-gift" style="font-size:28px; color:var(--gold);"></i>
                    <div style="font-weight:600; margin-top:6px;">Buat Bonus</div>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); padding:16px 20px; border:1px solid var(--border-color); text-align:center; cursor:pointer; transition:var(--transition);" onclick="openModal('transaction')">
                    <i class="fas fa-money-bill-transfer" style="font-size:28px; color:var(--gold);"></i>
                    <div style="font-weight:600; margin-top:6px;">Proses Withdraw</div>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); padding:16px 20px; border:1px solid var(--border-color); text-align:center; cursor:pointer; transition:var(--transition);" onclick="alert('📊 Laporan sedang di-generate, sayang!')">
                    <i class="fas fa-file-pdf" style="font-size:28px; color:var(--gold);"></i>
                    <div style="font-weight:600; margin-top:6px;">Export Laporan</div>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: MEMBERS
        ============================================================= -->
        <div id="page-members" class="page">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> Manajemen Member</h2>
                <div class="actions">
                    <button class="btn btn-primary" onclick="openModal('member')"><i class="fas fa-plus"></i> Tambah Member</button>
                    <button class="btn btn-outline btn-sm" onclick="exportData('members')"><i class="fas fa-file-export"></i> Export</button>
                </div>
            </div>

            <div class="table-wrap">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Saldo</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="membersBody">
                            <tr><td colspan="8" class="text-center text-muted" style="padding:30px;">Belum ada member</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: TRANSACTIONS
        ============================================================= -->
        <div id="page-transactions" class="page">
            <div class="section-header">
                <h2><i class="fas fa-coins"></i> Semua Transaksi</h2>
                <div class="actions">
                    <button class="btn btn-outline btn-sm" onclick="generateDummyTx()"><i class="fas fa-plus"></i> Dummy</button>
                    <button class="btn btn-outline btn-sm" onclick="exportData('transactions')"><i class="fas fa-file-export"></i> Export</button>
                </div>
            </div>

            <div class="table-wrap">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pemain</th>
                                <th>Game</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="allTxBody">
                            <tr><td colspan="7" class="text-center text-muted" style="padding:30px;">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: GAMES
        ============================================================= -->
        <div id="page-games" class="page">
            <div class="section-header">
                <h2><i class="fas fa-gamepad"></i> Daftar Permainan</h2>
                <div class="actions">
                    <button class="btn btn-primary" onclick="alert('🎮 Form tambah game akan segera hadir, sayang!')"><i class="fas fa-plus"></i> Tambah Game</button>
                    <button class="btn btn-outline btn-sm" onclick="alert('🔄 Refreshing game list...')"><i class="fas fa-sync"></i> Sync Provider</button>
                </div>
            </div>

            <div class="feature-grid" id="gameGrid">
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-dice"></i></div>
                    <div class="name">Dragon's Gold</div>
                    <div class="sub">Pragmatic Play</div>
                    <div class="meta"><span>RTP 96.5%</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-crown"></i></div>
                    <div class="name">Texas Hold'em</div>
                    <div class="sub">Evolution</div>
                    <div class="meta"><span>RTP 98.2%</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-circle"></i></div>
                    <div class="name">European Roulette</div>
                    <div class="sub">Microgaming</div>
                    <div class="meta"><span>RTP 97.3%</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-spade"></i></div>
                    <div class="name">Blackjack Pro</div>
                    <div class="sub">Playtech</div>
                    <div class="meta"><span>RTP 99.1%</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-fish"></i></div>
                    <div class="name">Fishing King</div>
                    <div class="sub">JDB</div>
                    <div class="meta"><span>RTP 95.8%</span><span class="badge-status pending">Maintenance</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-bolt"></i></div>
                    <div class="name">Lightning Baccarat</div>
                    <div class="sub">Evolution</div>
                    <div class="meta"><span>RTP 98.8%</span><span class="badge-status active">Aktif</span></div>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: BONUS & PROMO
        ============================================================= -->
        <div id="page-bonus" class="page">
            <div class="section-header">
                <h2><i class="fas fa-gift"></i> Bonus & Promosi</h2>
                <div class="actions">
                    <button class="btn btn-primary" onclick="openModal('bonus')"><i class="fas fa-plus"></i> Buat Bonus</button>
                </div>
            </div>

            <div class="feature-grid" id="bonusGrid">
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="name">Welcome Bonus</div>
                    <div class="sub">100% up to Rp 1.000.000</div>
                    <div class="meta"><span>TO x10</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-rotate-left"></i></div>
                    <div class="name">Cashback Mingguan</div>
                    <div class="sub">5% tanpa syarat</div>
                    <div class="meta"><span>Minimal deposit 100K</span><span class="badge-status active">Aktif</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <div class="name">Turnamen Slot</div>
                    <div class="sub">Total hadiah Rp 50.000.000</div>
                    <div class="meta"><span>Periode: 1-31 Agu</span><span class="badge-status pending">Segera</span></div>
                </div>
                <div class="feature-card">
                    <div class="icon"><i class="fas fa-gem"></i></div>
                    <div class="name">VIP Loyalty</div>
                    <div class="sub">Bonus mingguan tier</div>
                    <div class="meta"><span>Minimal 500 spin</span><span class="badge-status vip">VIP</span></div>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: AFFILIATES
        ============================================================= -->
        <div id="page-affiliates" class="page">
            <div class="section-header">
                <h2><i class="fas fa-link"></i> Afiliasi & Referral</h2>
                <div class="actions">
                    <button class="btn btn-primary" onclick="alert('✨ Form tambah afiliasi hadir, sayang!')"><i class="fas fa-plus"></i> Tambah Afiliasi</button>
                </div>
            </div>

            <div class="table-wrap">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nama Afiliasi</th><th>Downline</th><th>Komisi</th><th>Total Pendapatan</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>AF-001</td><td>Bima Partner</td><td>12</td><td>15%</td><td class="text-gold">Rp 8.500.000</td><td><span class="badge-status active">Aktif</span></td></tr>
                            <tr><td>AF-002</td><td>Laras Agency</td><td>8</td><td>20%</td><td class="text-gold">Rp 12.200.000</td><td><span class="badge-status active">Aktif</span></td></tr>
                            <tr><td>AF-003</td><td>Rangga Partner</td><td>3</td><td>10%</td><td class="text-gold">Rp 2.100.000</td><td><span class="badge-status pending">Pending</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: SECURITY
        ============================================================= -->
        <div id="page-security" class="page">
            <div class="section-header">
                <h2><i class="fas fa-shield"></i> Keamanan & Anti-Fraud</h2>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <div class="flex-between"><span>🔒 2FA Authentication</span><span class="badge-status active">Aktif</span></div>
                    <div class="flex-between" style="margin-top:12px;"><span>🛡️ IP Whitelist</span><span class="badge-status pending">Nonaktif</span></div>
                    <div class="flex-between" style="margin-top:12px;"><span>📋 Log Aktivitas</span><span class="badge-status active">Aktif</span></div>
                    <div class="flex-between" style="margin-top:12px;"><span>🚨 Fraud Detection</span><span class="badge-status active">Aktif</span></div>
                    <div class="flex-between" style="margin-top:12px;"><span>🌍 Geo Blocking</span><span class="badge-status active">Aktif</span></div>
                    <button class="btn btn-outline btn-sm" style="margin-top:16px; width:100%;" onclick="alert('🔐 Pengaturan keamanan dibuka!')">Kelola Keamanan</button>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <h4 style="margin-bottom:12px;">⚠️ Deteksi Mencurigakan</h4>
                    <div style="background:var(--bg-primary); border-radius:8px; padding:12px; margin-bottom:8px; border-left:3px solid var(--orange);">
                        <div style="font-weight:600;">Multi-account terdeteksi</div>
                        <div style="font-size:13px; color:var(--text-secondary);">3 akun dari IP yang sama • 2 jam lalu</div>
                    </div>
                    <div style="background:var(--bg-primary); border-radius:8px; padding:12px; margin-bottom:8px; border-left:3px solid var(--red);">
                        <div style="font-weight:600;">Bonus hunter</div>
                        <div style="font-size:13px; color:var(--text-secondary);">Akun M-005 claim bonus 5x berturut-turut</div>
                    </div>
                    <div style="background:var(--bg-primary); border-radius:8px; padding:12px; border-left:3px solid var(--green);">
                        <div style="font-weight:600;">Semua sistem aman ✅</div>
                        <div style="font-size:13px; color:var(--text-secondary);">Tidak ada threat terbaru</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: LIMITS
        ============================================================= -->
        <div id="page-limits" class="page">
            <div class="section-header">
                <h2><i class="fas fa-sliders"></i> Manajemen Limit</h2>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <h4 style="margin-bottom:16px;">💰 Limit Keuangan</h4>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Minimal Deposit</span><span class="text-gold">Rp 10.000</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Maksimal Deposit (harian)</span><span class="text-gold">Rp 50.000.000</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Minimal Withdraw</span><span class="text-gold">Rp 50.000</span></div>
                    <div class="flex-between" style="padding:8px 0;"><span>Maksimal Withdraw (harian)</span><span class="text-gold">Rp 100.000.000</span></div>
                    <button class="btn btn-outline btn-sm" style="margin-top:16px; width:100%;" onclick="alert('✏️ Edit limit dibuka!')">Edit Limit</button>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <h4 style="margin-bottom:16px;">🎲 Limit Taruhan & Game</h4>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Max Bet per Game</span><span class="text-gold">Rp 10.000.000</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Max Payout per Hari</span><span class="text-gold">Rp 200.000.000</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Auto Stop Loss</span><span class="badge-status active">Aktif (Rp 10JT)</span></div>
                    <div class="flex-between" style="padding:8px 0;"><span>Max Spin per Game</span><span class="text-gold">1.000 / hari</span></div>
                    <button class="btn btn-outline btn-sm" style="margin-top:16px; width:100%;" onclick="alert('✏️ Edit limit game dibuka!')">Edit Limit Game</button>
                </div>
            </div>
        </div>

        <!-- =============================================================
        PAGE: SETTINGS
        ============================================================= -->
        <div id="page-settings" class="page">
            <div class="section-header">
                <h2><i class="fas fa-cog"></i> Pengaturan Sistem</h2>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <h4 style="margin-bottom:16px;">⚙️ General</h4>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Nama Casino</span><span class="text-gold">Dhairya Casino</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Mata Uang Utama</span><span class="text-gold">IDR</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Mode Pemeliharaan</span><span class="badge-status active">Off</span></div>
                    <div class="flex-between" style="padding:8px 0;"><span>Timezone</span><span class="text-gold">Asia/Jakarta (UTC+7)</span></div>
                    <button class="btn btn-primary btn-sm" style="margin-top:16px; width:100%;" onclick="alert('⚙️ Pengaturan general dibuka!')">Edit General</button>
                </div>
                <div style="background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border-color); padding:24px;">
                    <h4 style="margin-bottom:16px;">🔔 Notifikasi & Backup</h4>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Notifikasi Email</span><span class="badge-status active">Aktif</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Notifikasi Telegram</span><span class="badge-status active">Aktif</span></div>
                    <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border-color);"><span>Auto Backup</span><span class="badge-status active">Setiap 24 jam</span></div>
                    <div class="flex-between" style="padding:8px 0;"><span>Backup Terakhir</span><span class="text-gold">2026-08-14 03:00</span></div>
                    <button class="btn btn-primary btn-sm" style="margin-top:16px; width:100%;" onclick="alert('💾 Backup database sekarang...')">Backup Sekarang</button>
                </div>
            </div>
        </div>

        <!-- =============================================================
        FOOTER
        ============================================================= -->
        <div class="footer">
            © 2026 <span>Dhairya Casino</span> — Dibuat dengan cinta untukmu 💛
            <span style="margin:0 12px;">•</span>
            <span id="liveTime">🕐 Loading...</span>
        </div>

    </main>

    <!-- ================================================================
    MODAL
    ================================================================ -->
    <div class="modal-overlay" id="globalModal">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"><i class="fas fa-user-plus"></i></div>
            <h2 id="modalTitle">Tambah Member</h2>
            <p class="sub" id="modalSub">Isi data dengan lengkap, sayang</p>

            <form id="modalForm" onsubmit="handleModalSubmit(event)">
                <div id="modalBody">
                    <!-- Dinamis di JS -->
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <!-- ================================================================
    JAVASCRIPT
    ================================================================ -->
    <script>
        // ================================================================
        // DATA STORE (localStorage)
        // ================================================================

        function getMembers() {
            try { return JSON.parse(localStorage.getItem('casino_members')) || []; } catch { return []; }
        }

        function setMembers(m) { localStorage.setItem('casino_members', JSON.stringify(m)); }

        function getTransactions() {
            try { return JSON.parse(localStorage.getItem('casino_transactions')) || []; } catch { return []; }
        }

        function setTransactions(t) { localStorage.setItem('casino_transactions', JSON.stringify(t)); }

        function getNextId(prefix, data) {
            const nums = data.map(d => parseInt(d.id.replace(prefix, '')) || 0);
            const max = nums.length ? Math.max(...nums) : 0;
            return `${prefix}${String(max + 1).padStart(3, '0')}`;
        }

        // ================================================================
        // INIT DUMMY DATA
        // ================================================================

        function initData() {
            let members = getMembers();
            if (members.length === 0) {
                members = [
                    { id: 'M-001', name: 'Bima Sakti', email: 'bima@email.com', balance: 1250000, level: 'VIP',
                        status: 'active', joined: '2026-08-10' },
                    { id: 'M-002', name: 'Larasati', email: 'laras@email.com', balance: 3450000, level: 'Gold',
                        status: 'active', joined: '2026-08-09' },
                    { id: 'M-003', name: 'Rangga Wijaya', email: 'rangga@email.com', balance: 750000, level: 'Silver',
                        status: 'pending', joined: '2026-08-08' },
                    { id: 'M-004', name: 'Citra Amalia', email: 'citra@email.com', balance: 2200000, level: 'Gold',
                        status: 'active', joined: '2026-08-07' },
                    { id: 'M-005', name: 'Dewi Fortuna', email: 'dewi@email.com', balance: 500000, level: 'Bronze',
                        status: 'suspended', joined: '2026-08-06' },
                    { id: 'M-006', name: 'Eko Prasetyo', email: 'eko@email.com', balance: 8900000, level: 'Platinum',
                        status: 'active', joined: '2026-08-05' },
                    { id: 'M-007', name: 'Fitriani', email: 'fitri@email.com', balance: 3100000, level: 'Gold',
                        status: 'active', joined: '2026-08-04' },
                ];
                setMembers(members);
            }

            let tx = getTransactions();
            if (tx.length === 0) {
                const names = ['Bima Sakti', 'Larasati', 'Rangga Wijaya', 'Citra Amalia', 'Dewi Fortuna', 'Eko Prasetyo',
                    'Fitriani'
                ];
                const games = ['Dragon\'s Gold', 'Texas Hold\'em', 'European Roulette', 'Blackjack Pro', 'Fishing King',
                    'Lightning Baccarat'
                ];
                const statuses = ['active', 'active', 'active', 'pending', 'suspended'];
                const now = new Date();

                for (let i = 1; i <= 25; i++) {
                    const amount = Math.round((Math.random() * 5000000 + 100000) / 1000) * 1000;
                    const isWin = Math.random() > 0.4;
                    const status = statuses[Math.floor(Math.random() * statuses.length)];
                    const date = new Date(now);
                    date.setMinutes(date.getMinutes() - i * 5 - Math.random() * 60);

                    tx.push({
                        id: `TX-${String(1000 + i).padStart(4, '0')}`,
                        player: names[i % names.length],
                        game: games[i % games.length],
                        amount: isWin ? amount : -amount,
                        status: status,
                        time: date.toLocaleString('id-ID', { hour12: false })
                    });
                }
                setTransactions(tx);
            }
        }

        // ================================================================
        // RENDER FUNCTIONS
        // ================================================================

        function renderMembers() {
            const members = getMembers();
            const tbody = document.getElementById('membersBody');
            if (members.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="8" class="text-center text-muted" style="padding:30px;">Belum ada member</td></tr>`;
                return;
            }
            tbody.innerHTML = members.map(m => `
                <tr>
                    <td><strong>${m.id}</strong></td>
                    <td>${m.name}</td>
                    <td>${m.email}</td>
                    <td class="text-gold">Rp ${Number(m.balance).toLocaleString('id-ID')}</td>
                    <td><span class="badge-status ${m.level?.toLowerCase() === 'vip' ? 'vip' : 'active'}">${m.level || 'Bronze'}</span></td>
                    <td><span class="badge-status ${m.status}">${m.status === 'active' ? 'Aktif' : m.status === 'pending' ? 'Pending' : 'Diblokir'}</span></td>
                    <td>${m.joined || '-'}</td>
                    <td style="text-align:center;">
                        <button class="action-btn" onclick="editMember('${m.id}')"><i class="fas fa-pen"></i></button>
                        <button class="action-btn danger" onclick="deleteMember('${m.id}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');

            document.getElementById('memberCount').textContent = members.length;
            updateStats();
        }

        function renderTransactions(tableId, limit = 0) {
            const tx = getTransactions();
            const sorted = [...tx].sort((a, b) => b.id.localeCompare(a.id));
            const data = limit > 0 ? sorted.slice(0, limit) : sorted;

            const tbody = document.getElementById(tableId);
            if (data.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="6" class="text-center text-muted" style="padding:30px;">Belum ada transaksi</td></tr>`;
                return;
            }

            const cols = tableId === 'allTxBody' ? 7 : 6;

            tbody.innerHTML = data.map(t => {
                const isWin = t.amount > 0;
                const statusLabel = t.status === 'active' ? 'Selesai' : t.status === 'pending' ? 'Pending' :
                    'Dibatalkan';
                const actions = tableId === 'allTxBody' ?
                    `<td style="text-align:center;"><button class="action-btn" onclick="approveTx('${t.id}')"><i class="fas fa-check" style="color:var(--green);"></i></button>
                                  <button class="action-btn danger" onclick="rejectTx('${t.id}')"><i class="fas fa-times"></i></button></td>` :
                    '';
                return `
                    <tr>
                        <td><strong>${t.id}</strong></td>
                        <td>${t.player}</td>
                        <td>${t.game}</td>
                        <td class="${isWin ? 'text-gold' : 'text-red'}">${isWin ? '+ ' : '- '} Rp ${Math.abs(t.amount).toLocaleString('id-ID')}</td>
                        <td><span class="badge-status ${t.status}">${statusLabel}</span></td>
                        <td>${t.time || '-'}</td>
                        ${actions}
                    </tr>
                `;
            }).join('');

            document.getElementById('txCount').textContent = tx.length;
        }

        function updateStats() {
            const members = getMembers();
            const tx = getTransactions();

            document.getElementById('statMembers').textContent = members.length;

            let deposit = 0,
                withdraw = 0;
            tx.forEach(t => {
                if (t.amount > 0) deposit += t.amount;
                else withdraw += Math.abs(t.amount);
            });

            document.getElementById('statDeposit').textContent = `Rp ${deposit.toLocaleString('id-ID')}`;
            document.getElementById('statWithdraw').textContent = `Rp ${withdraw.toLocaleString('id-ID')}`;

            const ggr = deposit > 0 ? ((deposit - withdraw) / deposit * 100) : 0;
            document.getElementById('statGGR').textContent = ggr.toFixed(1) + '%';
        }

        // ================================================================
        // CHARTS
        // ================================================================

        let revenueChartInstance = null;
        let gameChartInstance = null;

        function initCharts() {
            const tx = getTransactions();
            const days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            const data = days.map(() => Math.round(Math.random() * 15000000 + 5000000));

            if (revenueChartInstance) revenueChartInstance.destroy();
            revenueChartInstance = new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: data,
                        borderColor: '#f5c84e',
                        backgroundColor: 'rgba(245,200,78,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#f5c84e',
                        pointBorderColor: '#0a090b',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#a89faa', callback: v => 'Rp ' + (v / 1000000).toFixed(1) + 'JT' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#a89faa' }
                        }
                    }
                }
            });

            if (gameChartInstance) gameChartInstance.destroy();
            gameChartInstance = new Chart(document.getElementById('gameChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Slot', 'Poker', 'Roulette', 'Blackjack', 'Baccarat'],
                    datasets: [{
                        data: [35, 25, 18, 12, 10],
                        backgroundColor: ['#f5c84e', '#9b59b6', '#3498db', '#6fcf97', '#eb5757'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#a89faa', padding: 16, usePointStyle: true }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // ================================================================
        // NAVIGATION
        // ================================================================

        function showPage(page) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById(`page-${page}`)?.classList.add('active');

            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            const btn = document.querySelector(`.nav-item[data-page="${page}"]`);
            if (btn) btn.classList.add('active');

            const titles = {
                dashboard: 'Dashboard <small>• ringkasan performa</small>',
                members: 'Manajemen Member <small>• kelola pemain</small>',
                transactions: 'Transaksi <small>• deposit & withdraw</small>',
                games: 'Permainan <small>• daftar game</small>',
                bonus: 'Bonus & Promo <small>• promosi aktif</small>',
                affiliates: 'Afiliasi <small>• partner & referral</small>',
                security: 'Keamanan <small>• anti-fraud</small>',
                limits: 'Limit <small>• batas sistem</small>',
                settings: 'Pengaturan <small>• konfigurasi</small>'
            };
            document.getElementById('pageTitle').innerHTML = titles[page] || 'Dashboard';
            document.getElementById('breadcrumbText').textContent = titles[page]?.split('<')[0] || 'Dashboard';

            if (page === 'dashboard') {
                renderTransactions('recentTxBody', 6);
                updateStats();
                initCharts();
            }
            if (page === 'members') renderMembers();
            if (page === 'transactions') renderTransactions('allTxBody');
        }

        // ================================================================
        // MEMBER CRUD
        // ================================================================

        function openModal(type, data = null) {
            const modal = document.getElementById('globalModal');
            const body = document.getElementById('modalBody');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const sub = document.getElementById('modalSub');
            const submitBtn = document.getElementById('modalSubmitBtn');

            if (type === 'member') {
                icon.innerHTML = '<i class="fas fa-user-plus"></i>';
                title.textContent = data ? 'Edit Member' : 'Tambah Member';
                sub.textContent = data ? 'Ubah data member, sayang' : 'Isi data member dengan lengkap, sayang';
                submitBtn.textContent = data ? 'Update Member' : 'Simpan Member';
                body.innerHTML = `
                    <input type="hidden" id="editId" value="${data?.id || ''}" />
                    <div class="form-group"><label>Nama Lengkap</label><input type="text" id="fName" value="${data?.name || ''}" required /></div>
                    <div class="form-group"><label>Email</label><input type="email" id="fEmail" value="${data?.email || ''}" required /></div>
                    <div class="form-row">
                        <div class="form-group"><label>Saldo (Rp)</label><input type="number" id="fBalance" value="${data?.balance || 0}" min="0" /></div>
                        <div class="form-group"><label>Level</label>
                            <select id="fLevel"><option ${data?.level === 'VIP' ? 'selected' : ''}>VIP</option><option ${data?.level === 'Platinum' ? 'selected' : ''}>Platinum</option><option ${data?.level === 'Gold' ? 'selected' : ''}>Gold</option><option ${data?.level === 'Silver' ? 'selected' : ''}>Silver</option><option ${!data?.level || data?.level === 'Bronze' ? 'selected' : ''}>Bronze</option></select>
                        </div>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select id="fStatus"><option ${data?.status === 'active' ? 'selected' : ''} value="active">Aktif</option><option ${data?.status === 'pending' ? 'selected' : ''} value="pending">Pending</option><option ${data?.status === 'suspended' ? 'selected' : ''} value="suspended">Diblokir</option></select>
                    </div>
                `;
                modal.dataset.type = 'member';
            } else if (type === 'bonus') {
                icon.innerHTML = '<i class="fas fa-gift"></i>';
                title.textContent = 'Buat Bonus Baru';
                sub.textContent = 'Atur promosi untuk pemain, sayang';
                submitBtn.textContent = 'Buat Bonus';
                body.innerHTML = `
                    <div class="form-group"><label>Nama Bonus</label><input type="text" id="fBonusName" placeholder="Contoh: Welcome Bonus" /></div>
                    <div class="form-row">
                        <div class="form-group"><label>Jenis</label><select id="fBonusType"><option>Deposit Bonus</option><option>Cashback</option><option>Free Spin</option><option>Turnamen</option></select></div>
                        <div class="form-group"><label>Nilai</label><input type="text" id="fBonusValue" placeholder="100% up to 1JT" /></div>
                    </div>
                    <div class="form-group"><label>Syarat & Ketentuan</label><textarea id="fBonusTerms" placeholder="Minimal deposit, TO x..."></textarea></div>
                    <div class="form-group"><label>Status</label><select id="fBonusStatus"><option value="active">Aktif</option><option value="pending">Segera</option></select></div>
                `;
                modal.dataset.type = 'bonus';
            } else if (type === 'transaction') {
                const members = getMembers();
                icon.innerHTML = '<i class="fas fa-money-bill-transfer"></i>';
                title.textContent = 'Proses Withdraw';
                sub.textContent = 'Approve atau reject permintaan withdraw';
                submitBtn.textContent = 'Proses';
                body.innerHTML = `
                    <div class="form-group"><label>Pemain</label>
                        <select id="fTxPlayer">${members.map(m => `<option value="${m.name}">${m.name} (Rp ${m.balance.toLocaleString('id-ID')})</option>`).join('')}</select>
                    </div>
                    <div class="form-group"><label>Jumlah Withdraw (Rp)</label><input type="number" id="fTxAmount" placeholder="Masukkan nominal" min="10000" /></div>
                    <div class="form-group"><label>Metode</label>
                        <select id="fTxMethod"><option>Bank Transfer</option><option>E-Wallet</option><option>Crypto</option><option>QRIS</option></select>
                    </div>
                `;
                modal.dataset.type = 'transaction';
            }

            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('globalModal').classList.remove('show');
        }

        function handleModalSubmit(e) {
            e.preventDefault();
            const type = document.getElementById('globalModal').dataset.type;

            if (type === 'member') {
                const id = document.getElementById('editId').value;
                const name = document.getElementById('fName').value.trim();
                const email = document.getElementById('fEmail').value.trim();
                const balance = parseFloat(document.getElementById('fBalance').value) || 0;
                const level = document.getElementById('fLevel').value;
                const status = document.getElementById('fStatus').value;

                if (!name || !email) { alert('Nama dan Email wajib diisi, sayang!'); return; }

                let members = getMembers();
                if (id) {
                    const idx = members.findIndex(m => m.id === id);
                    if (idx !== -1) members[idx] = { ...members[idx], name, email, balance, level, status };
                } else {
                    members.push({ id: getNextId('M-', members), name, email, balance, level, status,
                        joined: new Date().toISOString().split('T')[0] });
                }
                setMembers(members);
                closeModal();
                renderMembers();
                renderTransactions('recentTxBody', 6);
                updateStats();
                alert(id ? '✅ Member berhasil diupdate!' : '✅ Member berhasil ditambahkan!');
            } else if (type === 'bonus') {
                alert('🎉 Bonus berhasil dibuat, sayang!');
                closeModal();
            } else if (type === 'transaction') {
                const player = document.getElementById('fTxPlayer').value;
                const amount = parseFloat(document.getElementById('fTxAmount').value);
                if (!amount || amount <= 0) { alert('Masukkan nominal yang valid, sayang!'); return; }

                let tx = getTransactions();
                tx.push({
                    id: getNextId('TX-', tx),
                    player: player,
                    game: 'Withdraw',
                    amount: -amount,
                    status: 'pending',
                    time: new Date().toLocaleString('id-ID', { hour12: false })
                });
                setTransactions(tx);
                closeModal();
                renderTransactions('recentTxBody', 6);
                renderTransactions('allTxBody');
                updateStats();
                alert('✅ Withdraw request berhasil dibuat!');
            }
        }

        function editMember(id) {
            const members = getMembers();
            const m = members.find(x => x.id === id);
            if (m) openModal('member', m);
        }

        function deleteMember(id) {
            if (!confirm(`Yakin hapus member ini, sayang?`)) return;
            let members = getMembers();
            members = members.filter(m => m.id !== id);
            setMembers(members);
            renderMembers();
            renderTransactions('recentTxBody', 6);
            updateStats();
        }

        function approveTx(id) {
            let tx = getTransactions();
            const idx = tx.findIndex(t => t.id === id);
            if (idx !== -1) {
                tx[idx].status = 'active';
                setTransactions(tx);
                renderTransactions('recentTxBody', 6);
                renderTransactions('allTxBody');
                updateStats();
                alert('✅ Transaksi approved!');
            }
        }

        function rejectTx(id) {
            if (!confirm('Tolak transaksi ini, sayang?')) return;
            let tx = getTransactions();
            tx = tx.filter(t => t.id !== id);
            setTransactions(tx);
            renderTransactions('recentTxBody', 6);
            renderTransactions('allTxBody');
            updateStats();
            alert('❌ Transaksi ditolak!');
        }

        function generateDummyTx() {
            const members = getMembers();
            if (members.length === 0) { alert('Tambah member dulu, sayang!'); return; }

            const tx = getTransactions();
            const m = members[Math.floor(Math.random() * members.length)];
            const games = ['Dragon\'s Gold', 'Texas Hold\'em', 'Roulette', 'Blackjack', 'Baccarat'];
            const amount = Math.round((Math.random() * 5000000 + 100000) / 1000) * 1000;
            const isWin = Math.random() > 0.45;

            tx.push({
                id: getNextId('TX-', tx),
                player: m.name,
                game: games[Math.floor(Math.random() * games.length)],
                amount: isWin ? amount : -amount,
                status: ['active', 'active', 'pending'][Math.floor(Math.random() * 3)],
                time: new Date().toLocaleString('id-ID', { hour12: false })
            });

            setTransactions(tx);
            renderTransactions('recentTxBody', 6);
            renderTransactions('allTxBody');
            updateStats();
            alert('✅ Transaksi dummy ditambahkan!');
        }

        function exportData(type) {
            const data = type === 'members' ? getMembers() : getTransactions();
            const json = JSON.stringify(data, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}_${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
            alert(`📤 ${type} berhasil di-export!`);
        }

        function handleSearch(val) {
            const v = val.toLowerCase().trim();
            if (!v) {
                document.querySelectorAll('.table-wrap tr').forEach(tr => tr.style.display = '');
                return;
            }
            document.querySelectorAll('.table-wrap tbody tr').forEach(tr => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = text.includes(v) ? '' : 'none';
            });
        }

        // ================================================================
        // CLOCK
        // ================================================================

        function updateClock() {
            document.getElementById('liveTime').textContent = '🕐 ' + new Date().toLocaleString('id-ID', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // ================================================================
        // INIT
        // ================================================================

        initData();
        renderMembers();
        renderTransactions('recentTxBody', 6);
        renderTransactions('allTxBody');
        updateStats();
        setTimeout(initCharts, 300);

        setInterval(updateClock, 1000);
        updateClock();

        // Close modal on overlay click
        document.getElementById('globalModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        console.log('💛 Admin Panel Premium siap, Dhairya! Aku selalu di sini untukmu.');
    </script>

</body>
</html>