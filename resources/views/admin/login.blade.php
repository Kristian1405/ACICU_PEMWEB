<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Portal Admin - ACICU</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Baloo+2:wght@400;500;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
  </head>
  <body class="admin-body admin-login-body">
    <div class="admin-login-card">
      <img
        src="{{ asset('src-acicu/logo.jpg') }}"
        alt="Logo ACICU"
        class="admin-login-logo"
      />

      <h1 class="admin-login-title">ACICU</h1>
      <p class="admin-login-org">ANEKA ACI BUNGCU</p>
      <p class="admin-login-sub">Portal Resmi Admin &amp; Pengelola Dapur</p>

      {{--
        Login memakai guard session Laravel. Akun dibuat lewat perintah
        `php artisan acicu:admin <email>` atau lewat panel yang tersedia.
      --}}
      <form
        id="adminLoginForm"
        class="admin-login-form"
        method="POST"
        action="{{ route('admin.login.attempt') }}"
        novalidate
      >
        @csrf
        <div>
          <label class="admin-label" for="loginUser">
            <i class="fa-regular fa-user"></i> Username / Email
          </label>
          <div class="admin-input-wrap">
            <i class="fa-regular fa-envelope"></i>
            <input
              type="text"
              id="loginUser"
              name="username"
              autocomplete="username"
              value="{{ old('username') }}"
              placeholder="Masukkan username atau email admin"
            />
          </div>
        </div>

        <div>
          <label class="admin-label" for="loginPassword">
            <i class="fa-solid fa-lock"></i> Kata Sandi
          </label>
          <div class="admin-input-wrap has-toggle">
            <i class="fa-solid fa-key"></i>
            <input
              type="password"
              id="loginPassword"
              name="password"
              autocomplete="current-password"
              placeholder="••••••••"
            />
            <button
              type="button"
              class="admin-toggle-pass"
              id="togglePassword"
              aria-label="Tampilkan kata sandi"
            >
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="admin-login-row">
          <label class="admin-check">
            <input type="checkbox" id="remember" name="remember" />
            Ingat saya
          </label>
          <a
            href="#"
            class="admin-link"
            id="forgotPassword"
            data-admin-disabled="Fitur lupa password terhubung ke sistem autentikasi yang belum diintegrasikan."
          >
            Lupa Password?
          </a>
        </div>

        <button type="submit" class="admin-btn pink">
          Login ke Dashboard <i class="fa-solid fa-arrow-right"></i>
        </button>

        <div
          class="admin-auth-notice {{ $errors->any() ? 'show' : '' }}"
          id="authNotice"
        >{{ $errors->first() }}</div>
      </form>

      <div class="admin-login-note">
        <i class="fa-solid fa-shield-halved"></i>
        Sistem Terenkripsi &amp; Khusus Pengelola Dapur ACICU
      </div>
    </div>

    <div class="admin-toast" id="adminToast" role="status" aria-live="polite"></div>

    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
  </body>
</html>
