<!doctype html>
<html lang="sr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ElitDent — Prijava</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

<header class="topbar">
  <div class="container topbar-inner">
    <div class="brand">
      <span class="logo" aria-hidden="true">
        <svg class="logo-svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M9.2 3.5c-2.9 0-5.2 2.2-5.2 5.1 0 2.2.8 3.8 1.6 5.2.5.9 1 1.7 1.2 2.6.4 1.9.6 4.3 1.9 5.6.8.8 2 .8 2.8.1.7-.7.9-1.8 1.1-2.8.3-1.3.4-2.4 1.6-2.4 1.1 0 1.3 1.1 1.6 2.4.2 1 .4 2.1 1.1 2.8.8.7 2 .7 2.8-.1 1.3-1.3 1.5-3.7 1.9-5.6.2-.9.7-1.7 1.2-2.6.8-1.4 1.6-3 1.6-5.2 0-2.9-2.3-5.1-5.2-5.1-1.2 0-2 .2-2.8.6-.7.3-1.3.6-2.2.6s-1.5-.3-2.2-.6c-.8-.4-1.6-.6-2.8-.6Z"
                fill="#ffffff" opacity="0.95"/>
        </svg>
      </span>
      <span class="brand-text">ElitDent</span>
    </div>

    <a class="btn btn-nav" href="/">Početna</a>
  </div>
</header>

@php
  $loginErr = $errors->getBag('login');
  $regErr   = $errors->getBag('register');
  $hErr     = $errors->getBag('health');
@endphp

