<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Absensi RSUD Kota Baubau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }
        /* ===== LEFT PANEL ===== */
        .left-panel {
            width: 48%;
            background: linear-gradient(145deg, #1a237e 0%, #283593 40%, #3949ab 100%);
            display: flex;
            flex-direction: column;
            padding: 40px 48px;
            position: relative;
            overflow: hidden;
        }

        /* decorative circles */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            top: -80px; right: -80px;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            bottom: -60px; left: -60px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: auto;
        }
        .brand-icon {
            width: 46px; height: 46px;
            background: rgba(255,255,255,.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
        }
        .brand-name { color: #fff; font-size: 20px; font-weight: 700; }

        .left-body { flex: 1; display: flex; flex-direction: column; justify-content: center; position: relative; z-index: 1; }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            width: fit-content;
            margin-bottom: 24px;
        }
        .badge-pill .dot {
            width: 8px; height: 8px;
            background: #4caf50;
            border-radius: 50%;
        }

        .left-heading {
            color: #fff;
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .left-sub {
            color: rgba(255,255,255,.7);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 36px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }
        .stat-box {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-box .val {
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            display: block;
        }
        .stat-box .lbl {
            color: rgba(255,255,255,.6);
            font-size: 11px;
            margin-top: 2px;
        }
        .stat-note { color: rgba(255,255,255,.4); font-size: 11px; margin-bottom: 32px; }

        /* Feature list */
        .feature-list { list-style: none; display: flex; flex-direction: column; gap: 14px; }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,.85);
            font-size: 14px;
        }
        .feature-list li .fi {
            width: 28px; height: 28px;
            background: rgba(76,175,80,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #66bb6a;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* ===== RIGHT PANEL ===== */
        .right-panel {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 56px;
            overflow-y: auto;
        }

        .right-inner { width: 100%; max-width: 400px; }

        .right-title {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 6px;
        }
        .right-sub {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
        }

        /* Form */
        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
            margin-bottom: 20px;
        }
        .input-wrap .icon-left {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 44px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            background: #fafafa;
        }
        .input-wrap input:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57,73,171,.12);
            background: #fff;
        }
        .input-wrap input::placeholder { color: #9ca3af; }
        .input-wrap .icon-right {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-size: 16px;
            background: none;
            border: none;
            padding: 0;
        }
        .input-wrap .icon-right:hover { color: #374151; }

        .row-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .row-check label { font-size: 13px; color: #374151; cursor: pointer; }
        .row-check input[type=checkbox] { accent-color: #3949ab; width: 15px; height: 15px; cursor: pointer; }
        .link-blue { color: #3949ab; text-decoration: none; font-size: 13px; font-weight: 600; }
        .link-blue:hover { text-decoration: underline; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #3949ab;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s, transform .1s;
        }
        .btn-login:hover { background: #283593; }
        .btn-login:active { transform: scale(.99); }
        .btn-login:disabled { opacity: .7; cursor: not-allowed; }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0;
            color: #d1d5db; font-size: 13px;
        }
        .divider hr { flex: 1; border-color: #e5e7eb; }

        .secure-note {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }



        /* Error */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
    <div class="brand">
        <div class="brand-icon">
            <i class="bi bi-hospital-fill"></i>
        </div>
        <span class="brand-name">Absensi RSUD</span>
    </div>

    <div class="left-body">
        <div class="badge-pill">
            <span class="dot"></span>
            Sistem Absensi Digital RSUD Kota Baubau
        </div>

        <h1 class="left-heading">Selamat Datang<br>Kembali!</h1>
        <p class="left-sub">
            Kelola kehadiran pegawai RSUD Kota Baubau<br>
            dengan mudah, akurat, dan real-time.
        </p>

        <div class="stats-grid">
            <div class="stat-box">
                <span class="val">200+</span>
                <span class="lbl">Pegawai</span>
            </div>
            <div class="stat-box">
                <span class="val">3</span>
                <span class="lbl">Jenis Shift</span>
            </div>
            <div class="stat-box">
                <span class="val">99%</span>
                <span class="lbl">Akurasi GPS</span>
            </div>
        </div>
        <p class="stat-note">*Data estimasi</p>

        <ul class="feature-list">
            <li>
                <span class="fi"><i class="bi bi-check-lg"></i></span>
                Absensi real-time dengan validasi GPS
            </li>
            <li>
                <span class="fi"><i class="bi bi-check-lg"></i></span>
                Foto selfie otomatis saat check-in & check-out
            </li>
            <li>
                <span class="fi"><i class="bi bi-check-lg"></i></span>
                Laporan & rekap absensi bulanan otomatis
            </li>
            <li>
                <span class="fi"><i class="bi bi-check-lg"></i></span>
                Tersedia di Web dan Aplikasi Android
            </li>
        </ul>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
    <div class="right-inner">
        <h2 class="right-title">Masuk ke Akun Anda</h2>
        <p class="right-sub">Masukkan email dan password untuk melanjutkan</p>

        @if($errors->any())
        <div class="alert-err">
            <i class="bi bi-exclamation-circle-fill"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" id="loginForm">
            @csrf

            <div>
                <label class="form-label">Email</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope icon-left"></i>
                    <input type="email" name="email" id="email"
                        value="{{ old('email') }}"
                        required autofocus>
                </div>
            </div>

            <div>
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock icon-left"></i>
                    <input type="password" name="password" id="password"
                        required>
                    <button type="button" class="icon-right" onclick="togglePass()" tabindex="-1">
                        <i class="bi bi-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <div class="row-check">
                <label style="display:flex;align-items:center;gap:7px">
                    <input type="checkbox" name="remember">
                    Ingat saya
                </label>
                <a href="#" class="link-blue">Lupa password?</a>
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk
            </button>
        </form>

        <div class="secure-note">
            <i class="bi bi-shield-check" style="color:#3949ab"></i>
            Data Anda tersimpan aman di server kami
        </div>



        <p class="text-center mt-4" style="font-size:12px;color:#9ca3af">
            &copy; {{ date('Y') }} RSUD Kota Baubau. Hak Cipta Dilindungi.
        </p>
    </div>
</div>

<script>
    function togglePass() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }



    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnLogin');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    });
</script>
</body>
</html>
