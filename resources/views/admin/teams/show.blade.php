@extends('layouts.admin')

@section('title','Tim')
@section('pageTitle','Tim')

@section('content')
@php
  $bag = $errors->getBag('teamUpdate');
@endphp

  <div class="row">
    <div>
      <h1 class="h1">{{ $team->Naziv }}</h1>
      <p class="muted">Vođa: {{ $team->Ime }} {{ $team->Prezime }} ({{ $team->VodjaEmail }})</p>
    </div>
    <a class="btn btn-back" href="{{ route('admin.teams.index') }}">← Nazad</a>
  </div>

  @if($bag->any())
    <div class="flash err">Proveri polja — neka nisu ispravna.</div>
  @endif

  <div class="grid3">
    <div class="panel">
      <h3>Podaci o timu</h3>

      <form method="POST" action="{{ route('admin.teams.update', ['id'=>$team->TimID]) }}" class="form" id="teamEditForm">
        @csrf

        <label>Vođa tima</label>
        <select name="VodjaEmail" id="leaderSelect" class="select {{ $bag->has('VodjaEmail') ? 'is-invalid' : '' }}" required>
          @foreach($doctors as $d)
            <option value="{{ $d->Email }}" {{ old('VodjaEmail', $team->VodjaEmail)===$d->Email ? 'selected' : '' }}>
              {{ $d->Ime }} {{ $d->Prezime }} — {{ $d->Specijalizacija }}
            </option>
          @endforeach
        </select>
        @if($bag->has('VodjaEmail')) <div class="field-error">{{ $bag->first('VodjaEmail') }}</div> @endif

        <label>Naziv tima</label>
        <input name="Naziv" id="teamName" value="{{ old('Naziv', $team->Naziv) }}" class="{{ $bag->has('Naziv') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Naziv')) <div class="field-error">{{ $bag->first('Naziv') }}</div> @endif

        <label>Opis</label>
        <input name="Opis" id="teamDesc" value="{{ old('Opis', $team->Opis) }}" class="{{ $bag->has('Opis') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Opis')) <div class="field-error">{{ $bag->first('Opis') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj izmene</button>
      </form>

      <hr class="hr">

      <form method="POST" action="{{ route('admin.teams.destroy', ['id'=>$team->TimID]) }}"
            onsubmit="return confirm('Da li si siguran da želiš da obrišeš tim?')">
        @csrf
        <button class="btn btn-danger" type="submit">Obriši tim</button>
      </form>
    </div>

    <div class="panel">
      <h3>Članovi tima</h3>
      <p class="muted" style="margin-top:6px;">Vođa se ne prikazuje u listi (uvek je član).</p>

      <input class="pick-search" id="memberSearch" placeholder="Pretraži doktore..." autocomplete="off">

      <form method="POST" action="{{ route('admin.teams.update', ['id'=>$team->TimID]) }}" class="form" id="membersForm">
        @csrf
        <input type="hidden" name="MembersIntent" value="1">

        <input type="hidden" name="VodjaEmail" value="{{ old('VodjaEmail', $team->VodjaEmail) }}" id="leaderHidden1">
        <input type="hidden" name="Naziv" value="{{ old('Naziv', $team->Naziv) }}" id="nameHidden1">
        <input type="hidden" name="Opis" value="{{ old('Opis', $team->Opis) }}" id="descHidden1">

        <div class="picklist" id="docList">
          @foreach($doctors as $d)
            @php
              $checked = in_array($d->Email, old('Doktori', $selectedDoctors) ?? []);
              $dq = strtolower($d->Ime.' '.$d->Prezime.' '.$d->Email.' '.$d->Specijalizacija);
            @endphp
            <label class="chk member-item" data-email="{{ $d->Email }}" data-q="{{ $dq }}">
              <input type="checkbox" name="Doktori[]" value="{{ $d->Email }}" {{ $checked ? 'checked' : '' }}>
              <span>{{ $d->Ime }} {{ $d->Prezime }}</span>
              <em>{{ $d->Specijalizacija }}</em>
            </label>
          @endforeach
        </div>

        <button class="btn btn-primary" type="submit">Sačuvaj članove</button>
      </form>
    </div>

    <div class="panel">
      <h3>Usluge koje tim nudi</h3>

      <input class="pick-search" id="serviceSearch" placeholder="Pretraži usluge..." autocomplete="off">

      <form method="POST" action="{{ route('admin.teams.update', ['id'=>$team->TimID]) }}" class="form" id="servicesForm">
        @csrf
        <input type="hidden" name="ServicesIntent" value="1">

        <input type="hidden" name="VodjaEmail" value="{{ old('VodjaEmail', $team->VodjaEmail) }}" id="leaderHidden2">
        <input type="hidden" name="Naziv" value="{{ old('Naziv', $team->Naziv) }}" id="nameHidden2">
        <input type="hidden" name="Opis" value="{{ old('Opis', $team->Opis) }}" id="descHidden2">

        <div class="picklist" id="svcList">
          @foreach($services as $s)
            @php
              $checked = in_array($s->Naziv, old('Usluge', $selectedServices) ?? []);
              $sq = strtolower($s->Naziv.' '.$s->Opis);
            @endphp
            <label class="chk service-item" data-q="{{ $sq }}">
              <input type="checkbox" name="Usluge[]" value="{{ $s->Naziv }}" {{ $checked ? 'checked' : '' }}>
              <span>{{ $s->Naziv }}</span>
              <em>{{ $s->TrajanjeMinuta }} min</em>
            </label>
          @endforeach
        </div>

        <button class="btn btn-primary" type="submit">Sačuvaj usluge</button>
      </form>
    </div>
  </div>

