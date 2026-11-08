@extends('layouts.patient')

@section('title','Stomatološki karton')
@section('pageTitle','Stomatološki karton')

@section('content')
@php
  $pal = function (?string $stanje) {
    $s = mb_strtolower(trim((string)$stanje));

    $dot = 'var(--ok)';       $bd = 'rgba(34,197,94,.50)';
    if (str_contains($s, 'plomba') || str_contains($s, 'ispun')) {
      $dot = 'var(--warn)';   $bd = 'rgba(245,158,11,.55)';
    } elseif (str_contains($s, 'karijes')) {
      $dot = 'var(--danger)'; $bd = 'rgba(59,130,246,.60)';
    } elseif (str_contains($s, 'kruna') || str_contains($s, 'most')) {
      $dot = 'var(--info)';   $bd = 'rgba(168,85,247,.55)';
    } elseif (str_contains($s, 'nedost')) {
      $dot = 'var(--missing)';$bd = 'rgba(107,114,128,.50)';
    }
    return compact('dot','bd');
  };

  $renderTooth = function ($zub) use ($schemaMap, $pal) {
    $z = $schemaMap[$zub] ?? ['Stanje'=>'—', 'Napomena'=>null];
    $p = $pal($z['Stanje'] ?? '');
@endphp
    <div class="tooth" style="border-color: {{ $p['bd'] }};">
      <span class="tooth-dot" style="background: {{ $p['dot'] }};"></span>
      <div class="tooth-num">{{ $zub }}</div>

      <div class="tooth-tip">
        <div class="tip-title">Zub {{ $zub }}</div>
        <div class="tip-row"><span class="tip-label">Stanje:</span> {{ $z['Stanje'] ?? '—' }}</div>
        @if(!empty($z['Napomena']))
          <div class="tip-row" style="margin-top:4px;"><span class="tip-label">Napomena:</span> {{ $z['Napomena'] }}</div>
        @endif
      </div>
    </div>
@php
  };
