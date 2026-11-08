@extends('layouts.admin')

@section('title','Admin dashboard')
@section('pageTitle','Dashboard')

@section('content')
  <h1 class="h1">Statistika ordinacije</h1>
  <p class="muted">Brzi pregled stanja sistema.</p>

  <div class="stat-grid">
    <div class="stat">
      <div class="stat-label">Ukupno termina</div>
      <div class="stat-value">{{ $totalTerms }}</div>
      <div class="stat-sub">Danas: {{ $todayTerms }}</div>
    </div>

    <div class="stat">
      <div class="stat-label">Doktori</div>
      <div class="stat-value">{{ $totalDoctors }}</div>
      <div class="stat-sub">U sistemu</div>
    </div>

    <div class="stat">
      <div class="stat-label">Pacijenti</div>
      <div class="stat-value">{{ $totalPatients }}</div>
      <div class="stat-sub">Registrovani</div>
    </div>

    <div class="stat">
      <div class="stat-label">Timovi</div>
      <div class="stat-value">{{ $totalTeams - $totalDoctors }}</div>
      <div class="stat-sub">Aktivni</div>
    </div>

    <div class="stat">
      <div class="stat-label">Usluge</div>
      <div class="stat-value">{{ $totalServices }}</div>
      <div class="stat-sub">U katalogu</div>
    </div>
  </div>

  <div class="charts">
    <div class="chart">
      <h3>Status termina</h3>
      <p class="small">Distribucija po statusu (Zakazan / Realizovan / Otkazan).</p>

      @if(count($statusBars) === 0)
        <div class="empty">Još uvek nema termina.</div>
      @else
        @foreach($statusBars as $s)
          <div class="bar-row">
            <div class="bar-name">{{ $s['status'] }}</div>
            <div class="bar-track"><div class="bar-fill" style="width: {{ $s['pct'] }}%"></div></div>
            <div class="bar-val">{{ $s['cnt'] }}</div>
          </div>
        @endforeach
      @endif
    </div>

    <div class="chart">
      <h3>Termini (7 dana)</h3>
      <p class="small">Broj termina po danu u poslednjih 7 dana.</p>

      <div class="spark">
        @foreach($spark as $x)
          <div class="spark-col">
            <div class="spark-num">{{ $x['cnt'] }}</div>
            <div class="spark-bar" style="height: {{ $x['h'] }}px"></div>
            <div class="spark-label">{{ $x['label'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endsection