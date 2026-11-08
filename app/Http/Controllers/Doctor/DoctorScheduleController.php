<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Support\TermMaintenance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorScheduleController extends Controller
{
    private function ensureDoctor(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'doktor', 403);
    }

    public function index(Request $request)
    {
        $this->ensureDoctor();

        TermMaintenance::cancelExpiredUnrealized();

        $email = Auth::user()->Email;

        $availability = DB::table('DOKTOR_RASPOLOZIVOST')->where('DoktorEmail', $email)->first();
        $from = $availability ? substr((string)$availability->VremeOd, 0, 5) : '09:00';
        $to   = $availability ? substr((string)$availability->VremeDo, 0, 5) : '17:00';

        return view('doctor.schedule', [
            'winFrom' => $from,
            'winTo' => $to,
        ]);
    }

    public function days(Request $request)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['nullable', 'string'],
        ]);

        $status = $data['status'] ?? 'Svi'; // default = Svi
        $month = $data['month'];

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $teamIds = DB::table('TIM_CLAN')->where('DoktorEmail', $email)->pluck('TimID')->map(fn($x) => (int)$x)->all();
        if (count($teamIds) === 0) return response()->json(['counts' => []]);

        $q = DB::table('TERMIN')
            ->select('Datum', DB::raw('COUNT(*) as cnt'))
            ->whereIn('TimID', $teamIds)
            ->whereBetween('Datum', [$start->toDateString(), $end->toDateString()]);

        if ($status !== 'Svi') $q->where('Status', $status);

        $rows = $q->groupBy('Datum')->pluck('cnt', 'Datum');

        $counts = [];
        foreach ($rows as $d => $c) $counts[$d] = (int)$c;

        return response()->json(['counts' => $counts]);
    }

    public function day(Request $request)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $data = $request->validate([
            'datum' => ['required', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $status = $data['status'] ?? 'Svi';
        $date = Carbon::parse($data['datum'])->toDateString();

        $teamIds = DB::table('TIM_CLAN')->where('DoktorEmail', $email)->pluck('TimID')->map(fn($x) => (int)$x)->all();
        if (count($teamIds) === 0) return response()->json(['items' => []]);

        $q = DB::table('TERMIN as T')
            ->join('TIM as TM', 'T.TimID', '=', 'TM.TimID')
            ->join('PACIJENT as P', 'T.PacijentEmail', '=', 'P.Email')
            ->join('KORISNIK as KP', 'P.Email', '=', 'KP.Email')
            ->select(
                'T.TerminID',
                'T.Datum',
                'T.VremePocetka',
                'T.VremeZavrsetka',
                'T.Status',
                'T.Razlog',
                'T.NazivUsluge',
                'TM.Naziv as TimNaziv',
                'KP.Ime as PacIme',
                'KP.Prezime as PacPrezime',
                'T.PacijentEmail',
                'P.Telefon as PacTelefon' // ✅ NOVO
            )
            ->whereIn('T.TimID', $teamIds)
            ->where('T.Datum', $date);

        if ($status !== 'Svi') $q->where('T.Status', $status);

        $items = $q->orderBy('T.VremePocetka')->get()->map(function ($r) {
            $startHm = substr((string)$r->VremePocetka, 0, 5);
            $endHm   = substr((string)$r->VremeZavrsetka, 0, 5);

            $start = Carbon::createFromFormat('Y-m-d H:i:s', $r->Datum . ' ' . $r->VremePocetka);
            $canCancel = ((string)$r->Status === 'Zakazan') && $start->isFuture();

            return [
                'id' => (int)$r->TerminID,
                'date' => (string)$r->Datum,
                'start' => $startHm,
                'end' => $endHm,
                'status' => (string)$r->Status,
                'service' => (string)$r->NazivUsluge,
                'team' => (string)$r->TimNaziv,
                'patient' => trim($r->PacIme . ' ' . $r->PacPrezime),
                'patientEmail' => (string)$r->PacijentEmail,
                'patientPhone' => (string)($r->PacTelefon ?? ''), // ✅ NOVO
                'reason' => $r->Razlog,
                'canCancel' => $canCancel,
            ];
        });

        $availability = DB::table('DOKTOR_RASPOLOZIVOST')->where('DoktorEmail', $email)->first();
        $from = $availability ? substr((string)$availability->VremeOd, 0, 5) : '09:00';
        $to   = $availability ? substr((string)$availability->VremeDo, 0, 5) : '17:00';

        return response()->json([
            'window' => ['from' => $from, 'to' => $to],
            'items' => $items,
        ]);
    }

    public function cancel(Request $request, int $id)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $data = $request->validate([
            'Razlog' => ['required', 'string', 'min:3', 'max:200'],
        ], [
            'Razlog.required' => 'Unesi razlog otkazivanja.',
            'Razlog.min' => 'Razlog je prekratak.',
            'Razlog.max' => 'Razlog može imati najviše 200 karaktera.',
        ]);

        $teamIds = DB::table('TIM_CLAN')->where('DoktorEmail', $email)->pluck('TimID')->map(fn($x) => (int)$x)->all();
        abort_unless(count($teamIds) > 0, 403);

        $term = DB::table('TERMIN')->where('TerminID', $id)->first();
        abort_unless($term, 404);
        abort_unless(in_array((int)$term->TimID, $teamIds, true), 403);
        abort_unless($term->Status === 'Zakazan', 422);

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $term->Datum . ' ' . $term->VremePocetka);
        abort_unless($start->isFuture(), 422);

        $today = Carbon::today()->toDateString();

        DB::table('TERMIN')->where('TerminID', $id)->update([
            'Status' => 'Otkazan',
            'DatumOtkazivanja' => $today,
            'Razlog' => mb_substr($data['Razlog'], 0, 200),
        ]);

        return back()->with('ok', 'Termin je otkazan.');
    }
}