<main class="auth-bg">
  <div class="container">
    <div class="auth-wrap" id="authWrap" data-active="{{ $active }}">

      <div class="auth-card">
        <div class="tabs">
          <button type="button" class="tab" data-tab="login">Login</button>
          <button type="button" class="tab" data-tab="register">Register</button>
          <div class="tab-indicator"></div>
        </div>

        <div class="content">

          <section class="panel" data-panel="login">
            <h1>Prijava</h1>
            <p class="sub">Uloguj se da nastaviš dalje.</p>

            <form method="POST" action="/login" class="form">
              @csrf
              <input type="hidden" name="_form" value="login">

              <label>Email</label>
              <input
                name="Email"
                type="email"
                value="{{ old('Email') }}"
                class="{{ $loginErr->has('Email') ? 'is-invalid' : '' }}"
                required
              >
              @if($loginErr->has('Email'))
                <div class="field-error">{{ $loginErr->first('Email') }}</div>
              @endif

              <label>Lozinka</label>
              <input
                name="Lozinka"
                type="password"
                value="{{ old('Lozinka') }}"
                class="{{ $loginErr->has('Lozinka') ? 'is-invalid' : '' }}"
                required
              >
              @if($loginErr->has('Lozinka'))
                <div class="field-error">{{ $loginErr->first('Lozinka') }}</div>
              @endif

              <button class="btn btn-primary" type="submit">Uloguj se</button>
            </form>
          </section>

          <section class="panel" data-panel="register">
            @if ($showHealth)
              <h1>Zdravstveni podaci (opciono)</h1>
              <p class="sub">Možeš preskočiti i dopuniti kasnije u profilu.</p>

              <form method="POST" action="{{ route('register.health.save') }}" class="form">
                @csrf

                <label>Alergije (opciono)</label>
                <input name="Alergije" value="{{ old('Alergije', $health->Alergije ?? '') }}"
                       class="{{ $hErr->has('Alergije') ? 'is-invalid' : '' }}">
                @if($hErr->has('Alergije')) <div class="field-error">{{ $hErr->first('Alergije') }}</div> @endif

                <label>Hronične bolesti (opciono)</label>
                <input name="HronicneBolesti" value="{{ old('HronicneBolesti', $health->HronicneBolesti ?? '') }}"
                       class="{{ $hErr->has('HronicneBolesti') ? 'is-invalid' : '' }}">
                @if($hErr->has('HronicneBolesti')) <div class="field-error">{{ $hErr->first('HronicneBolesti') }}</div> @endif

                <div class="grid2">
                  <div>
                    <label>Terapije (opciono)</label>
                    <input name="Terapije" value="{{ old('Terapije', $health->Terapije ?? '') }}"
                           class="{{ $hErr->has('Terapije') ? 'is-invalid' : '' }}">
                    @if($hErr->has('Terapije')) <div class="field-error">{{ $hErr->first('Terapije') }}</div> @endif
                  </div>
                  <div>
                    <label>Napomena (opciono)</label>
                    <input name="Napomena" value="{{ old('Napomena', $health->Napomena ?? '') }}"
                           class="{{ $hErr->has('Napomena') ? 'is-invalid' : '' }}">
                    @if($hErr->has('Napomena')) <div class="field-error">{{ $hErr->first('Napomena') }}</div> @endif
                  </div>
                </div>

                <button class="btn btn-primary" type="submit">Sačuvaj i nastavi</button>
              </form>

              <form method="POST" action="{{ route('register.health.skip') }}" class="form" style="margin-top:10px;">
                @csrf
                <button class="btn btn-secondary" type="submit">Preskoči za sada</button>
              </form>

            @else
              <h1>Registracija</h1>
              <p class="sub">Kreiranje naloga je dostupno samo za pacijente.</p>

              <form method="POST" action="{{ route('register') }}" class="form">
                @csrf
                <input type="hidden" name="_form" value="register">

                <div class="grid2">
                  <div>
                    <label>Ime</label>
                    <input name="Ime" value="{{ old('Ime') }}" required
                           class="{{ $regErr->has('Ime') ? 'is-invalid' : '' }}">
                    @if($regErr->has('Ime')) <div class="field-error">{{ $regErr->first('Ime') }}</div> @endif
                  </div>
                  <div>
                    <label>Prezime</label>
                    <input name="Prezime" value="{{ old('Prezime') }}" required
                           class="{{ $regErr->has('Prezime') ? 'is-invalid' : '' }}">
                    @if($regErr->has('Prezime')) <div class="field-error">{{ $regErr->first('Prezime') }}</div> @endif
                  </div>
                </div>

                <label>Email</label>
                <input name="Email" type="email" value="{{ old('Email') }}" required
                       class="{{ $regErr->has('Email') ? 'is-invalid' : '' }}">
                @if($regErr->has('Email')) <div class="field-error">{{ $regErr->first('Email') }}</div> @endif

                <div class="grid2">
                  <div>
                    <label>Lozinka</label>
                    <input name="Lozinka" type="password" value="{{ old('Lozinka') }}" required
                           class="{{ $regErr->has('Lozinka') ? 'is-invalid' : '' }}">
                    @if($regErr->has('Lozinka')) <div class="field-error">{{ $regErr->first('Lozinka') }}</div> @endif
                  </div>
                  <div>
                    <label>Potvrda lozinke</label>
                    <input name="Lozinka_confirmation" type="password" value="{{ old('Lozinka_confirmation') }}" required
                           class="{{ $regErr->has('Lozinka') ? 'is-invalid' : '' }}">
                  </div>
                </div>

                <div class="grid2">
                  <div>
                    <label>Datum rođenja</label>
                    <input name="DatumRodjenja" type="date" value="{{ old('DatumRodjenja') }}" required
                           class="{{ $regErr->has('DatumRodjenja') ? 'is-invalid' : '' }}">
                    @if($regErr->has('DatumRodjenja')) <div class="field-error">{{ $regErr->first('DatumRodjenja') }}</div> @endif
                  </div>
                  <div>
                    <label>Telefon</label>
                    <input name="Telefon" value="{{ old('Telefon') }}" required
                           class="{{ $regErr->has('Telefon') ? 'is-invalid' : '' }}">
                    @if($regErr->has('Telefon')) <div class="field-error">{{ $regErr->first('Telefon') }}</div> @endif
                  </div>
                </div>

                <label>Adresa</label>
                <input name="Adresa" value="{{ old('Adresa') }}" required
                       class="{{ $regErr->has('Adresa') ? 'is-invalid' : '' }}">
                @if($regErr->has('Adresa')) <div class="field-error">{{ $regErr->first('Adresa') }}</div> @endif

                <p class="hint">Nakon registracije otvoriće se korak za zdravstvene podatke (opciono).</p>

                <button class="btn btn-primary" type="submit">Registruj se</button>
              </form>
            @endif
          </section>

        </div>
      </div>

    </div>
  </div>
</main>

<script>
(function(){
  const wrap = document.getElementById('authWrap');
  const tabs = wrap.querySelectorAll('.tab');
  const panels = wrap.querySelectorAll('.panel');

  function showPanel(name){
    wrap.dataset.active = name;
    panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === name));
    const firstInvalid = document.querySelector('.panel.is-active input.is-invalid');
    if (firstInvalid) firstInvalid.focus();
  }

  showPanel(wrap.dataset.active || 'login');

  tabs.forEach(t => t.addEventListener('click', () => showPanel(t.dataset.tab)));

  window.addEventListener('load', () => {
    const firstInvalid = document.querySelector('.panel.is-active input.is-invalid');
    if (firstInvalid) return;
    const firstInput = document.querySelector('.panel.is-active input');
    if (firstInput) firstInput.focus();
  });
})();
</script>

</body>
</html>