<script>
(function(){
  const leaderSelect = document.getElementById('leaderSelect');
  const memberSearch = document.getElementById('memberSearch');
  const memberItems = Array.from(document.querySelectorAll('.member-item'));

  let memberQuery = '';

  function syncHidden(){
    const v = leaderSelect.value;
    document.getElementById('leaderHidden1').value = v;
    document.getElementById('leaderHidden2').value = v;
  }

  function applyMemberFilter(){
    const leader = leaderSelect.value;
    memberItems.forEach(row => {
      const email = row.dataset.email;
      const input = row.querySelector('input[type="checkbox"]');

      if (email === leader){
        row.style.display = 'none';
        if (input) input.checked = false;
        return;
      }

      const q = (row.dataset.q || '');
      row.style.display = (!memberQuery || q.includes(memberQuery)) ? '' : 'none';
    });
  }

  leaderSelect?.addEventListener('change', () => {
    syncHidden();
    applyMemberFilter();
  });

  memberSearch?.addEventListener('input', () => {
    memberQuery = memberSearch.value.trim().toLowerCase();
    applyMemberFilter();
  });

  syncHidden();
  applyMemberFilter();

  const nameInput = document.getElementById('teamName');
  const descInput = document.getElementById('teamDesc');
  function syncMeta(){
    document.getElementById('nameHidden1').value = nameInput.value;
    document.getElementById('nameHidden2').value = nameInput.value;
    document.getElementById('descHidden1').value = descInput.value;
    document.getElementById('descHidden2').value = descInput.value;
  }
  nameInput?.addEventListener('input', syncMeta);
  descInput?.addEventListener('input', syncMeta);
  syncMeta();

  const sSearch = document.getElementById('serviceSearch');
  const sItems = Array.from(document.querySelectorAll('.service-item'));
  sSearch?.addEventListener('input', () => {
    const q = sSearch.value.trim().toLowerCase();
    sItems.forEach(i => i.style.display = (!q || (i.dataset.q || '').includes(q)) ? '' : 'none');
  });
})();
</script>
@endsection