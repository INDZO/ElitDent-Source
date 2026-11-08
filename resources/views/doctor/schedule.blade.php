@extends('layouts.doctor')

@section('title','Raspored')
@section('pageTitle','Raspored')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor.css') }}">
@endpush

@section('content')
  <div class="row">
    <div>
      <h1 class="h1">Raspored</h1>
      <p class="muted">Kalendar sa brojem termina po danu. Klikni dan da vidiš satnicu i listu termina.</p>
    </div>
  </div>

  <div class="d-tabs" id="statusTabs">
    <button type="button" class="d-tab" data-status="Zakazan">Aktivni</button>
    <button type="button" class="d-tab" data-status="Realizovan">Završeni</button>
    <button type="button" class="d-tab" data-status="Otkazan">Otkazani</button>
    <button type="button" class="d-tab active" data-status="Svi">Svi</button>
  </div>

  <div class="panel">
    <div class="d-month">
      <button type="button" class="btn btn-ghost" id="prevMonth">←</button>
      <div class="d-month-label" id="monthLabel"></div>
      <button type="button" class="btn btn-ghost" id="nextMonth">→</button>
    </div>

    <div class="d-weekdays">
      <div>Pon</div><div>Uto</div><div>Sre</div><div>Čet</div><div>Pet</div><div>Sub</div><div>Ned</div>
    </div>

    <div class="d-cal" id="calendar"></div>
    <div class="muted" style="margin-top:10px;">Radno vreme (informativno): {{ $winFrom }}–{{ $winTo }}</div>
  </div>

  <div class="panel" style="margin-top:14px;">
    <div class="d-details-head">
      <div>
        <div class="d-details-title" id="dayTitle">Izaberi dan</div>
        <div class="d-muted" id="daySub">Klikni na kalendar.</div>
      </div>
    </div>

    <div id="vtWrap" style="display:none;">
      <div class="vt">
        <div class="vt-wrap">
          <div class="vt-times" id="vtTimes"></div>
          <div class="vt-area" id="vtArea"></div>
        </div>
      </div>
    </div>

    <div class="d-list" id="dayList" style="margin-top:12px;"></div>
  </div>

  <div class="ap-modal" id="cancelModal" style="display:none;">
    <div class="ap-modal-backdrop" id="cancelBackdrop"></div>
    <div class="ap-modal-box">
      <div class="ap-modal-head">
        <div class="ap-modal-title">Otkazivanje termina</div>
        <button class="ap-x" type="button" id="cancelClose">✕</button>
      </div>
      <div class="ap-modal-body">
        <div class="muted" id="cancelInfo"></div>

        <form method="POST" id="cancelForm" class="form" style="margin-top:12px;">
          @csrf
          <label>Razlog (obavezno)</label>
          <textarea name="Razlog" id="cancelReason" required minlength="3" maxlength="200"
            placeholder="Npr. ne mogu da radim tada, pomerite termin…"></textarea>
          <button class="btn btn-primary" type="submit">Potvrdi otkazivanje</button>
        </form>
      </div>
    </div>
  </div>

