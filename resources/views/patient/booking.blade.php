@extends('layouts.patient')

@section('title','Zakazivanje')
@section('pageTitle','Zakazivanje')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/patient-booking.css') }}">
@endpush

@section('content')
@php
  $servicesArr = $services->map(fn($s)=>[
    'Naziv'=>$s->Naziv,
    'Opis'=>$s->Opis,
    'TrajanjeMinuta'=>(int)$s->TrajanjeMinuta,
    'Cena'=>(string)$s->Cena,
  ])->values();
@endphp

<div class="row">
  <div>
    <h1 class="h1">Zakazivanje termina</h1>
    <p class="muted">Izaberi uslugu, tim/doktora i termin koji ti odgovara.</p>
  </div>
</div>

<form method="POST" action="{{ route('patient.booking.store') }}" class="panel form">
  @csrf

  <div class="bk-grid">
    <div>
      <label>Usluga</label>
      <select id="service" name="usluga" required>
        <option value="">— Izaberi uslugu —</option>
        @foreach($services as $s)
          <option value="{{ $s->Naziv }}">{{ $s->Naziv }}</option>
        @endforeach
      </select>
      <div class="bk-hint" id="serviceHint"></div>
    </div>

    <div>
      <label>Tim / Doktor</label>
      <select id="team" name="tim" required disabled>
        <option value="">— Prvo izaberi uslugu —</option>
      </select>
      <div class="bk-hint" id="teamHint"></div>
    </div>
  </div>

  <input type="hidden" name="datum" id="pickedDate">
  <input type="hidden" name="vreme" id="pickedTime">

  <div class="bk-stage" id="calendarStage" style="display:none;">
    <div class="bk-stage-head">
      <div class="bk-stage-title">Izaberi dan</div>
      <div class="bk-month">
        <button type="button" class="btn btn-ghost" id="prevMonth">←</button>
        <div class="bk-month-label" id="monthLabel"></div>
        <button type="button" class="btn btn-ghost" id="nextMonth">→</button>
      </div>
    </div>

    <div class="bk-weekdays">
      <div>Pon</div><div>Uto</div><div>Sre</div><div>Čet</div><div>Pet</div><div>Sub</div><div>Ned</div>
    </div>

    <div class="bk-calendar" id="calendar"></div>
    <div class="bk-small muted" id="daysInfo"></div>
  </div>

  <div class="bk-stage" id="slotsStage" style="display:none;">
    <div class="bk-stage-head">
      <div class="bk-stage-title">Slobodni termini</div>
      <div class="bk-small muted" id="slotsInfo"></div>
    </div>
    <div class="bk-slots" id="slots"></div>
  </div>

  <div class="bk-actions">
    <button class="btn btn-primary" type="submit" id="confirmBtn" disabled>Potvrdi zakazivanje</button>
  </div>
</form>

