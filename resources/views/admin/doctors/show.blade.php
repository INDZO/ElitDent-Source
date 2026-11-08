@extends('layouts.admin')

@section('title','Doktor')
@section('pageTitle','Doktor')

@section('content')
@php
  $bUpdate = $errors->getBag('doctorUpdate');
  $bAvail  = $errors->getBag('doctorAvail');
  $bOff    = $errors->getBag('doctorOffday');
  $bSvc    = $errors->getBag('doctorServices');
@endphp

  <div class="row">
    <div>
      <h1 class="h1">{{ $doctor->Ime }} {{ $doctor->Prezime }}</h1>
      <p class="muted">{{ $doctor->Email }}</p>
    </div>
    <a class="btn btn-back" href="{{ route('admin.doctors.index') }}">← Nazad</a>
  </div>

  <div class="grid3">
    <div class="panel">
      <h3>Profil doktora</h3>

      <form method="POST" action="{{ route('admin.doctors.update', $doctor->Email) }}" class="form">
        @csrf

        <div class="grid2">
          <div>
            <label>Ime</label>
            <input name="Ime" value="{{ old('Ime', $doctor->Ime) }}" class="{{ $bUpdate->has('Ime') ? 'is-invalid' : '' }}" required>
            @if($bUpdate->has('Ime')) <div class="field-error">{{ $bUpdate->first('Ime') }}</div> @endif
          </div>
          <div>
            <label>Prezime</label>
            <input name="Prezime" value="{{ old('Prezime', $doctor->Prezime) }}" class="{{ $bUpdate->has('Prezime') ? 'is-invalid' : '' }}" required>
            @if($bUpdate->has('Prezime')) <div class="field-error">{{ $bUpdate->first('Prezime') }}</div> @endif
          </div>
        </div>

        <label>Specijalizacija</label>
        <input name="Specijalizacija" value="{{ old('Specijalizacija', $doctor->Specijalizacija) }}" class="{{ $bUpdate->has('Specijalizacija') ? 'is-invalid' : '' }}" required>
        @if($bUpdate->has('Specijalizacija')) <div class="field-error">{{ $bUpdate->first('Specijalizacija') }}</div> @endif

        <label>Telefon</label>
        <input name="Telefon" value="{{ old('Telefon', $doctor->Telefon) }}" class="{{ $bUpdate->has('Telefon') ? 'is-invalid' : '' }}" required>
        @if($bUpdate->has('Telefon')) <div class="field-error">{{ $bUpdate->first('Telefon') }}</div> @endif

        <label>Nova lozinka (opciono)</label>
        <input name="NovaLozinka" type="password" class="{{ $bUpdate->has('NovaLozinka') ? 'is-invalid' : '' }}">
        @if($bUpdate->has('NovaLozinka')) <div class="field-error">{{ $bUpdate->first('NovaLozinka') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj izmene</button>
      </form>

      <hr class="hr">

      <form method="POST" action="{{ route('admin.doctors.destroy', $doctor->Email) }}"
            onsubmit="return confirm('Da li si siguran da želiš da obrišeš doktora?')">
        @csrf
        <button class="btn btn-danger" type="submit">Obriši doktora</button>
      </form>
      
    </div>

    <div class="panel">
      <h3>Raspoloživost</h3>
      <form method="POST" action="{{ route('admin.doctors.availability', $doctor->Email) }}" class="form">
        @csrf

        <label>Radni dan</label>
        <input name="RadniDan" value="{{ old('RadniDan', $availability->RadniDan ?? '') }}" class="{{ $bAvail->has('RadniDan') ? 'is-invalid' : '' }}" required>
        @if($bAvail->has('RadniDan')) <div class="field-error">{{ $bAvail->first('RadniDan') }}</div> @endif

        <div class="grid2">
          <div>
            <label>Vreme od</label>
            <input name="VremeOd" type="time" value="{{ old('VremeOd', isset($availability->VremeOd) ? substr($availability->VremeOd,0,5) : '') }}"
                   class="{{ $bAvail->has('VremeOd') ? 'is-invalid' : '' }}" required>
            @if($bAvail->has('VremeOd')) <div class="field-error">{{ $bAvail->first('VremeOd') }}</div> @endif
          </div>
          <div>
            <label>Vreme do</label>
            <input name="VremeDo" type="time" value="{{ old('VremeDo', isset($availability->VremeDo) ? substr($availability->VremeDo,0,5) : '') }}"
                   class="{{ $bAvail->has('VremeDo') ? 'is-invalid' : '' }}" required>
            @if($bAvail->has('VremeDo')) <div class="field-error">{{ $bAvail->first('VremeDo') }}</div> @endif
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Sačuvaj</button>
      </form>

      <hr class="hr">
      <h3>Neradni dani</h3>

      @if($bOff->any())
        <div class="flash err">Proveri datum/razlog za neradni dan.</div>
      @endif

      <form method="POST" action="{{ route('admin.doctors.offdays.add', $doctor->Email) }}" class="form">
        @csrf
        <label>Datum</label>
        <input name="Datum" type="date" value="{{ old('Datum') }}" class="{{ $bOff->has('Datum') ? 'is-invalid' : '' }}" required>
        @if($bOff->has('Datum')) <div class="field-error">{{ $bOff->first('Datum') }}</div> @endif

        <label>Razlog (opciono)</label>
        <input name="Razlog" value="{{ old('Razlog') }}" class="{{ $bOff->has('Razlog') ? 'is-invalid' : '' }}" placeholder="npr. Godišnji odmor">
        @if($bOff->has('Razlog')) <div class="field-error">{{ $bOff->first('Razlog') }}</div> @endif

        <button class="btn btn-primary" type="submit">Dodaj</button>
      </form>

      <div class="list" style="max-height:280px; overflow:auto; padding-right:4px">
        @foreach($offDays as $od)
          <div class="list-row">
            <div>
              <div class="list-title">{{ $od->Datum }}</div>
              <div class="list-sub">{{ $od->Razlog ?? '—' }}</div>
            </div>
            <form method="POST" action="{{ route('admin.doctors.offdays.delete', ['email'=>$doctor->Email,'datum'=>$od->Datum]) }}">
              @csrf
              <button class="btn btn-ghost" type="submit">Obriši</button>
            </form>
          </div>
        @endforeach

        @if(count($offDays)===0)
          <div class="empty">Nema unetih neradnih dana.</div>
        @endif
      </div>

    </div>

    <div class="panel">
      <h3>Usluge koje doktor nudi</h3>
      @if($bSvc->any())
        <div class="flash err">Proveri selekciju usluga.</div>
      @endif

      <input class="pick-search" id="docSvcSearch" placeholder="Pretraži usluge..." autocomplete="off">

      <form method="POST" action="{{ route('admin.doctors.services', $doctor->Email) }}" class="form" id="doctorServicesForm">
        @csrf

        <div class="picklist" id="docSvcList">
          @foreach($allServices as $s)
            @php
              $checked = in_array($s->Naziv, old('Usluge', $doctorServiceNames) ?? []);
              $q = strtolower($s->Naziv.' '.$s->Opis);
            @endphp
            <label class="chk doc-svc-item" data-q="{{ $q }}">
              <input type="checkbox" name="Usluge[]" value="{{ $s->Naziv }}" {{ $checked ? 'checked' : '' }}>
              <span>{{ $s->Naziv }}</span>
              <em>{{ $s->TrajanjeMinuta }} min</em>
            </label>
          @endforeach
        </div>

        @if($bSvc->has('Usluge')) <div class="field-error">{{ $bSvc->first('Usluge') }}</div> @endif

        <button class="btn btn-primary" type="submit">Sačuvaj usluge</button>
      </form>
    </div>
  </div>

<script>
(function(){
  const s = document.getElementById('docSvcSearch');
  const items = Array.from(document.querySelectorAll('.doc-svc-item'));
  s?.addEventListener('input', () => {
    const q = s.value.trim().toLowerCase();
    items.forEach(i => i.style.display = (!q || i.dataset.q.includes(q)) ? '' : 'none');
  });
})();
</script>
@endsection