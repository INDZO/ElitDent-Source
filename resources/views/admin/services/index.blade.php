@extends('layouts.admin')

@section('title','Usluge')
@section('pageTitle','Usluge')

@section('content')
@php($bag = $errors->getBag('serviceCreate'))

  <div class="row">
    <div>
      <h1 class="h1">Usluge</h1>
      <p class="muted">Katalog usluga ordinacije koje doktori nude.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openSvcModal()">+ Dodaj uslugu</button>
  </div>

  <div class="search-row">
    <input id="svcSearch" class="search-input" placeholder="Pretraži usluge (naziv/opis)..." autocomplete="off">
  </div>

  <div class="cards">
    @foreach($services as $s)
      @php($cntTeams = (int)($teamCounts[$s->Naziv] ?? 0))
      <a class="doctor-card svc-card"
         data-q="{{ strtolower($s->Naziv.' '.$s->Opis) }}"
         href="{{ route('admin.services.show', ['naziv' => $s->Naziv]) }}">
        <div class="doc-name">{{ $s->Naziv }}</div>
        <div class="doc-sub">{{ $s->TrajanjeMinuta }} min • {{ number_format((float)$s->Cena, 2, '.', '') }} RSD</div>
        <div class="doc-meta">Timovi koji nude: {{ $cntTeams }}</div>
      </a>
    @endforeach

    @if(count($services)===0)
      <div class="empty">Nema usluga. Dodaj prvu uslugu.</div>
    @endif
  </div>

  <!-- Modal -->
  <div class="modal" id="svcModal">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title">Dodaj uslugu</div>
        <button class="icon-btn" type="button" onclick="closeSvcModal()">✕</button>
      </div>

      @if(session('err') && (session('open_modal') === 'serviceCreate'))
        <div class="flash err" id="svcFlash" style="margin-bottom:10px;">{{ session('err') }}</div>
      @endif

      <form method="POST" action="{{ route('admin.services.store') }}" class="form" id="svcForm">
        @csrf

        <label>Naziv</label>
        <input name="Naziv" value="{{ old('Naziv') }}" class="{{ $bag->has('Naziv') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Naziv')) <div class="field-error">{{ $bag->first('Naziv') }}</div> @endif

        <label>Opis</label>
        <input name="Opis" value="{{ old('Opis') }}" class="{{ $bag->has('Opis') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Opis')) <div class="field-error">{{ $bag->first('Opis') }}</div> @endif

        <div class="grid2">
          <div>
            <label>Trajanje (min)</label>
            <input name="TrajanjeMinuta" type="number" min="5" max="480" value="{{ old('TrajanjeMinuta', 30) }}" class="{{ $bag->has('TrajanjeMinuta') ? 'is-invalid' : '' }}" required>
            @if($bag->has('TrajanjeMinuta')) <div class="field-error">{{ $bag->first('TrajanjeMinuta') }}</div> @endif
          </div>
          <div>
            <label>Cena (RSD)</label>
            <input name="Cena" type="number" min="0" step="0.01" value="{{ old('Cena', 0) }}" class="{{ $bag->has('Cena') ? 'is-invalid' : '' }}" required>
            @if($bag->has('Cena')) <div class="field-error">{{ $bag->first('Cena') }}</div> @endif
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Sačuvaj</button>
        <button class="btn btn-ghost" type="button" onclick="closeSvcModal()">Odustani</button>
      </form>
    </div>
  </div>

<script>
function openSvcModal(){
  document.getElementById('svcModal').classList.add('open');
  setTimeout(() => document.querySelector('#svcModal .is-invalid')?.focus(), 50);
}
function clearSvcModal(){
  const form = document.getElementById('svcForm');
  if(!form) return;
  form.reset();
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.field-error').forEach(el => el.remove());
  document.getElementById('svcFlash')?.remove();
}
function closeSvcModal(){
  document.getElementById('svcModal').classList.remove('open');
  clearSvcModal();
}

(function(){
  const input = document.getElementById('svcSearch');
  const cards = Array.from(document.querySelectorAll('.svc-card'));
  input?.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    cards.forEach(c => c.style.display = (!q || c.dataset.q.includes(q)) ? '' : 'none');
  });
})();

@if($bag->any() || (session('open_modal') === 'serviceCreate'))
openSvcModal();
@endif
</script>
@endsection
