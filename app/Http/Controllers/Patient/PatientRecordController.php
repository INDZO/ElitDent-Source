<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientRecordController extends Controller
{
    private function ensurePatient(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'pacijent', 403);
    }

    public function show()
    {
        $this->ensurePatient();

        $u = Auth::user();

        $patient = DB::table('PACIJENT')->where('Email', $u->Email)->first();
        $health  = DB::table('ZDRAVSTVENI_PODACI')->where('PacijentEmail', $u->Email)->first();
        $card    = DB::table('STOMATOLOSKI_KARTON')->where('PacijentEmail', $u->Email)->first();

        if (!$card) {
            DB::table('STOMATOLOSKI_KARTON')->insert([
                'PacijentEmail' => $u->Email,
                'PlanTerapije' => null,
                'Preporuke' => null,
                'Beleske' => null,
                'Napomena' => null,
            ]);
            $card = DB::table('STOMATOLOSKI_KARTON')->where('PacijentEmail', $u->Email)->first();
        }

        $schemaCount = DB::table('DENTALNA_SEMA')->where('PacijentEmail', $u->Email)->count();
        if ($schemaCount === 0) {
            foreach ($this->allTeethFDI() as $zub) {
                DB::table('DENTALNA_SEMA')->insert([
                    'PacijentEmail' => $u->Email,
                    'Zub' => $zub,
                    'Stanje' => 'Zdrav',
                    'Napomena' => null,
                ]);
            }
        }

        $schema = DB::table('DENTALNA_SEMA')->where('PacijentEmail', $u->Email)->get();

        $schemaMap = [];
        foreach ($schema as $row) {
            $schemaMap[$row->Zub] = [
                'Stanje' => $row->Stanje,
                'Napomena' => $row->Napomena,
                'Class' => $this->statusClass((string)$row->Stanje),
            ];
        }

        $interventions = DB::table('INTERVENCIJA')
            ->join('TERMIN', 'INTERVENCIJA.TerminID', '=', 'TERMIN.TerminID')
            ->join('KORISNIK as KD', 'INTERVENCIJA.DoktorEmail', '=', 'KD.Email')
            ->select(
                'TERMIN.Datum',
                'TERMIN.NazivUsluge',
                'INTERVENCIJA.Zub',
                'INTERVENCIJA.Opis',
                'INTERVENCIJA.Napomena',
                'KD.Ime as DoktorIme',
                'KD.Prezime as DoktorPrezime'
            )
            ->where('INTERVENCIJA.PacijentEmail', $u->Email)
            ->orderByDesc('TERMIN.Datum')
            ->limit(25)
            ->get();

        $documents = DB::table('DOKUMENT')
            ->where('PacijentEmail', $u->Email)
            ->orderByDesc('DatumDodavanja')
            ->get();

        return view('patient.record', [
            'u' => $u,
            'patient' => $patient,
            'health' => $health,
            'card' => $card,
            'schemaMap' => $schemaMap,
            'interventions' => $interventions,
            'documents' => $documents,
            'upper' => $this->upperFDI(),
            'lower' => $this->lowerFDI(),
        ]);
    }

    private function statusClass(string $stanje): string
    {
        $s = mb_strtolower($stanje);
        if (str_contains($s, 'karijes')) return 'tooth-danger';
        if (str_contains($s, 'plomba') || str_contains($s, 'ispun')) return 'tooth-warn';
        if (str_contains($s, 'kruna') || str_contains($s, 'most')) return 'tooth-info';
        if (str_contains($s, 'nedost')) return 'tooth-muted';
        return 'tooth-ok';
    }

    private function allTeethFDI(): array
    {
        return array_merge($this->upperFDI(), $this->lowerFDI());
    }

    private function upperFDI(): array
    {
        return [
            '18',
            '17',
            '16',
            '15',
            '14',
            '13',
            '12',
            '11',
            '21',
            '22',
            '23',
            '24',
            '25',
            '26',
            '27',
            '28',
        ];
    }

    private function lowerFDI(): array
    {
        return [
            '38',
            '37',
            '36',
            '35',
            '34',
            '33',
            '32',
            '31',
            '41',
            '42',
            '43',
            '44',
            '45',
            '46',
            '47',
            '48',
        ];
    }
}
