<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Masuk — {{ config('app.name', 'Smart Bin IoT Dashboard') }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css" />
<link rel="stylesheet" href="{{ asset('/assets/dashboard/style.css') }}" />
<link rel="stylesheet" href="{{ asset('/assets/auth/login.css') }}" />
</head>
<body class="login-body">

<div class="login-shell" role="main" aria-label="Form masuk SMART BIN IoT Dashboard">

  <!-- ==================== BRAND PANEL ==================== -->
  <div class="login-brand">
    <div class="brand-glow" aria-hidden="true"></div>

    <div class="brand-top">
      <div class="logo-icon">🗑️</div>
      <div>
        <div class="logo-text">SMART BIN</div>
        <div class="logo-sub">IoT Dashboard</div>
      </div>
    </div>

    <div class="brand-mid">
      <h1>Pantau & kelola tempat sampah pintar dari satu dashboard.</h1>
      <p>Klasifikasi otomatis, monitoring kapasitas real-time, dan notifikasi dini — semua dalam satu sistem terpadu.</p>

      <ul class="brand-features">
        <li><i class="ti ti-scan" aria-hidden="true"></i><span>Klasifikasi sampah real-time dengan AI</span></li>
        <li><i class="ti ti-eye" aria-hidden="true"></i><span>Monitoring kapasitas seluruh smart bin</span></li>
        <li><i class="ti ti-chart-bar" aria-hidden="true"></i><span>Statistik & analitik tren sampah</span></li>
      </ul>
    </div>

    <div class="brand-status">
      <span class="status-dot" aria-hidden="true"></span>
      Sistem online — 24 perangkat terhubung
    </div>
  </div>

  <!-- ==================== FORM PANEL ==================== -->
  <div class="login-form-panel">
    <div class="login-form-wrap">

      <div class="login-form-head">
        <h2>Masuk ke Dashboard</h2>
        <p>Masukkan kredensial Anda untuk mengakses panel pemantauan.</p>
      </div>

      {{-- Session Status, contoh: pesan setelah reset password berhasil --}}
      @if (session('status'))
        <div class="auth-status">
          <i class="ti ti-circle-check" aria-hidden="true"></i> {{ session('status') }}
        </div>
      @endif

      <form class="login-form" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        {{-- Email Address --}}
        <label class="field-label" for="email">Email</label>
        <div class="input-icon-wrap @error('email') has-error @enderror">
          <i class="ti ti-user" aria-hidden="true"></i>
          <input type="email" id="email" name="email" value="{{ old('email') }}"
                 placeholder="admin@smartbin.io" required autofocus autocomplete="username" />
        </div>
        @error('email')
          <div class="field-error server">{{ $message }}</div>
        @enderror

        {{-- Password --}}
        <label class="field-label" for="password">Kata Sandi</label>
        <div class="input-icon-wrap @error('password') has-error @enderror">
          <i class="ti ti-lock" aria-hidden="true"></i>
          <input type="password" id="password" name="password"
                 placeholder="••••••••" required autocomplete="current-password" />
          <i class="ti ti-eye toggle-pass" id="toggle-pass" aria-hidden="true" role="button" tabindex="0" aria-label="Tampilkan kata sandi"></i>
        </div>
        @error('password')
          <div class="field-error server">{{ $message }}</div>
        @enderror

        <div class="form-row">
          {{-- Remember Me --}}
          <label class="remember-row" for="remember_me">
            <span class="toggle-switch">
              <input type="checkbox" id="remember_me" name="remember" {{ old('remember') ? 'checked' : '' }} />
              <span class="toggle-track" aria-hidden="true"></span>
            </span>
            <span>Ingat saya</span>
          </label>

          {{-- Forgot Password --}}
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
          @endif
        </div>

        <button class="btn-primary login-btn" type="submit" id="login-submit-btn">
          <i class="ti ti-login-2" aria-hidden="true"></i> Masuk
        </button>
      </form>

      <div class="login-divider"><span>atau</span></div>

      <button class="btn-secondary login-btn-secondary" type="button" disabled title="Belum terhubung ke backend">
        <i class="ti ti-qrcode" aria-hidden="true"></i> Masuk dengan QR Perangkat
      </button>

      {{-- Register link, hanya tampil jika route register tersedia --}}
      <div class="login-footnote">
        @if (Route::has('register'))
          Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>
        @else
          Belum punya akun? <a href="mailto:admin@smartbin.io">Hubungi administrator</a>
        @endif
      </div>
    </div>

    <div class="login-page-footer">© {{ date('Y') }} {{ config('app.name', 'Smart Bin IoT System') }} · v1.0</div>
  </div>

</div>

<script>
/* ---- Toggle password visibility ---- */
const passInput = document.getElementById('password');
const togglePass = document.getElementById('toggle-pass');

function togglePasswordVisibility() {
  const isText = passInput.type === 'text';
  passInput.type = isText ? 'password' : 'text';
  togglePass.classList.toggle('ti-eye', isText);
  togglePass.classList.toggle('ti-eye-off', !isText);
  togglePass.setAttribute('aria-label', isText ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
}

togglePass.addEventListener('click', togglePasswordVisibility);
togglePass.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); togglePasswordVisibility(); }
});

/* ---- Submit feedback (does not block real form submission) ---- */
const form = document.querySelector('.login-form');
const submitBtn = document.getElementById('login-submit-btn');

form.addEventListener('submit', () => {
  submitBtn.innerHTML = '<i class="ti ti-loader" aria-hidden="true"></i> Memverifikasi...';
  submitBtn.disabled = true;
  // Form tetap di-submit secara normal ke route('login'); Laravel yang menangani redirect/error.
});
</script>

</body>
</html>