@push('scripts')
<script>
(() => {
  const services = @json($servicesArr);
  const teamsByService = @json($teamsByService);

  const serviceSel = document.getElementById('service');
  const teamSel = document.getElementById('team');

  const serviceHint = document.getElementById('serviceHint');
  const teamHint = document.getElementById('teamHint');

  const calendarStage = document.getElementById('calendarStage');
  const slotsStage = document.getElementById('slotsStage');

  const calendarEl = document.getElementById('calendar');
  const monthLabel = document.getElementById('monthLabel');

  const daysInfo = document.getElementById('daysInfo');
  const slotsEl = document.getElementById('slots');
  const slotsInfo = document.getElementById('slotsInfo');

  const pickedDate = document.getElementById('pickedDate');
  const pickedTime = document.getElementById('pickedTime');

  const confirmBtn = document.getElementById('confirmBtn');

  const prevMonth = document.getElementById('prevMonth');
  const nextMonth = document.getElementById('nextMonth');

  let availableDays = new Set();
  let monthCursor = new Date();
  monthCursor.setDate(1);

  function svcByName(name){
    return services.find(s => s.Naziv === name) || null;
  }

  function resetAfterService(){
    teamSel.innerHTML = `<option value="">— Izaberi tim —</option>`;
    teamSel.disabled = true;
    teamHint.textContent = '';
    calendarStage.style.display = 'none';
    slotsStage.style.display = 'none';
    availableDays = new Set();
    pickedDate.value = '';
    pickedTime.value = '';
    confirmBtn.disabled = true;
  }

  function resetAfterTeam(){
    calendarStage.style.display = 'none';
    slotsStage.style.display = 'none';
    availableDays = new Set();
    pickedDate.value = '';
    pickedTime.value = '';
    confirmBtn.disabled = true;
  }

  function fillTeams(serviceName){
    const teams = teamsByService[serviceName] || [];
    teamSel.innerHTML = `<option value="">— Izaberi tim —</option>`;
    for (const t of teams){
      const label = `${t.Naziv} — ${t.Vodja} (${t.Clanova} čl.)`;
      const opt = document.createElement('option');
      opt.value = String(t.TimID);
      opt.textContent = label;
      teamSel.appendChild(opt);
    }
    teamSel.disabled = teams.length === 0;
    teamHint.textContent = teams.length ? 'Izaberi ko realizuje uslugu.' : 'Nema timova koji nude ovu uslugu.';
  }

  async function loadDays(){
    const svc = serviceSel.value;
    const tim = teamSel.value;
    if (!svc || !tim) return;

    resetAfterTeam();
    daysInfo.textContent = 'Učitavam slobodne dane…';

    const url = new URL(@json(route('patient.booking.days')), window.location.origin);
    url.searchParams.set('usluga', svc);
    url.searchParams.set('tim', tim);

    const res = await fetch(url.toString(), { headers: { 'Accept':'application/json' }});
    const data = await res.json();

    availableDays = new Set((data.days || []));
    calendarStage.style.display = 'block';
    renderCalendar();

    daysInfo.textContent = availableDays.size
      ? `Pronađeno ${availableDays.size} dana sa slobodnim terminima (narednih 60 dana).`
      : 'Nema slobodnih termina u narednih 60 dana.';
  }

  function fmtMonth(d){
  const m = d.toLocaleString('sr-Latn-RS', { month:'long', year:'numeric' });
  return m.charAt(0).toUpperCase() + m.slice(1);
}

  function toISODate(y,m,day){
    const mm = String(m+1).padStart(2,'0');
    const dd = String(day).padStart(2,'0');
    return `${y}-${mm}-${dd}`;
  }

  function renderCalendar(){
    calendarEl.innerHTML = '';
    monthLabel.textContent = fmtMonth(monthCursor);

    const y = monthCursor.getFullYear();
    const m = monthCursor.getMonth();

    const first = new Date(y,m,1);
    const last = new Date(y,m+1,0);

    // Monday-based index: 0..6
    const firstDow = (first.getDay() + 6) % 7;

    // leading blanks
    for(let i=0;i<firstDow;i++){
      const cell = document.createElement('div');
      cell.className = 'bk-day bk-empty';
      calendarEl.appendChild(cell);
    }

    for(let day=1; day<=last.getDate(); day++){
      const iso = toISODate(y,m,day);
      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'bk-day';
      cell.textContent = String(day);

      if (availableDays.has(iso)){
        cell.classList.add('bk-available');
        cell.addEventListener('click', () => pickDay(iso, cell));
      } else {
        cell.classList.add('bk-disabled');
        cell.disabled = true;
      }

      // selected mark
      if (pickedDate.value === iso) cell.classList.add('bk-selected');

      calendarEl.appendChild(cell);
    }
  }

  async function pickDay(iso, cell){
    pickedDate.value = iso;
    pickedTime.value = '';
    confirmBtn.disabled = true;

    // update selected style
    [...calendarEl.querySelectorAll('.bk-day')].forEach(d => d.classList.remove('bk-selected'));
    cell.classList.add('bk-selected');

    slotsStage.style.display = 'block';
    slotsEl.innerHTML = '';
    slotsInfo.textContent = 'Učitavam slobodne termine…';

    const url = new URL(@json(route('patient.booking.slots')), window.location.origin);
    url.searchParams.set('usluga', serviceSel.value);
    url.searchParams.set('tim', teamSel.value);
    url.searchParams.set('datum', iso);

    const res = await fetch(url.toString(), { headers: { 'Accept':'application/json' }});
    const data = await res.json();
    const slots = data.slots || [];

    if (!slots.length){
      slotsInfo.textContent = 'Nema slobodnih termina tog dana.';
      return;
    }

    slotsInfo.textContent = `Izaberi vreme (${slots.length} dostupnih termina).`;

    for (const s of slots){
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'bk-slot';
      b.textContent = s;
      b.addEventListener('click', () => pickSlot(s, b));
      slotsEl.appendChild(b);
    }
  }

  function pickSlot(time, btn){
    pickedTime.value = time;
    confirmBtn.disabled = false;

    [...slotsEl.querySelectorAll('.bk-slot')].forEach(x => x.classList.remove('bk-slot-selected'));
    btn.classList.add('bk-slot-selected');
  }

  serviceSel.addEventListener('change', () => {
    resetAfterService();
    const svc = svcByName(serviceSel.value);
    if (!svc){ serviceHint.textContent = ''; return; }
    serviceHint.textContent = `${svc.TrajanjeMinuta} min • ${svc.Cena} RSD`;
    fillTeams(serviceSel.value);
  });

  teamSel.addEventListener('change', () => {
    if (!teamSel.value){ resetAfterTeam(); return; }
    loadDays();
  });

  prevMonth.addEventListener('click', () => {
    monthCursor.setMonth(monthCursor.getMonth()-1);
    renderCalendar();
  });
  nextMonth.addEventListener('click', () => {
    monthCursor.setMonth(monthCursor.getMonth()+1);
    renderCalendar();
  });
})();
</script>
@endpush
@endsection
