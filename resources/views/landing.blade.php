<!doctype html>
<html lang="sr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ElitDent — Stomatološka ordinacija</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
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

      <a class="btn btn-nav" href="{{ route('login') }}">Uloguj se</a>
    </div>
  </header>

  <main>

    <section class="hero">
      <div class="container hero-inner">

        <div class="hero-left">
          <div class="hero-kicker">Stomatološka ordinacija</div>

          <h1 class="hero-title">
            Osmeh koji traje — uz savremenu stomatološku negu.
          </h1>

          <p class="hero-lead">
            Preventiva, estetika i kompleksne intervencije — uz pažljiv pristup, savremene protokole i jasno objašnjenje terapije.
          </p>

          <div class="hero-badges">
            <span class="badge">Individualan pristup</span>
            <span class="badge">Savremena oprema</span>
            <span class="badge">Transparentne preporuke</span>
          </div>

          <div class="hero-cta">
            <a class="btn btn-hero" href="{{ route('login') }}">Uloguj se</a>
            <span class="hero-note">Jedan Angular login preusmerava svaku ulogu na odgovarajući Laravel dashboard.</span>
          </div>
        </div>

        <div class="hero-right">
          <div class="hero-visual">
            <svg class="hero-svg" viewBox="0 0 520 420" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustracija ordinacije">
              <defs>
                <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0" stop-color="#ff5fa2" stop-opacity="0.22"/>
                  <stop offset="1" stop-color="#ff86bb" stop-opacity="0.10"/>
                </linearGradient>
                <linearGradient id="g2" x1="0" y1="1" x2="1" y2="0">
                  <stop offset="0" stop-color="#ffe4ee"/>
                  <stop offset="1" stop-color="#ffffff"/>
                </linearGradient>
              </defs>

              <rect x="18" y="18" width="484" height="384" rx="28" fill="url(#g2)" stroke="rgba(0,0,0,0.06)"/>
              <circle cx="120" cy="110" r="72" fill="url(#g1)"/>
              <circle cx="410" cy="130" r="58" fill="url(#g1)"/>
              <circle cx="380" cy="300" r="86" fill="url(#g1)"/>

              <path d="M240 150c-40 0-72 32-72 72 0 30 11 52 22 71 7 12 14 23 17 36 5 26 8 57 26 75 11 11 28 11 39 1 10-10 12-25 15-38 4-18 6-31 23-31 16 0 19 13 23 31 3 13 5 28 15 38 11 10 28 10 39-1 18-18 21-49 26-75 3-13 10-24 17-36 11-19 22-41 22-71 0-40-32-72-72-72-17 0-28 3-40 9-10 4-18 8-31 8s-21-4-31-8c-12-6-23-9-40-9Z"
                    fill="#ff5fa2" opacity="0.90"/>

              <rect x="62" y="310" width="190" height="26" rx="13" fill="rgba(255,95,162,0.16)"/>
              <rect x="62" y="345" width="250" height="18" rx="9" fill="rgba(17,24,39,0.08)"/>
            </svg>

            <div class="hero-mini">
              <div class="mini-card">
                <div class="mini-title">Sterilizacija</div>
                <div class="mini-text">Standardi i bezbednost</div>
              </div>
              <div class="mini-card">
                <div class="mini-title">Komfor</div>
                <div class="mini-text">Miran i pažljiv pristup</div>
              </div>
              <div class="mini-card">
                <div class="mini-title">Plan terapije</div>
                <div class="mini-text">Jasne preporuke</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <section class="section services">
      <div class="container">
        <div class="section-head">
          <h2>Naše usluge</h2>
          <p>Od preventive do kompleksnih zahvata — uz savremene protokole i komfor pacijenta.</p>
        </div>

        <div class="grid cards-6">
          <div class="card">
            <div class="card-icon">🪥</div>
            <h3>Preventiva</h3>
            <p>Pregledi, čišćenje kamenca, poliranje i saveti za higijenu.</p>
          </div>
          <div class="card">
            <div class="card-icon">✨</div>
            <h3>Estetika</h3>
            <p>Izbeljivanje, kompozitne korekcije i dizajn osmeha.</p>
          </div>
          <div class="card">
            <div class="card-icon">🦷</div>
            <h3>Endodoncija</h3>
            <p>Lečenje kanala uz preciznu dijagnostiku i kontrolu.</p>
          </div>
          <div class="card">
            <div class="card-icon">👑</div>
            <h3>Protetika</h3>
            <p>Krunice i nadoknade za funkcionalan i prirodan osmeh.</p>
          </div>
          <div class="card">
            <div class="card-icon">📐</div>
            <h3>Ortodoncija</h3>
            <p>Plan terapije i korekcije zuba uz individualan pristup.</p>
          </div>
          <div class="card">
            <div class="card-icon">🧩</div>
            <h3>Hirurgija</h3>
            <p>Intervencije uz adekvatnu pripremu i postoperativne smernice.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section why">
      <div class="container">
        <div class="why-wrap">
          <div class="why-left">
            <div class="section-head compact">
              <h2>Zašto ElitDent</h2>
              <p>Fokus na kvalitet, komfor i jasnu komunikaciju sa pacijentom.</p>
            </div>

            <ul class="feature-list">
              <li><span class="tick"></span> Detaljan pregled i plan terapije pre intervencije</li>
              <li><span class="tick"></span> Objašnjenje svake opcije i preporuke bez pritiska</li>
              <li><span class="tick"></span> Savremena oprema i sterilizacija po standardima</li>
              <li><span class="tick"></span> Timski pristup kada je potreban širi spektar usluga</li>
            </ul>
          </div>

          <div class="why-right">
            <div class="photo-card">
              <div class="photo-pattern"></div>
              <div class="photo-text">
                <h3>Prijatan ambijent</h3>
                <p>Poseban fokus na pacijente koji imaju nelagodu ili strah — miran pristup i tempo koji vama odgovara.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section faq">
      <div class="container">
        <div class="section-head">
          <h2>Česta pitanja</h2>
          <p>Kratki odgovori na ono što pacijenti najčešće pitaju.</p>
        </div>

        <div class="grid cards-3">
          <div class="card">
            <h3>Da li dobijam plan terapije?</h3>
            <p>Da — nakon pregleda dobijate jasnu preporuku i naredne korake.</p>
          </div>
          <div class="card">
            <h3>Da li su zahvati bezbolni?</h3>
            <p>Koristimo adekvatnu anesteziju i radimo uz maksimalan komfor.</p>
          </div>
          <div class="card">
            <h3>Koliko traje oporavak?</h3>
            <p>Zavisi od zahvata — dobijate precizne smernice nakon intervencije.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section cta">
      <div class="container">
        <div class="cta-box">
          <div>
            <h2>Spremni za sledeći korak?</h2>
            <p>Ulogujte se i nastavite dalje ka zakazivanju termina.</p>
          </div>
          <a class="btn btn-cta" href="{{ route('login') }}">Uloguj se</a>
        </div>
      </div>
    </section>

  </main>

  <footer class="footerbar">
    <div class="container footer-inner">
      <div class="footer-col">
        <div class="footer-brand">
          <span class="footer-mark" aria-hidden="true">🦷</span>
          <span class="footer-name">ElitDent</span>
        </div>
        <div class="footer-text">Savremena stomatološka nega i komfor pacijenta.</div>
      </div>

      <div class="footer-col">
        <div class="footer-title">Navigacija</div>
        <a class="footer-link" href="{{ route('login') }}">Uloguj se</a>
      </div>

      <div class="footer-col">
        <div class="footer-title">Usluge</div>
        <div class="footer-text">Preventiva • Estetika • Protetika</div>
      </div>

      <div class="footer-col">
        <div class="footer-title">Kvalitet</div>
        <div class="footer-text">Standardi • Sterilizacija • Transparentnost</div>
      </div>
    </div>

    <div class="footer-bottom">
      © {{ date('Y') }} ElitDent. Sva prava zadržana.
    </div>
  </footer>

</body>
</html>
