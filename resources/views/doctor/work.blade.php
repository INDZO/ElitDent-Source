@extends('layouts.doctor')

@section('title','Rad')
@section('pageTitle','Rad')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-work.css') }}">
@endpush

@section('content')
<div class="dw-topbar">
  <div>
    <h1 class="h1">Rad</h1>
    <p class="muted">Današnji termini (aktivni). Klikni termin da otvoriš karton pacijenta.</p>
  </div>

  @if($selected)
    <div class="dw-actions">
      <a class="btn btn-ghost" href="{{ route('doctor.work') }}">Nazad na listu</a>
      <button class="btn btn-primary" type="button" id="dwToggleEdit">Završi rad</button>
    </div>
  @endif
</div>

{{-- LISTA (full width) --}}
@if(!$selected)
  <div class="panel">
    <h3>Danas (Zakazani)</h3>
    <p class="muted" style="margin-top:6px;">{{ \Carbon\Carbon::today()->format('d.m.Y') }}</p>

    <div class="dw-list">
      @forelse($todayTerms as $t)
        @php
          
        @endphp
        <a class="dw-item" href="{{ route('doctor.work', ['termin'=>$t->TerminID]) }}">
          <div class="dw-left">
            <div class="dw-time">
              {{ substr((string)$t->VremePocetka,0,5) }}–{{ substr((string)$t->VremeZavrsetka,0,5) }}
            </div>
            <div class="dw-service">{{ $t->NazivUsluge }}</div>
            <div class="dw-sub">
              Pacijent: <b>{{ $t->Ime }} {{ $t->Prezime }}</b>
              @if(!empty($t->Telefon)) • Tel: {{ $t->Telefon }} @endif
              <br>
              Tim: {{ $t->TimNaziv }}
              @if(!empty($t->DatumRodjenja)) • Rođen: {{ $t->DatumRodjenja }} @endif
            </div>
          </div>
          <div class="dw-right">
            <span class="dw-pill">Status: {{ $t->Status }}</span>
            <span class="dw-pill">ID: {{ $t->TerminID }}</span>
          </div>
        </a>
      @empty
        <div class="muted">Nema zakazanih termina za danas.</div>
      @endforelse
    </div>
  </div>