@push('scripts')
<script>
(() => {
  const calendarEl = document.getElementById('calendar');
  const monthLabel = document.getElementById('monthLabel');
  const prevMonth = document.getElementById('prevMonth');
  const nextMonth = document.getElementById('nextMonth');

  const tabs = document.getElementById('statusTabs');
  let status = 'Svi';

  let monthCursor = new Date();
  monthCursor.setDate(1);

  let counts = {};
  let selectedDate = '';

  const dayTitle = document.getElementById('dayTitle');
  const daySub = document.getElementById('daySub');
  const dayList = document.getElementById('dayList');

  const vtWrap = document.getElementById('vtWrap');
  const vtTimes = document.getElementById('vtTimes');
  const vtArea = document.getElementById('vtArea');

  const cancelModal = document.getElementById('cancelModal');
  const cancelBackdrop = document.getElementById('cancelBackdrop');
  const cancelClose = document.getElementById('cancelClose');
  const cancelInfo = document.getElementById('cancelInfo');
  const cancelForm = document.getElementById('cancelForm');
  const cancelReason = document.getElementById('cancelReason');
  const cancelBaseUrl = @json(url('/doctor/termini'));

  function openCancel(item){
    cancelInfo.textContent = `${item.service} • ${item.date} • ${item.start}-${item.end} • ${item.patient}`;
    cancelForm.action = `${cancelBaseUrl}/${item.id}/otkazi`;
    cancelReason.value = '';
    cancelModal.style.display = 'block';
    setTimeout(() => cancelReason.focus(), 40);
  }
  function closeCancel(){ cancelModal.style.display = 'none'; }
  cancelBackdrop.addEventListener('click', closeCancel);
  cancelClose.addEventListener('click', closeCancel);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeCancel(); });

  function fmtMonth(d){
    const m = d.toLocaleString("sr-Latn-RS",{month:"long", year:"numeric"});
    return m.charAt(0).toUpperCase() + m.slice(1);
  }
  function ymd(y,m,day){
    const mm = String(m+1).padStart(2,'0');
    const dd = String(day).padStart(2,'0');
    return `${y}-${mm}-${dd}`;
  }
  function ym(d){
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
  }

  async function loadMonth(){
    const url = new URL(@json(route('doctor.schedule.days')), window.location.origin);
    url.searchParams.set('month', ym(monthCursor));
    url.searchParams.set('status', status);

    const res = await fetch(url.toString(), { headers:{'Accept':'application/json'}});
    const data = await res.json();
    counts = data.counts || {};

    renderCalendar();
    if (selectedDate) loadDay(selectedDate);
  }

  function renderCalendar(){
    calendarEl.innerHTML = '';
    monthLabel.textContent = fmtMonth(monthCursor);

    const y = monthCursor.getFullYear();
    const m = monthCursor.getMonth();

    const first = new Date(y,m,1);
    const last = new Date(y,m+1,0);
    const firstDow = (first.getDay() + 6) % 7;

    for(let i=0;i<firstDow;i++){
      const div = document.createElement('div');
      div.className = 'd-day d-empty';
      calendarEl.appendChild(div);
    }

    for(let day=1; day<=last.getDate(); day++){
      const iso = ymd(y,m,day);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'd-day';
      btn.textContent = String(day);

      const c = counts[iso] || 0;
      if (c > 0){
        const b = document.createElement('span');
        b.className = 'd-badge';
        b.textContent = String(c);
        btn.appendChild(b);
      }

      if (iso === selectedDate) btn.classList.add('selected');

      btn.addEventListener('click', () => {
        selectedDate = iso;
        renderCalendar();
        loadDay(iso);
      });

      calendarEl.appendChild(btn);
    }
  }

  function hmToMin(hm){
    const [h,m] = hm.split(':').map(Number);
    return h*60 + m;
  }

  function renderVertical(windowFrom, windowTo, items){
    vtTimes.innerHTML = '';
    vtArea.innerHTML = '';
    vtWrap.style.display = 'block';

    const ws = hmToMin(windowFrom);
    const we = hmToMin(windowTo);
    const totalMin = Math.max(1, we - ws);

    const hourPx = 72;
    const minutePx = hourPx / 60;

    vtArea.style.height = `${totalMin * minutePx}px`;
    vtArea.style.setProperty('--vt-hour', `${hourPx}px`);

    for (let t = ws; t <= we; t += 60){
      const label = document.createElement('div');
      label.className = 'vt-time';
      label.textContent = `${String(Math.floor(t/60)).padStart(2,'0')}:00`;
      vtTimes.appendChild(label);

      const y = (t - ws) * minutePx;
      const line = document.createElement('div');
      line.className = 'vt-line';
      line.style.top = `${y}px`;
      vtArea.appendChild(line);
    }

    for (const it of items){
      const s = hmToMin(it.start);
      const e = hmToMin(it.end);

      const top = (s - ws) * minutePx;
      const height = Math.max(34, (e - s) * minutePx);

      const block = document.createElement('div');
      block.className = 'vt-block ' + (it.status === 'Zakazan' ? 'vt-zak' : (it.status === 'Realizovan' ? 'vt-rea' : 'vt-otk'));
      block.style.top = `${Math.max(0, top)}px`;
      block.style.height = `${height}px`;
      block.title = `${it.service} • ${it.start}-${it.end} • ${it.patient}`;

      
      block.innerHTML = `
        <div class="vt-row">
          <span class="vt-tr">${it.start}–${it.end}</span>
          <span class="vt-svc">${it.service}</span>
          <span class="vt-pat">${it.patient}</span>
          <span class="vt-st">${it.status}</span>
        </div>
      `;

      vtArea.appendChild(block);
    }
  }

  function renderList(items){
    dayList.innerHTML = '';
    if (!items.length){
      dayList.innerHTML = `<div class="muted">Nema termina za izabrani dan.</div>`;
      return;
    }

    for (const it of items){
      const card = document.createElement('div');
      card.className = 'd-card';

      const left = document.createElement('div');
      const phone = (it.patientPhone || '').trim();

      left.innerHTML = `
        <div class="d-title">${it.service}</div>
        <div class="d-meta">
          <span class="d-pill">${it.start}–${it.end}</span>
          <span class="d-pill">${it.team}</span>
          <span class="d-pill">${it.patient}</span>
          ${phone ? `<span class="d-pill">Tel: ${phone}</span>` : ``}
        </div>
        ${it.status === 'Otkazan' && it.reason ? `<div class="muted" style="margin-top:8px;"><b>Razlog:</b> ${it.reason}</div>` : ``}
      `;

      const right = document.createElement('div');
      right.className = 'd-actions';

      const st = document.createElement('span');
      st.className = 'd-pill';
      st.textContent = it.status;
      right.appendChild(st);

      if (it.status === 'Zakazan' && it.canCancel){
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-ghost';
        b.textContent = 'Otkaži';
        b.addEventListener('click', () => openCancel(it));
        right.appendChild(b);
      }

      card.appendChild(left);
      card.appendChild(right);
      dayList.appendChild(card);
    }
  }

  async function loadDay(date){
    dayTitle.textContent = `Dan: ${date}`;
    daySub.textContent = `Filter: ${status}`;

    const url = new URL(@json(route('doctor.schedule.day')), window.location.origin);
    url.searchParams.set('datum', date);
    url.searchParams.set('status', status);

    const res = await fetch(url.toString(), { headers:{'Accept':'application/json'}});
    const data = await res.json();

    const w = data.window || {from:'09:00', to:'17:00'};
    const items = data.items || [];

    renderVertical(w.from, w.to, items);
    renderList(items);
  }

  tabs.addEventListener('click', (e) => {
    const b = e.target.closest('.d-tab');
    if (!b) return;

    status = b.dataset.status;
    [...tabs.querySelectorAll('.d-tab')].forEach(x => x.classList.remove('active'));
    b.classList.add('active');

    loadMonth();
    if (selectedDate) loadDay(selectedDate);
  });

  prevMonth.addEventListener('click', () => {
    monthCursor.setMonth(monthCursor.getMonth()-1);
    loadMonth();
  });
  nextMonth.addEventListener('click', () => {
    monthCursor.setMonth(monthCursor.getMonth()+1);
    loadMonth();
  });

  loadMonth();
})();
</script>
@endpush
@endsection