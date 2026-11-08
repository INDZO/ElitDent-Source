@extends('layouts.doctor')

@section('title','Profil doktora')
@section('pageTitle','Profil')

@section('content')
  <div class="row">
    <div>
      <h1 class="h1">Profil doktora</h1>
      <p class="muted">Podaci su read-only. Za izmene se obrati administratoru. Neradni dani se mogu menjati ovde.</p>
    </div>
  </div>

  <div class="grid3">
    <div class="panel">
      <h3>Osnovni podaci</h3>
      <div class="form">
        <label>Ime i prezime</label>
        <input value="{{ $doctor->Ime }} {{ $doctor->Prezime }}" disabled>

        <label>Email</label>
        <input value="{{ $doctor->Email }}" disabled>

        <label>Specijalizacija</label>
        <input value="{{ $doctor->Specijalizacija }}" disabled>

        <label>Telefon</label>
        <input value="{{ $doctor->Telefon }}" disabled>
      </div>
    </div>

    <div class="panel">
      <h3>Raspoloživost (read-only)</h3>
      @if(!$availability)
        <p class="muted">Raspoloživost nije podešena.</p>
      @else
        <div class="form">
          <label>Radni dani</label>
          <input value="{{ $availability->RadniDan }}" disabled>

          <div class="grid2">
            <div>
              <label>Vreme od</label>
              <input value="{{ substr((string)$availability->VremeOd,0,5) }}" disabled>
            </div>
            <div>
              <label>Vreme do</label>
              <input value="{{ substr((string)$availability->VremeDo,0,5) }}" disabled>
            </div>
          </div>
        </div>
      @endif
      <p class="muted" style="margin-top:10px;">Za promenu radnog vremena obrati se administratoru.</p>
    </div>

    <div class="panel">
      <h3>Neradni dani</h3>

      <form method="POST" action="{{ route('doctor.offdays.add') }}" class="form">
        @csrf
        <label>Datum</label>
        <input type="date" name="Datum" value="{{ old('Datum') }}" required>

        <label>Razlog (opciono)</label>
        <input name="Razlog" value="{{ old('Razlog') }}" maxlength="120" placeholder="npr. godišnji odmor">

        <button class="btn btn-primary" type="submit">Dodaj neradni dan</button>

        @php($bag = $errors->getBag('offday'))
        @if($bag->any())
          <div class="flash err" style="margin-top:10px;">
            {{ $bag->first() }}
          </div>
        @endif

        <p class="muted" style="margin-top:10px;">
          Napomena: dodavanje neradnog dana automatski otkazuje sve zakazane termine tog dana.
        </p>
      </form>

      <hr class="hr">

      <h4 style="margin:0;">Lista</h4>

      {{-- ✅ fiksna visina + scroll --}}
      <div class="list" style="max-height:280px; overflow:auto; margin-top:10px; padding-right:4px;">
        @forelse($offDays as $d)
          <div class="list-row">
            <div>
              <div style="font-weight:900;">{{ $d->Datum }}</div>
              @if($d->Razlog)<div class="muted" style="margin-top:4px;">{{ $d->Razlog }}</div>@endif
            </div>
            <form method="POST" action="{{ route('doctor.offdays.delete', $d->Datum) }}" onsubmit="return confirm('Obrisati neradni dan?')">
              @csrf
              <button class="btn btn-ghost" type="submit">Obriši</button>
            </form>
          </div>
        @empty
          <div class="muted">Nema neradnih dana.</div>
        @endforelse
      </div>
    </div>
  </div>
@endsection