@extends('layouts.admin')

@section('title','Timovi')
@section('pageTitle','Timovi')

@section('content')
  <div class="row">
    <div>
      <h1 class="h1">Timovi</h1>
      <p class="muted">Kreiranje timova i dodela doktora/usluga.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openTeamModal()">+ Dodaj tim</button>
  </div>

  <div class="search-row">
    <input id="teamSearch" class="search-input" placeholder="Pretraži timove (naziv/vođa)..." autocomplete="off">
  </div>

  <div class="cards">
    @foreach($teams as $t)
      <a class="doctor-card team-card"
         data-q="{{ strtolower($t->Naziv.' '.$t->Ime.' '.$t->Prezime.' '.$t->VodjaEmail) }}"
         href="{{ route('admin.teams.show', ['id'=>$t->TimID]) }}">
        <div class="doc-name">{{ $t->Naziv }}</div>
        <div class="doc-sub">Vođa: {{ $t->Ime }} {{ $t->Prezime }}</div>
        <div class="doc-meta">Članovi: {{ $memberCounts[$t->TimID] ?? 0 }} • Usluge: {{ $serviceCounts[$t->TimID] ?? 0 }}</div>
      </a>
    @endforeach

    @if(count($teams)===0)
      <div class="empty">Nema timova. Dodaj prvi tim.</div>
    @endif
  </div>

  <div class="modal" id="teamModal">
    <div class="modal-card modal-tall">
      <div class="modal-head">
        <div class="modal-title">Dodaj tim</div>
        <button class="icon-btn" type="button" onclick="closeTeamModal()">✕</button>
      </div>

      <div class="modal-body">
        @if(session('err') && (session('open_modal') === 'teamCreate'))
          <div class="flash err" id="teamFlash" style="margin-bottom:10px;">{{ session('err') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.teams.store') }}" class="form" id="teamCreateForm">
          @csrf

          <label>Vođa tima</label>
          <select name="VodjaEmail" id="leaderSelect"
                  class="{{ $errors->getBag('teamCreate')->has('VodjaEmail') ? 'is-invalid' : '' }}" required>
            <option value="">— izaberi —</option>
            @foreach($doctors as $d)
              <option value="{{ $d->Email }}" {{ old('VodjaEmail')===$d->Email ? 'selected' : '' }}>
                {{ $d->Ime }} {{ $d->Prezime }} — {{ $d->Specijalizacija }}
              </option>
            @endforeach
          </select>
          @if($errors->getBag('teamCreate')->has('VodjaEmail'))
            <div class="field-error">{{ $errors->getBag('teamCreate')->first('VodjaEmail') }}</div>
          @endif

          <label>Naziv tima</label>
          <input name="Naziv" value="{{ old('Naziv') }}"
                 class="{{ $errors->getBag('teamCreate')->has('Naziv') ? 'is-invalid' : '' }}" required>
          @if($errors->getBag('teamCreate')->has('Naziv'))
            <div class="field-error">{{ $errors->getBag('teamCreate')->first('Naziv') }}</div>
          @endif

          <label>Opis</label>
          <input name="Opis" value="{{ old('Opis') }}"
                 class="{{ $errors->getBag('teamCreate')->has('Opis') ? 'is-invalid' : '' }}" required>
          @if($errors->getBag('teamCreate')->has('Opis'))
            <div class="field-error">{{ $errors->getBag('teamCreate')->first('Opis') }}</div>
          @endif

          <div class="pickgrid">
            <div>
              <div class="pick-title">Članovi tima</div>
              <div class="pick-sub">Vođa se automatski dodaje. Ovde biraš ostale članove.</div>

              <input class="pick-search" id="docPickSearch" placeholder="Pretraži doktore..." autocomplete="off">

              <div class="picklist" id="docList">
                @foreach($doctors as $d)
                  <label class="chk doc-item"
                         data-email="{{ $d->Email }}"
                         data-q="{{ strtolower($d->Ime.' '.$d->Prezime.' '.$d->Email.' '.$d->Specijalizacija) }}">
                    <input type="checkbox" name="Doktori[]" value="{{ $d->Email }}"
                           {{ in_array($d->Email, (array)old('Doktori', [])) ? 'checked' : '' }}>
                    <span>{{ $d->Ime }} {{ $d->Prezime }}</span>
                    <em>{{ $d->Specijalizacija }}</em>
                  </label>
                @endforeach
              </div>
            </div>

            <div>
              <div class="pick-title">Usluge</div>
              <div class="pick-sub">Označi usluge koje tim nudi.</div>

              <input class="pick-search" id="svcPickSearch" placeholder="Pretraži usluge..." autocomplete="off">

              <div class="picklist" id="svcList">
                @foreach($services as $s)
                  <label class="chk svc-item" data-q="{{ strtolower($s->Naziv.' '.$s->Opis) }}">
                    <input type="checkbox" name="Usluge[]" value="{{ $s->Naziv }}"
                           {{ in_array($s->Naziv, (array)old('Usluge', [])) ? 'checked' : '' }}>
                    <span>{{ $s->Naziv }}</span>
                    <em>{{ $s->TrajanjeMinuta }} min</em>
                  </label>
                @endforeach
              </div>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">Sačuvaj</button>
          <button class="btn btn-ghost" type="button" onclick="closeTeamModal()">Odustani</button>
        </form>
      </div>
    </div>
  </div>

<script>
function openTeamModal(){
  document.getElementById('teamModal').classList.add('open');
  setTimeout(() => document.querySelector('#teamModal .is-invalid')?.focus(), 50);
}

function clearTeamModal(){
  const form = document.getElementById('teamCreateForm');
  if(!form) return;

  form.querySelectorAll('input, select').forEach(el => {
    el.classList.remove('is-invalid');
    if(el.tagName === 'SELECT') el.selectedIndex = 0;
    else if(el.type === 'checkbox' || el.type === 'radio') el.checked = false;
    else el.value = '';
  });

  form.querySelectorAll('.field-error').forEach(e => e.remove());
  document.getElementById('teamFlash')?.remove();

  document.querySelectorAll('#docList .doc-item').forEach(i => i.style.display = '');
}

function closeTeamModal(){
  document.getElementById('teamModal').classList.remove('open');
  clearTeamModal();
}

(function(){
  const input = document.getElementById('teamSearch');
  const cards = Array.from(document.querySelectorAll('.team-card'));
  input?.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    cards.forEach(c => c.style.display = (!q || c.dataset.q.includes(q)) ? '' : 'none');
  });

  const leader = document.getElementById('leaderSelect');
  function hideLeaderFromList(){
    const v = leader.value;
    document.querySelectorAll('#docList .doc-item').forEach(row => {
      const email = row.getAttribute('data-email');
      if(email === v){
        row.style.display = 'none';
        row.querySelector('input[type="checkbox"]').checked = false;
      } else {
        row.style.display = '';
      }
    });
  }
  leader?.addEventListener('change', hideLeaderFromList);
  hideLeaderFromList();

  const docPick = document.getElementById('docPickSearch');
  const docItems = Array.from(document.querySelectorAll('.doc-item'));
  docPick?.addEventListener('input', () => {
    const q = docPick.value.trim().toLowerCase();
    docItems.forEach(i => {
      if(i.style.display === 'none') return; 
      i.style.display = (!q || i.dataset.q.includes(q)) ? '' : 'none';
    });
    hideLeaderFromList();
  });

  const svcPick = document.getElementById('svcPickSearch');
  const svcItems = Array.from(document.querySelectorAll('.svc-item'));
  svcPick?.addEventListener('input', () => {
    const q = svcPick.value.trim().toLowerCase();
    svcItems.forEach(i => i.style.display = (!q || i.dataset.q.includes(q)) ? '' : 'none');
  });
})();

@if($errors->hasBag('teamCreate') && $errors->getBag('teamCreate')->any())
openTeamModal();
@endif
@if(session('open_modal') === 'teamCreate')
openTeamModal();
@endif
</script>
@endsection