@endphp

  <div class="row">
    <div>
      <h1 class="h1">Stomatološki karton</h1>
      <p class="muted">Dentalna šema, karton, intervencije i dokumenta (read-only).</p>
    </div>
    <div class="actions-row">
      <a class="btn btn-primary" href="{{ route('patient.profile') }}">Profil</a>
    </div>
  </div>

  <div class="dental-card">
    <div class="dental-head">
      <div class="dental-title">Dentalna šema</div>
      <div class="legend">
        <div class="legend-item"><span class="legend-dot ok"></span> Zdrav</div>
        <div class="legend-item"><span class="legend-dot warn"></span> Plomba</div>
        <div class="legend-item"><span class="legend-dot danger"></span> Karijes</div>
        <div class="legend-item"><span class="legend-dot info"></span> Kruna/Most</div>
        <div class="legend-item"><span class="legend-dot missing"></span> Nedostaje</div>
      </div>
    </div>

    <div class="jaw">
      <div class="jaw-title">Gornja vilica</div>
      <div class="jaw-row">
        <div class="jaw-half">
          @foreach(array_slice($upper, 0, 8) as $zub)
            @php($renderTooth($zub))
          @endforeach
        </div>
        <div class="jaw-divider"></div>
        <div class="jaw-half">
          @foreach(array_slice($upper, 8, 8) as $zub)
            @php($renderTooth($zub))
          @endforeach
        </div>
      </div>
    </div>

    <div class="jaw">
      <div class="jaw-title">Donja vilica</div>
      <div class="jaw-row">
        <div class="jaw-half">
          @foreach(array_slice($lower, 0, 8) as $zub)
            @php($renderTooth($zub))
          @endforeach
        </div>
        <div class="jaw-divider"></div>
        <div class="jaw-half">
          @foreach(array_slice($lower, 8, 8) as $zub)
            @php($renderTooth($zub))
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="grid2" style="margin-top:14px;">
    <div class="panel">
      <h3>Karton (read-only)</h3>
      <div class="form">
        <label>Plan terapije</label>
        <textarea disabled>{{ $card->PlanTerapije ?? '' }}</textarea>

        <label>Preporuke</label>
        <textarea disabled>{{ $card->Preporuke ?? '' }}</textarea>

        <label>Beleske</label>
        <textarea disabled>{{ $card->Beleske ?? '' }}</textarea>

        <label>Napomena</label>
        <textarea disabled>{{ $card->Napomena ?? '' }}</textarea>
      </div>
    </div>

    <div class="panel">
      <h3>Zdravstveni podaci</h3>
      <p class="muted" style="margin-top:6px;">Za izmenu koristi Profil.</p>

      <div class="kv-grid">
        <div class="kv"><div class="k">Alergije</div><div class="v">{{ $health->Alergije ?: '—' }}</div></div>
        <div class="kv"><div class="k">Hronične bolesti</div><div class="v">{{ $health->HronicneBolesti ?: '—' }}</div></div>
        <div class="kv"><div class="k">Terapije</div><div class="v">{{ $health->Terapije ?: '—' }}</div></div>
        <div class="kv"><div class="k">Datum ažuriranja</div><div class="v">{{ $health->DatumAzuriranja ?? '—' }}</div></div>
        <div class="kv"><div class="k">Napomena</div><div class="v">{{ $health->Napomena ?: '—' }}</div></div>
      </div>
    </div>
  </div>

  <div class="panel" style="margin-top:14px;">
    <h3>Istorija intervencija</h3>
    <p class="muted" style="margin-top:6px;">Poslednjih 25 zapisa.</p>

    @if(count($interventions) === 0)
      <div class="muted">Nema evidentiranih intervencija za ovog pacijenta.</div>
    @else
      <table class="table">
        <thead>
          <tr>
            <th>Datum</th>
            <th>Usluga</th>
            <th>Zub</th>
            <th>Opis</th>
            <th>Doktor</th>
          </tr>
        </thead>
        <tbody>
          @foreach($interventions as $i)
            <tr>
              <td>{{ $i->Datum }}</td>
              <td>{{ $i->NazivUsluge }}</td>
              <td>{{ $i->Zub ?? '—' }}</td>
              <td>
                <div style="font-weight:900;">{{ $i->Opis }}</div>
                @if($i->Napomena)<div class="muted" style="margin-top:2px;">{{ $i->Napomena }}</div>@endif
              </td>
              <td>{{ $i->DoktorIme }} {{ $i->DoktorPrezime }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  <div class="panel" style="margin-top:14px;">
    <h3>Dokumenti</h3>

    @if($errors->any())
      <div class="flash err" style="margin-top:10px;">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('patient.docs.store') }}" enctype="multipart/form-data" class="form">
      @csrf

      <div class="doc-form">
        <div>
          <label>Naziv (opciono)</label>
          <input name="Naziv" placeholder="npr. RTG snimak - februar">
        </div>
        <div>
          <label>Fajl (JPG/PNG/PDF)</label>
          <input type="file" name="file" required>
        </div>
      </div>

      <label>Napomena (opciono)</label>
      <input name="Napomena" placeholder="npr. Kontrolni snimak">

      <button class="btn btn-primary" type="submit">Dodaj dokument</button>
    </form>

    <hr class="hr">

    @if(count($documents) === 0)
      <div class="muted">Nema dodatih dokumenata.</div>
    @else
      <table class="table">
        <thead>
          <tr>
            <th>Naziv</th>
            <th>Tip</th>
            <th>Datum</th>
            <th>KB</th>
            <th>Akcije</th>
          </tr>
        </thead>
        <tbody>
          @foreach($documents as $d)
            <tr>
              <td>{{ $d->Naziv }}</td>
              <td>{{ $d->Tip }}</td>
              <td>{{ $d->DatumDodavanja }}</td>
              <td>{{ $d->Velicina ? number_format(((int)$d->Velicina)/1024, 1, '.', '') : '—' }}</td>
              <td class="doc-actions">
                <a class="btn btn-ghost" href="{{ route('patient.docs.view', ['putanja' => $d->Putanja]) }}" target="_blank">Pregled</a>
                <a class="btn btn-ghost" href="{{ route('patient.docs.download', ['putanja' => $d->Putanja]) }}">Preuzmi</a>

                <form method="POST" action="{{ route('patient.docs.delete') }}" onsubmit="return confirm('Obrisati dokument?')">
                  @csrf
                  <input type="hidden" name="putanja" value="{{ $d->Putanja }}">
                  <button class="btn btn-ghost" type="submit">Obriši</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection