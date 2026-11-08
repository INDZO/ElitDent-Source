@extends('layouts.patient')
  
@section('title','Profil')
@section('pageTitle','Profil')

@section('content')
@php
  $bProfile = $errors->getBag('profile');
  $bHealth  = $errors->getBag('health');
  $openEdit = $bProfile->any() || $bHealth->any() || old('_edit') === '1';
@endphp

  <div class="row">
    <div>
      <h1 class="h1">Moj profil</h1>
      <p class="muted">Pregled podataka i zdravstvenog upitnika.</p>
    </div>
    <div class="actions-row">
      <button class="btn btn-primary" type="button" id="btnEdit">Izmeni</button>
      <button class="btn btn-ghost" type="button" id="btnCancel" style="display:none;">Otkaži</button>
    </div>
  </div>

  <div class="stack">

    {{-- VIEW MODE --}}
    <div class="panel p-view" id="viewBlock">
      <h3>Osnovni podaci</h3>

      <div class="kv-grid">
        <div class="kv"><div class="k">Email</div><div class="v">{{ $u->Email }}</div></div>
        <div class="kv"><div class="k">Datum rođenja</div><div class="v">{{ $patient->DatumRodjenja ?? '—' }}</div></div>
        <div class="kv"><div class="k">Ime</div><div class="v">{{ $u->Ime }}</div></div>
        <div class="kv"><div class="k">Prezime</div><div class="v">{{ $u->Prezime }}</div></div>
        <div class="kv"><div class="k">Telefon</div><div class="v">{{ $patient->Telefon ?? '—' }}</div></div>
        <div class="kv"><div class="k">Adresa</div><div class="v">{{ $patient->Adresa ?? '—' }}</div></div>
      </div>

      <hr class="hr">

      <h3>Zdravstveni podaci</h3>
      <div class="kv-grid">
        <div class="kv"><div class="k">Alergije</div><div class="v">{{ $health->Alergije ?: '—' }}</div></div>
        <div class="kv"><div class="k">Hronične bolesti</div><div class="v">{{ $health->HronicneBolesti ?: '—' }}</div></div>
        <div class="kv"><div class="k">Terapije</div><div class="v">{{ $health->Terapije ?: '—' }}</div></div>
        <div class="kv"><div class="k">Napomena</div><div class="v">{{ $health->Napomena ?: '—' }}</div></div>
      </div>
      <p class="muted" style="margin-top:10px;">Poslednje ažuriranje: {{ $health->DatumAzuriranja ?? '—' }}</p>
    </div>

    {{-- EDIT MODE --}}
    <div class="panel p-edit {{ $openEdit ? 'is-open' : '' }}" id="editBlock">
      <h3>Izmena profila</h3>

      <form method="POST" action="{{ route('patient.profile.update') }}" class="form" style="margin-top:10px;">
        @csrf
        <input type="hidden" name="_edit" value="1">

        <label>Ime</label>
        <input name="Ime" value="{{ old('Ime', $u->Ime) }}" class="{{ $bProfile->has('Ime') ? 'is-invalid' : '' }}" required>
        @if($bProfile->has('Ime')) <div class="field-error">{{ $bProfile->first('Ime') }}</div> @endif

        <label>Prezime</label>
        <input name="Prezime" value="{{ old('Prezime', $u->Prezime) }}" class="{{ $bProfile->has('Prezime') ? 'is-invalid' : '' }}" required>
        @if($bProfile->has('Prezime')) <div class="field-error">{{ $bProfile->first('Prezime') }}</div> @endif

        <label>Telefon</label>
        <input name="Telefon" value="{{ old('Telefon', $patient->Telefon ?? '') }}" class="{{ $bProfile->has('Telefon') ? 'is-invalid' : '' }}" required>
        @if($bProfile->has('Telefon')) <div class="field-error">{{ $bProfile->first('Telefon') }}</div> @endif

        <label>Adresa</label>
        <input name="Adresa" value="{{ old('Adresa', $patient->Adresa ?? '') }}" class="{{ $bProfile->has('Adresa') ? 'is-invalid' : '' }}" required>
        @if($bProfile->has('Adresa')) <div class="field-error">{{ $bProfile->first('Adresa') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj profil</button>
      </form>

      <hr class="hr">

      <h3>Izmena zdravstvenih podataka</h3>
      <form method="POST" action="{{ route('patient.health.update') }}" class="form" style="margin-top:10px;">
        @csrf
        <input type="hidden" name="_edit" value="1">

        <label>Alergije</label>
        <textarea name="Alergije" class="{{ $bHealth->has('Alergije') ? 'is-invalid' : '' }}">{{ old('Alergije', $health->Alergije ?? '') }}</textarea>
        @if($bHealth->has('Alergije')) <div class="field-error">{{ $bHealth->first('Alergije') }}</div> @endif

        <label>Hronične bolesti</label>
        <textarea name="HronicneBolesti" class="{{ $bHealth->has('HronicneBolesti') ? 'is-invalid' : '' }}">{{ old('HronicneBolesti', $health->HronicneBolesti ?? '') }}</textarea>
        @if($bHealth->has('HronicneBolesti')) <div class="field-error">{{ $bHealth->first('HronicneBolesti') }}</div> @endif

        <label>Terapije (kratko)</label>
        <input name="Terapije" value="{{ old('Terapije', $health->Terapije ?? '') }}" class="{{ $bHealth->has('Terapije') ? 'is-invalid' : '' }}">
        @if($bHealth->has('Terapije')) <div class="field-error">{{ $bHealth->first('Terapije') }}</div> @endif

        <label>Napomena (kratko)</label>
        <input name="Napomena" value="{{ old('Napomena', $health->Napomena ?? '') }}" class="{{ $bHealth->has('Napomena') ? 'is-invalid' : '' }}">
        @if($bHealth->has('Napomena')) <div class="field-error">{{ $bHealth->first('Napomena') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj zdravstvene podatke</button>
      </form>
    </div>

  </div>

<script>
(function(){
  const view = document.getElementById('viewBlock');
  const edit = document.getElementById('editBlock');
  const btnEdit = document.getElementById('btnEdit');
  const btnCancel = document.getElementById('btnCancel');

  function openEdit(){
    view.classList.add('is-hidden');
    edit.classList.add('is-open');
    btnCancel.style.display = '';
    btnEdit.style.display = 'none';
  }
  function closeEdit(){
    view.classList.remove('is-hidden');
    edit.classList.remove('is-open');
    btnCancel.style.display = 'none';
    btnEdit.style.display = '';
  }

  btnEdit?.addEventListener('click', openEdit);
  btnCancel?.addEventListener('click', closeEdit);

  @if($openEdit)
    openEdit();
  @endif
})();
</script>
@endsection

