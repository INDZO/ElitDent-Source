<!doctype html>
<html lang="sr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Doktor')</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  @stack('styles')
</head>
<body class="dash">

@php($u = \Illuminate\Support\Facades\Auth::user())

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="side-logo">
    <span class="tooth" aria-hidden="true">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path d="M9.2 3.5c-2.9 0-5.2 2.2-5.2 5.1 0 2.2.8 3.8 1.6 5.2.5.9 1 1.7 1.2 2.6.4 1.9.6 4.3 1.9 5.6.8.8 2 .8 2.8.1.7-.7.9-1.8 1.1-2.8.3-1.3.4-2.4 1.6-2.4 1.1 0 1.3 1.1 1.6 2.4.2 1 .4 2.1 1.1 2.8.8.7 2 .7 2.8-.1 1.3-1.3 1.5-3.7 1.9-5.6.2-.9.7-1.7 1.2-2.6.8-1.4 1.6-3 1.6-5.2 0-2.9-2.3-5.1-5.2-5.1-1.2 0-2 .2-2.8.6-.7.3-1.3.6-2.2.6s-1.5-.3-2.2-.6c-.8-.4-1.6-.6-2.8-.6Z"
              fill="#ffffff" opacity="0.95"/>
      </svg>
    </span>
    <div class="side-name">ElitDent</div>
  </div>

  <nav class="side-nav">
    <a class="side-link {{ request()->routeIs('doctor.profile') ? 'active' : '' }}" href="{{ route('doctor.profile') }}">Profil</a>
    <a class="side-link {{ request()->routeIs('doctor.schedule*') ? 'active' : '' }}" href="{{ route('doctor.schedule') }}">Raspored</a>
    <a class="side-link {{ request()->routeIs('doctor.work*') ? 'active' : '' }}" href="{{ route('doctor.work') }}">Rad</a>
  </nav>

  <div class="side-footer">
    <div class="who">
      <div class="who-name">{{ $u->Ime }} {{ $u->Prezime }}</div>
      <div class="who-mail">{{ $u->Email }}</div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn-logout" type="submit">Logout</button>
    </form>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <button class="menu-btn" id="openSidebar" aria-label="Otvori meni">☰</button>
    <div class="top-title">@yield('pageTitle','')</div>
  </header>

  <div class="content">
    @if(session('ok'))
      <div class="flash ok">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
      <div class="flash err">{{ session('err') }}</div>
    @endif

    @yield('content')
  </div>
</div>

<script>
(function(){
  const overlay = document.getElementById('overlay');
  const openBtn = document.getElementById('openSidebar');

  function open(){ document.body.classList.add('sidebar-open'); }
  function close(){ document.body.classList.remove('sidebar-open'); }

  openBtn?.addEventListener('click', open);
  overlay?.addEventListener('click', close);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>

@stack('scripts')
</body>
</html>

