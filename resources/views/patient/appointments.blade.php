@extends('layouts.patient')

@section('title','Termini')
@section('pageTitle','Termini')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/patient-appointments.css') }}">
@endpush

@section('content')
@if(request()->boolean('created'))
  <div class="flash ok">Termin je uspešno zakazan i dodat u listu zakazanih termina.</div>
@endif

@php
  $cZak = (int)($counts['Zakazan'] ?? 0);
  $cRea = (int)($counts['Realizovan'] ?? 0);
  $cOtk = (int)($counts['Otkazan'] ?? 0);
@endphp

<div class="row">
  <div>
    <h1 class="h1">Moji termini</h1>
    <p class="muted">Pregled zakazanih, realizovanih i otkazanih termina.</p>
  </div>
</div>

<div class="ap-tabs">
  <a class="ap-tab {{ $status==='Zakazan' ? 'active' : '' }}" href="{{ route('patient.appointments', ['status'=>'Zakazan']) }}">
    Zakazani <span class="ap-count">{{ $cZak }}</span>
  </a>
  <a class="ap-tab {{ $status==='Realizovan' ? 'active' : '' }}" href="{{ route('patient.appointments', ['status'=>'Realizovan']) }}">
    Završeni <span class="ap-count">{{ $cRea }}</span>
  </a>
  <a class="ap-tab {{ $status==='Otkazan' ? 'active' : '' }}" href="{{ route('patient.appointments', ['status'=>'Otkazan']) }}">
    Otkazani <span class="ap-count">{{ $cOtk }}</span>
  </a>
</div>

<div class="panel">
  @if(count($items) === 0)
    <div class="muted">Nema termina u ovoj kategoriji.</div>
  @else
    <div class="ap-list">
      @foreach($items as $t)
        @php
          $vodja = trim(($t->VodjaIme ?? '').' '.($t->VodjaPrezime ?? ''));
          $time = substr((string)$t->VremePocetka,0,5).'–'.substr((string)$t->VremeZavrsetka,0,5);
        @endphp

        <div class="ap-card">
          <div class="ap-main">
            <div class="ap-title">{{ $t->NazivUsluge }}</div>
            <div class="ap-meta">
              <span class="ap-pill">{{ $t->Datum }}</span>
              <span class="ap-pill">{{ $time }}</span>
              <span class="ap-pill">{{ $t->TimNaziv }}</span>
              <span class="ap-pill">Vođa: {{ $vodja }}</span>
            </div>

            @if($t->Status === 'Otkazan')
              <div class="ap-note">
                <strong>Otkazano:</strong> {{ $t->DatumOtkazivanja ?? '—' }}
                @if($t->Razlog) • {{ $t->Razlog }} @endif
              </div>
            @endif
          </div>

          <div class="ap-actions">
            <span class="ap-status {{ $t->Status==='Zakazan' ? 's-ok' : ($t->Status==='Realizovan' ? 's-done' : 's-cancel') }}">
              {{ $t->Status }}
            </span>

            @if($t->Status === 'Zakazan')
              <button
                type="button"
                class="btn btn-ghost js-cancel"
                data-id="{{ $t->TerminID }}"
                data-datum="{{ $t->Datum }}"
                data-vreme="{{ $time }}"
                data-usluga="{{ $t->NazivUsluge }}"
              >
                Otkaži
              </button>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- Modal --}}
<div class="ap-modal" id="cancelModal" style="display:none;">
  <div class="ap-modal-backdrop" id="cancelBackdrop"></div>
  <div class="ap-modal-box" role="dialog" aria-modal="true" aria-labelledby="cancelTitle">
    <div class="ap-modal-head">
      <div class="ap-modal-title" id="cancelTitle">Otkazivanje termina</div>
      <button class="ap-x" type="button" id="cancelClose">✕</button>
    </div>

    <div class="ap-modal-body">
      <div class="muted" id="cancelInfo"></div>

      <form method="POST" id="cancelForm" class="form" style="margin-top:12px;">
        @csrf
        <label>Razlog (obavezno)</label>
        <textarea
          name="Razlog"
          id="cancelReason"
          required
          minlength="3"
          maxlength="200"
          placeholder="Npr. ne mogu da dođem, pomerite za sledeću nedelju…"
        ></textarea>
        <button class="btn btn-primary" type="submit">Potvrdi otkazivanje</button>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(() => {
  const modal = document.getElementById('cancelModal');
  const info = document.getElementById('cancelInfo');
  const form = document.getElementById('cancelForm');
  const reason = document.getElementById('cancelReason');
  const backdrop = document.getElementById('cancelBackdrop');
  const closeBtn = document.getElementById('cancelClose');

  const baseUrl = @json(url('/pacijent/termini'));

  function openCancel({ id, datum, vreme, usluga }) {
    info.textContent = `${usluga} • ${datum} • ${vreme}`;
    form.action = `${baseUrl}/${id}/otkazi`;
    reason.value = '';
    modal.style.display = 'block';
    setTimeout(() => reason.focus(), 50);
  }

  function closeCancel() {
    modal.style.display = 'none';
  }

  document.querySelectorAll('.js-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      openCancel({
        id: btn.dataset.id,
        datum: btn.dataset.datum,
        vreme: btn.dataset.vreme,
        usluga: btn.dataset.usluga
      });
    });
  });

  backdrop.addEventListener('click', closeCancel);
  closeBtn.addEventListener('click', closeCancel);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCancel();
  });
})();
</script>
@endpush
@endsection