@else
  <div class="panel">
    <h3>Termin #{{ $selected->TerminID }}</h3>
    <p class="muted" style="margin-top:6px;">
      {{ $selected->Datum }}
      • {{ substr((string)$selected->VremePocetka,0,5) }}–{{ substr((string)$selected->VremeZavrsetka,0,5) }}
      • {{ $selected->NazivUsluge }}
      • Tim: {{ $selected->TimNaziv }}
    </p>
  </div>

  <div class="dw-pat-card" style="margin-top:14px;">
    <div class="dw-pat-title">{{ $patient->Ime }} {{ $patient->Prezime }}</div>
    <div class="dw-pat-meta">
      <span class="dw-meta-pill">Email: {{ $patient->Email }}</span>
      <span class="dw-meta-pill">Rođen: {{ $patient->DatumRodjenja }}</span>
      <span class="dw-meta-pill">Tel: {{ $patient->Telefon }}</span>
      <span class="dw-meta-pill">Adresa: {{ $patient->Adresa }}</span>
    </div>
  </div>

  {{-- VIEW MODE --}}
  <div class="dw-view dwz-scope" id="dwView" style="margin-top:14px;">
    {{-- dental --}}
    <div class="dwz-card">
      <div class="dwz-head">
        <div class="dwz-title">Dentalna šema</div>
        <div class="dwz-legend">
          <span class="dwz-leg"><span class="dwz-dot ok"></span> Zdrav</span>
          <span class="dwz-leg"><span class="dwz-dot danger"></span> Karijes</span>
          <span class="dwz-leg"><span class="dwz-dot warn"></span> Plomba</span>
          <span class="dwz-leg"><span class="dwz-dot info"></span> Kruna/Most</span>
          <span class="dwz-leg"><span class="dwz-dot missing"></span> Nedostaje</span>
        </div>
      </div>

      <div class="dwz-jaw">
        <div class="dwz-jaw-title">Gornja vilica</div>
        <div class="dwz-jaw-row">
          <div class="dwz-half">
            @foreach(array_slice($upper,0,8) as $z)
              <div class="dwz-tooth"
                   data-zub="{{ $z }}"
                   data-state="{{ $schema[$z]['Stanje'] ?? 'Zdrav' }}"
                   data-note="{{ $schema[$z]['Napomena'] ?? '' }}">
                <span class="dwz-dot2"></span>
                <span class="dwz-num">{{ $z }}</span>

                <div class="dwz-tip">
                  <div class="dwz-tip-title">Zub {{ $z }}</div>
                  <div class="dwz-tip-row"><span class="dwz-tip-label">Stanje:</span> {{ $schema[$z]['Stanje'] ?? 'Zdrav' }}</div>
                  @if(!empty($schema[$z]['Napomena']))
                    <div class="dwz-tip-row" style="margin-top:4px;"><span class="dwz-tip-label">Napomena:</span> {{ $schema[$z]['Napomena'] }}</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          <div class="dwz-half">
            @foreach(array_slice($upper,8,8) as $z)
              <div class="dwz-tooth"
                   data-zub="{{ $z }}"
                   data-state="{{ $schema[$z]['Stanje'] ?? 'Zdrav' }}"
                   data-note="{{ $schema[$z]['Napomena'] ?? '' }}">
                <span class="dwz-dot2"></span>
                <span class="dwz-num">{{ $z }}</span>

                <div class="dwz-tip">
                  <div class="dwz-tip-title">Zub {{ $z }}</div>
                  <div class="dwz-tip-row"><span class="dwz-tip-label">Stanje:</span> {{ $schema[$z]['Stanje'] ?? 'Zdrav' }}</div>
                  @if(!empty($schema[$z]['Napomena']))
                    <div class="dwz-tip-row" style="margin-top:4px;"><span class="dwz-tip-label">Napomena:</span> {{ $schema[$z]['Napomena'] }}</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="dwz-jaw">
        <div class="dwz-jaw-title">Donja vilica</div>
        <div class="dwz-jaw-row">
          <div class="dwz-half">
            @foreach(array_slice($lower,0,8) as $z)
              <div class="dwz-tooth"
                   data-zub="{{ $z }}"
                   data-state="{{ $schema[$z]['Stanje'] ?? 'Zdrav' }}"
                   data-note="{{ $schema[$z]['Napomena'] ?? '' }}">
                <span class="dwz-dot2"></span>
                <span class="dwz-num">{{ $z }}</span>

                <div class="dwz-tip">
                  <div class="dwz-tip-title">Zub {{ $z }}</div>
                  <div class="dwz-tip-row"><span class="dwz-tip-label">Stanje:</span> {{ $schema[$z]['Stanje'] ?? 'Zdrav' }}</div>
                  @if(!empty($schema[$z]['Napomena']))
                    <div class="dwz-tip-row" style="margin-top:4px;"><span class="dwz-tip-label">Napomena:</span> {{ $schema[$z]['Napomena'] }}</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          <div class="dwz-half">
            @foreach(array_slice($lower,8,8) as $z)
              <div class="dwz-tooth"
                   data-zub="{{ $z }}"
                   data-state="{{ $schema[$z]['Stanje'] ?? 'Zdrav' }}"
                   data-note="{{ $schema[$z]['Napomena'] ?? '' }}">
                <span class="dwz-dot2"></span>
                <span class="dwz-num">{{ $z }}</span>

                <div class="dwz-tip">
                  <div class="dwz-tip-title">Zub {{ $z }}</div>
                  <div class="dwz-tip-row"><span class="dwz-tip-label">Stanje:</span> {{ $schema[$z]['Stanje'] ?? 'Zdrav' }}</div>
                  @if(!empty($schema[$z]['Napomena']))
                    <div class="dwz-tip-row" style="margin-top:4px;"><span class="dwz-tip-label">Napomena:</span> {{ $schema[$z]['Napomena'] }}</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    {{-- karton + health --}}
    <div class="grid2" style="margin-top:14px;">
      <div class="panel">
        <h3>Karton (read-only)</h3>
        <div class="form">
          <label>Plan terapije</label><input value="{{ $card->PlanTerapije ?? '' }}" disabled>
          <label>Preporuke</label><input value="{{ $card->Preporuke ?? '' }}" disabled>
          <label>Beleske</label><input value="{{ $card->Beleske ?? '' }}" disabled>
          <label>Napomena</label><input value="{{ $card->Napomena ?? '' }}" disabled>
        </div>
      </div>

      <div class="panel">
        <h3>Zdravstveni podaci (read-only)</h3>
        <div class="form">
          <label>Alergije</label><input value="{{ $health->Alergije ?? '' }}" disabled>
          <label>Hronične bolesti</label><input value="{{ $health->HronicneBolesti ?? '' }}" disabled>
          <label>Terapije</label><input value="{{ $health->Terapije ?? '' }}" disabled>
          <label>Napomena</label><input value="{{ $health->Napomena ?? '' }}" disabled>
        </div>
      </div>
    </div>

    {{-- interventions history --}}
    <div class="panel" style="margin-top:14px;">
      <h3>Istorija intervencija</h3>
      <p class="muted" style="margin-top:6px;">Poslednjih 25 zapisa.</p>

      @if(count($interventions) === 0)
        <div class="muted">Nema intervencija.</div>
      @else
        <table class="dw-table">
          <thead>
            <tr><th>Datum</th><th>Usluga</th><th>Zub</th><th>Opis</th><th>Doktor</th></tr>
          </thead>
          <tbody>
            @foreach($interventions as $i)
              <tr>
                <td>{{ $i->Datum }} {{ substr((string)$i->VremePocetka,0,5) }}</td>
                <td>{{ $i->NazivUsluge }}</td>
                <td>{{ $i->Zub ?? '—' }}</td>
                <td>
                  <div style="font-weight:900;">{{ $i->Opis }}</div>
                  @if($i->Napomena)<div class="muted" style="margin-top:2px;">{{ $i->Napomena }}</div>@endif
                </td>
                <td>{{ $i->DoktorIme }} {{ $i->DoktorPrezime }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    {{-- documents --}}
    <div class="panel" style="margin-top:14px;">
      <h3>Dokumenta</h3>
      @if(count($documents) === 0)
        <div class="muted">Nema dokumenata.</div>
      @else
        <table class="dw-table">
          <thead><tr><th>Naziv</th><th>Tip</th><th>Datum</th><th>Akcije</th></tr></thead>
          <tbody>
            @foreach($documents as $d)
              <tr>
                <td>{{ $d->Naziv }}</td>
                <td>{{ $d->Tip }}</td>
                <td>{{ $d->DatumDodavanja }}</td>
                <td class="dw-doc-actions">
                  @if(isset($d->Sadrzaj))
                    <a class="btn btn-ghost" target="_blank" href="{{ route('doctor.work.doc.view', ['termin'=>$selected->TerminID, 'putanja'=>$d->Putanja]) }}">Pregled</a>
                    <a class="btn btn-ghost" href="{{ route('doctor.work.doc.download', ['termin'=>$selected->TerminID, 'putanja'=>$d->Putanja]) }}">Preuzmi</a>
                  @else
                    <span class="muted">Nije dostupno</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  {{-- EDIT MODE --}}
  <div class="dw-edit dwz-scope" id="dwEdit" style="margin-top:14px;">
    <form class="panel form" method="POST" action="{{ route('doctor.work.finish', $selected->TerminID) }}" enctype="multipart/form-data">
      @csrf

      <h3>Završetak termina</h3>
      <p class="muted">Klikni zub da upišeš stanje. Dodaj intervencije i dokumenta. Sačuvaj i termin postaje realizovan.</p>

      <div class="grid2">
        <div><label>Plan terapije</label><input name="PlanTerapije" value="{{ old('PlanTerapije', $card->PlanTerapije ?? '') }}"></div>
        <div><label>Preporuke</label><input name="Preporuke" value="{{ old('Preporuke', $card->Preporuke ?? '') }}"></div>
        <div><label>Beleske</label><input name="Beleske" value="{{ old('Beleske', $card->Beleske ?? '') }}"></div>
        <div><label>Napomena</label><input name="Napomena" value="{{ old('Napomena', $card->Napomena ?? '') }}"></div>
      </div>

      <hr class="hr">

      <div class="dwz-card">
        <div class="dwz-head">
          <div class="dwz-title">Dentalna šema (klikni zub)</div>
          <div class="dwz-legend">
            <span class="dwz-leg"><span class="dwz-dot ok"></span> Zdrav</span>
            <span class="dwz-leg"><span class="dwz-dot danger"></span> Karijes</span>
            <span class="dwz-leg"><span class="dwz-dot warn"></span> Plomba</span>
            <span class="dwz-leg"><span class="dwz-dot info"></span> Kruna/Most</span>
            <span class="dwz-leg"><span class="dwz-dot missing"></span> Nedostaje</span>
          </div>
        </div>

        <div id="dwDentalHidden"></div>

        <div class="dwz-jaw">
          <div class="dwz-jaw-title">Gornja vilica</div>
          <div class="dwz-jaw-row">
            <div class="dwz-half" id="dwUpperLeft"></div>
            <div class="dwz-half" id="dwUpperRight"></div>
          </div>
        </div>

        <div class="dwz-jaw">
          <div class="dwz-jaw-title">Donja vilica</div>
          <div class="dwz-jaw-row">
            <div class="dwz-half" id="dwLowerLeft"></div>
            <div class="dwz-half" id="dwLowerRight"></div>
          </div>
        </div>
      </div>

      <div class="dw-rep" style="margin-top:14px;">
        <h4 style="margin:0;">Intervencije</h4>
        <p class="muted" style="margin-top:6px;">Dodaj jednu ili više intervencija za ovaj termin.</p>

        <div id="dwInterRows"></div>
        <button type="button" class="btn btn-ghost dw-rep-add" id="dwAddInter">+ Dodaj intervenciju</button>
      </div>

      <div class="dw-rep" style="margin-top:14px;">
        <h4 style="margin:0;">Dokumenta (opciono)</h4>
        <p class="muted" style="margin-top:6px;">Možeš dodati više fajlova (JPG/PNG/PDF).</p>
        <input type="file" name="docs[]" multiple>
      </div>

      <div class="dw-actions" style="margin-top:14px;">
        <button type="button" class="btn btn-ghost" id="dwBack">Nazad (read-only)</button>
        <button type="submit" class="btn btn-primary">Sačuvaj i završi termin</button>
      </div>

      @if($errors->any())
        <div class="flash err" style="margin-top:12px;">{{ $errors->first() }}</div>
      @endif
    </form>
  </div>

  {{-- Tooth modal --}}
  <div class="dwz-modal" id="dwToothModal">
    <div class="dwz-backdrop" id="dwToothBackdrop"></div>
    <div class="dwz-box">
      <div class="dwz-head2">
        <div class="dwz-title2" id="dwToothTitle">Zub</div>
        <button type="button" class="dwz-x" id="dwToothClose">✕</button>
      </div>
      <div class="dwz-body">
        <div class="form">
          <label>Stanje</label>
          <select id="dwToothState">
            <option value="Zdrav">Zdrav</option>
            <option value="Karijes">Karijes</option>
            <option value="Plomba">Plomba</option>
            <option value="Kruna/Most">Kruna/Most</option>
            <option value="Nedostaje">Nedostaje</option>
          </select>

          <label>Napomena (opciono)</label>
          <input id="dwToothNote" placeholder="npr. veliki karijes, kontrola za 2 nedelje">

          <button type="button" class="btn btn-primary" id="dwToothSave">Sačuvaj zub</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
  (() => {
    
    function applyStateClasses(root){
      const list = (root || document).querySelectorAll('.dwz-tooth');
      list.forEach(el => {
        const st = (el.getAttribute('data-state') || 'Zdrav').toLowerCase();
        el.classList.remove('dwz-st-zdrav','dwz-st-karijes','dwz-st-plomba','dwz-st-kruna','dwz-st-nedostaje');
        if (st.includes('karijes')) el.classList.add('dwz-st-karijes');
        else if (st.includes('plomba') || st.includes('ispun')) el.classList.add('dwz-st-plomba');
        else if (st.includes('kruna') || st.includes('most')) el.classList.add('dwz-st-kruna');
        else if (st.includes('nedost')) el.classList.add('dwz-st-nedostaje');
        else el.classList.add('dwz-st-zdrav');
      });
    }
    applyStateClasses(document);

    
    const btn = document.getElementById('dwToggleEdit');
    const view = document.getElementById('dwView');
    const edit = document.getElementById('dwEdit');
    const back = document.getElementById('dwBack');

    function openEdit(){
      view.classList.add('is-hidden');
      edit.classList.add('is-open');
      btn.textContent = 'U toku rada';
      btn.disabled = true;
      edit.scrollIntoView({behavior:'smooth', block:'start'});
    }
    function backView(){
      view.classList.remove('is-hidden');
      edit.classList.remove('is-open');
      btn.textContent = 'Završi rad';
      btn.disabled = false;
      view.scrollIntoView({behavior:'smooth', block:'start'});
    }
    btn.addEventListener('click', openEdit);
    back.addEventListener('click', backView);

    
    const teeth = @json(array_merge($upper,$lower));
    const interRows = document.getElementById('dwInterRows');
    let i = 0;

    function interRow(idx){
      const wrap = document.createElement('div');
      wrap.className = 'dw-rep-row';
      wrap.innerHTML = `
        <div>
          <label>Zub</label>
          <select name="intervencije[${idx}][Zub]">
            <option value="">—</option>
            ${teeth.map(z=>`<option value="${z}">${z}</option>`).join('')}
          </select>
        </div>
        <div>
          <label>Opis *</label>
          <input name="intervencije[${idx}][Opis]" placeholder="npr. čišćenje karijesa, plomba..." required>
        </div>
        <div>
          <label>Napomena</label>
          <input name="intervencije[${idx}][Napomena]" placeholder="opciono">
        </div>
        <button type="button" class="dw-rep-del" title="Ukloni">×</button>
      `;
      wrap.querySelector('.dw-rep-del').addEventListener('click', () => wrap.remove());
      return wrap;
    }
    document.getElementById('dwAddInter').addEventListener('click', () => interRows.appendChild(interRow(i++)));
    interRows.appendChild(interRow(i++));

    
    const schema = @json($schema); 
    const upper = @json($upper);
    const lower = @json($lower);

    const ul = document.getElementById('dwUpperLeft');
    const ur = document.getElementById('dwUpperRight');
    const ll = document.getElementById('dwLowerLeft');
    const lr = document.getElementById('dwLowerRight');
    const hidden = document.getElementById('dwDentalHidden');

    const changes = {}; 

    function normalizeState(s){
      const v = (s || '').toLowerCase();
      if (v.includes('karijes')) return 'Karijes';
      if (v.includes('plomba') || v.includes('ispun')) return 'Plomba';
      if (v.includes('kruna') || v.includes('most')) return 'Kruna/Most';
      if (v.includes('nedost')) return 'Nedostaje';
      return 'Zdrav';
    }
    function stateClass(st){
      const s = normalizeState(st);
      if (s === 'Karijes') return 'dwz-st-karijes';
      if (s === 'Plomba') return 'dwz-st-plomba';
      if (s === 'Kruna/Most') return 'dwz-st-kruna';
      if (s === 'Nedostaje') return 'dwz-st-nedostaje';
      return 'dwz-st-zdrav';
    }

    function toothEl(zub){
      const cur = changes[zub] || schema[zub] || {Stanje:'Zdrav', Napomena:''};
      const st = normalizeState(cur.Stanje || 'Zdrav');
      const note = (cur.Napomena || '').toString().trim();

      const el = document.createElement('div');
      el.className = `dwz-tooth dwz-click ${stateClass(st)}`;
      el.dataset.zub = zub;

      const dot = document.createElement('span');
      dot.className = 'dwz-dot2';

      const num = document.createElement('span');
      num.className = 'dwz-num';
      num.textContent = zub;

      const tip = document.createElement('div');
      tip.className = 'dwz-tip';
      tip.innerHTML = `
        <div class="dwz-tip-title">Zub ${zub}</div>
        <div class="dwz-tip-row"><span class="dwz-tip-label">Stanje:</span> ${st}</div>
        ${note ? `<div class="dwz-tip-row" style="margin-top:4px;"><span class="dwz-tip-label">Napomena:</span> ${escapeHtml(note)}</div>` : ``}
      `;

      el.appendChild(dot);
      el.appendChild(num);
      el.appendChild(tip);

      el.addEventListener('click', () => openModal(zub));
      return el;
    }

    function render(){
      ul.innerHTML = ''; ur.innerHTML = ''; ll.innerHTML = ''; lr.innerHTML = '';
      upper.slice(0,8).forEach(z => ul.appendChild(toothEl(z)));
      upper.slice(8,16).forEach(z => ur.appendChild(toothEl(z)));
      lower.slice(0,8).forEach(z => ll.appendChild(toothEl(z)));
      lower.slice(8,16).forEach(z => lr.appendChild(toothEl(z)));
    }

    function rebuildHidden(){
      hidden.innerHTML = '';
      let idx = 0;
      for (const [zub, obj] of Object.entries(changes)){
        const st = normalizeState(obj.Stanje || 'Zdrav');
        const note = (obj.Napomena || '').toString();
        hidden.insertAdjacentHTML('beforeend', `
          <input type="hidden" name="dental[${idx}][Zub]" value="${zub}">
          <input type="hidden" name="dental[${idx}][Stanje]" value="${escapeAttr(st)}">
          <input type="hidden" name="dental[${idx}][Napomena]" value="${escapeAttr(note)}">
        `);
        idx++;
      }
    }

    
    const modal = document.getElementById('dwToothModal');
    const backdrop = document.getElementById('dwToothBackdrop');
    const closeBtn = document.getElementById('dwToothClose');
    const title = document.getElementById('dwToothTitle');
    const stateSel = document.getElementById('dwToothState');
    const noteInp = document.getElementById('dwToothNote');
    const saveBtn = document.getElementById('dwToothSave');

    let activeZub = null;

    function openModal(zub){
      activeZub = zub;
      title.textContent = `Zub ${zub}`;
      const cur = changes[zub] || schema[zub] || {Stanje:'Zdrav', Napomena:''};
      stateSel.value = normalizeState(cur.Stanje || 'Zdrav');
      noteInp.value = (cur.Napomena || '').toString();
      modal.style.display = 'block';
      setTimeout(() => stateSel.focus(), 30);
    }
    function closeModal(){
      modal.style.display = 'none';
      activeZub = null;
    }
    backdrop.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });

    saveBtn.addEventListener('click', () => {
      if (!activeZub) return;
      const st = normalizeState(stateSel.value);
      const note = noteInp.value.trim();
      changes[activeZub] = {Stanje: st, Napomena: note};
      rebuildHidden();
      render();
      closeModal();
    });

    function escapeHtml(s){
      return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function escapeAttr(s){
      return (s ?? '').toString().replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    render();
    rebuildHidden();
  })();
  </script>
  @endpush

@endif
@endsection