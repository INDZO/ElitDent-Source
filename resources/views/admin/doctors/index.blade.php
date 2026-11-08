@extends('layouts.admin')

@section('title','Doktori')
@section('pageTitle','Doktori')

@section('content')
@php($bag = $errors->getBag('doctorCreate'))

  <div class="row">
    <div>
      <h1 class="h1">Doktori</h1>
      <p class="muted">Pregled i upravljanje doktorima.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openModal()">+ Dodaj doktora</button>
  </div>

  <div class="search-row">
    <input id="docSearch" class="search-input" placeholder="Pretraži doktore (ime/email/specijalizacija)..." autocomplete="off">
  </div>

  <div class="cards">
    @foreach($doctors as $d)
      @php($q = strtolower($d->Ime.' '.$d->Prezime.' '.$d->Email.' '.$d->Specijalizacija))
      <a class="doctor-card doc-card" data-q="{{ $q }}" href="{{ route('admin.doctors.show', $d->Email) }}">
        <div class="doc-name">{{ $d->Ime }} {{ $d->Prezime }}</div>
        <div class="doc-sub">{{ $d->Specijalizacija }}</div>
        <div class="doc-meta">{{ $d->Email }}</div>
      </a>
    @endforeach
    @if(count($doctors)===0)
      <div class="empty">Nema doktora. Dodaj prvog doktora.</div>
    @endif
  </div>

  <!-- Modal -->
  <div class="modal" id="modal">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title">Dodaj doktora</div>
        <button class="icon-btn" type="button" onclick="closeModal()">✕</button>
      </div>

      @if(session('err') && (session('open_modal') === 'doctorCreate'))
        <div class="flash err" id="doctorFlash" style="margin-bottom:10px;">{{ session('err') }}</div>
      @endif

      <form method="POST" action="{{ route('admin.doctors.store') }}" class="form" id="doctorCreateForm">
        @csrf

        <div class="grid2">
          <div>
            <label>Ime</label>
            <input name="Ime" value="{{ old('Ime') }}" class="{{ $bag->has('Ime') ? 'is-invalid' : '' }}" required>
            @if($bag->has('Ime')) <div class="field-error">{{ $bag->first('Ime') }}</div> @endif
          </div>
          <div>
            <label>Prezime</label>
            <input name="Prezime" value="{{ old('Prezime') }}" class="{{ $bag->has('Prezime') ? 'is-invalid' : '' }}" required>
            @if($bag->has('Prezime')) <div class="field-error">{{ $bag->first('Prezime') }}</div> @endif
          </div>
        </div>

        <label>Email</label>
        <input name="Email" type="email" value="{{ old('Email') }}" class="{{ $bag->has('Email') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Email')) <div class="field-error">{{ $bag->first('Email') }}</div> @endif

        <label>Specijalizacija</label>
        <input name="Specijalizacija" value="{{ old('Specijalizacija') }}" class="{{ $bag->has('Specijalizacija') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Specijalizacija')) <div class="field-error">{{ $bag->first('Specijalizacija') }}</div> @endif

        <label>Telefon</label>
        <input name="Telefon" value="{{ old('Telefon') }}" class="{{ $bag->has('Telefon') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Telefon')) <div class="field-error">{{ $bag->first('Telefon') }}</div> @endif

        <label>Početna lozinka</label>
        <input name="Lozinka" type="password" value="{{ old('Lozinka') }}" class="{{ $bag->has('Lozinka') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Lozinka')) <div class="field-error">{{ $bag->first('Lozinka') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj</button>
        <button class="btn btn-ghost" type="button" onclick="closeModal()">Odustani</button>
      </form>
    </div>
  </div>

<script>
function openModal(){
  document.getElementById('modal').classList.add('open');
  setTimeout(() => {
    const firstInvalid = document.querySelector('#modal .is-invalid');
    if(firstInvalid) firstInvalid.focus();
    else document.querySelector('#doctorCreateForm input')?.focus();
  }, 50);
}
function clearModal(){
  const form = document.getElementById('doctorCreateForm');
  if(!form) return;

  form.reset();
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.field-error').forEach(el => el.remove());
  document.getElementById('doctorFlash')?.remove();
}
function closeModal(){
  document.getElementById('modal').classList.remove('open');
  clearModal();
}

(function(){
  const input = document.getElementById('docSearch');
  const cards = Array.from(document.querySelectorAll('.doc-card'));
  input?.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    cards.forEach(c => c.style.display = (!q || c.dataset.q.includes(q)) ? '' : 'none');
  });
})();

@if($bag->any() || (session('open_modal') === 'doctorCreate'))
openModal();
@endif
</script>
@endsection
