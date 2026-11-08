@extends('layouts.admin')

@section('title','Usluga')
@section('pageTitle','Usluga')

@section('content')
@php($bag = $errors->getBag('serviceUpdate'))

  <div class="row">
    <div>
      <h1 class="h1">{{ $service->Naziv }}</h1>
      <p class="muted">{{ $service->TrajanjeMinuta }} min • {{ number_format((float)$service->Cena, 2, '.', '') }} RSD</p>
    </div>
    <a class="btn btn-back" href="{{ route('admin.services.index') }}">← Nazad</a>
  </div>

  <div class="grid-service">
    <div class="panel">
      <h3>Izmena usluge</h3>
      <p class="muted" style="margin-top:6px;">Naziv je identifikator (PK) i ne menja se.</p>

      <form method="POST" action="{{ route('admin.services.update', ['naziv'=>$service->Naziv]) }}" class="form">
        @csrf

        <label>Naziv (ne menja se)</label>
        <input value="{{ $service->Naziv }}" disabled>

        <label>Opis</label>
        <input name="Opis" value="{{ old('Opis', $service->Opis) }}" class="{{ $bag->has('Opis') ? 'is-invalid' : '' }}" required>
        @if($bag->has('Opis')) <div class="field-error">{{ $bag->first('Opis') }}</div> @endif

        <div class="grid2">
          <div>
            <label>Trajanje (min)</label>
            <input name="TrajanjeMinuta" type="number" min="5" max="480"
                   value="{{ old('TrajanjeMinuta', $service->TrajanjeMinuta) }}"
                   class="{{ $bag->has('TrajanjeMinuta') ? 'is-invalid' : '' }}" required>
            @if($bag->has('TrajanjeMinuta')) <div class="field-error">{{ $bag->first('TrajanjeMinuta') }}</div> @endif
          </div>
          <div>
            <label>Cena (RSD)</label>
            <input name="Cena" type="number" min="0" step="0.01"
                   value="{{ old('Cena', number_format((float)$service->Cena, 2, '.', '')) }}"
                   class="{{ $bag->has('Cena') ? 'is-invalid' : '' }}" required>
            @if($bag->has('Cena')) <div class="field-error">{{ $bag->first('Cena') }}</div> @endif
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Sačuvaj</button>
      </form>

      <hr class="hr">

      <form method="POST" action="{{ route('admin.services.destroy', ['naziv'=>$service->Naziv]) }}"
            onsubmit="return confirm('Da li si siguran da želiš da obrišeš uslugu?')">
        @csrf
        <button class="btn btn-danger" type="submit">Obriši uslugu</button>
      </form>
    </div>

    <div class="panel">
      <h3>Timovi koji nude uslugu</h3>

      <div class="list">
        @foreach($teams as $t)
          <div class="list-row">
            <div>
              <div class="list-title">{{ $t->Naziv }}</div>
              <div class="list-sub">Vođa: {{ $t->VodjaEmail }}</div>
            </div>
          </div>
        @endforeach

        @if(count($teams)===0)
          <div class="empty">Trenutno nijedan tim ne nudi ovu uslugu.</div>
        @endif
      </div>
    </div>
  </div>
@endsection
