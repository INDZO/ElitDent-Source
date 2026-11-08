<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientAppointmentsController extends Controller
{
    private function ensurePatient(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'pacijent', 403);
    }

    public function index(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $status = $request->query('status', 'Zakazan');
        $allowed = ['Zakazan', 'Realizovan', 'Otkazan'];
        if (!in_array($status, $allowed, true)) $status = 'Zakazan';

        $base = DB::table('TERMIN as T')
            ->join('TIM as TM', 'T.TimID', '=', 'TM.TimID')
            ->join('KORISNIK as KV', 'TM.VodjaEmail', '=', 'KV.Email')
            ->select(
                'T.TerminID',
                'T.Datum',
                'T.VremePocetka',
                'T.VremeZavrsetka',
                'T.Status',
                'T.DatumOtkazivanja',
                'T.Razlog',
                'T.NazivUsluge',
                'TM.Naziv as TimNaziv',
                'KV.Ime as VodjaIme',
                'KV.Prezime as VodjaPrezime'
            )
            ->where('T.PacijentEmail', $u->Email)
            ->where('T.Status', $status);

        if ($status === 'Zakazan') $base->orderBy('T.Datum')->orderBy('T.VremePocetka');
        else $base->orderByDesc('T.Datum')->orderByDesc('T.VremePocetka');

        $items = $base->get();

        $counts = DB::table('TERMIN')
            ->select('Status', DB::raw('COUNT(*) as cnt'))
            ->where('PacijentEmail', $u->Email)
            ->groupBy('Status')
            ->pluck('cnt', 'Status');

        return view('patient.appointments', [
            'items' => $items,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function cancel(Request $request, int $id)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $data = $request->validate([
            'Razlog' => ['required', 'string', 'min:3', 'max:200'],
        ], [
            'Razlog.required' => 'Unesi razlog otkazivanja.',
            'Razlog.min' => 'Razlog je prekratak.',
            'Razlog.max' => 'Razlog može imati najviše 200 karaktera.',
        ]);

        $term = DB::table('TERMIN')
            ->where('TerminID', $id)
            ->where('PacijentEmail', $u->Email)
            ->first();

        abort_unless($term, 404);
        abort_unless($term->Status === 'Zakazan', 422);

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $term->Datum . ' ' . $term->VremePocetka);
        if ($start->isPast()) abort(422);

        DB::table('TERMIN')
            ->where('TerminID', $id)
            ->update([
                'Status' => 'Otkazan',
                'DatumOtkazivanja' => Carbon::today()->toDateString(),
                'Razlog' => $data['Razlog'],
            ]);

        return back()->with('ok', 'Termin je otkazan.');
    }
}
