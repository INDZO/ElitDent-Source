<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Support\TermMaintenance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DoctorWorkController extends Controller
{
    private function ensureDoctor(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'doktor', 403);
    }

    private function doctorTeamIds(string $doctorEmail): array
    {
        return DB::table('TIM_CLAN')
            ->where('DoktorEmail', $doctorEmail)
            ->pluck('TimID')
            ->map(fn($x) => (int)$x)
            ->all();
    }

    private function doctorOwnsTerm(int $terminId, string $doctorEmail): ?object
    {
        $teamIds = $this->doctorTeamIds($doctorEmail);
        if (!$teamIds) return null;

        $term = DB::table('TERMIN as T')
            ->join('TIM as TM', 'T.TimID', '=', 'TM.TimID')
            ->select('T.*', 'TM.Naziv as TimNaziv')
            ->where('T.TerminID', $terminId)
            ->first();

        if (!$term) return null;
        if (!in_array((int)$term->TimID, $teamIds, true)) return null;

        return $term;
    }

    public function index(Request $request)
    {
        $this->ensureDoctor();
        TermMaintenance::cancelExpiredUnrealized();

        $email = Auth::user()->Email;
        $today = Carbon::today()->toDateString();

        $teamIds = $this->doctorTeamIds($email);

        $todayTerms = collect();
        if ($teamIds) {
            $todayTerms = DB::table('TERMIN as T')
                ->join('PACIJENT as P', 'T.PacijentEmail', '=', 'P.Email')
                ->join('KORISNIK as K', 'P.Email', '=', 'K.Email')
                ->join('TIM as TM', 'T.TimID', '=', 'TM.TimID')
                ->select(
                    'T.TerminID',
                    'T.Datum',
                    'T.VremePocetka',
                    'T.VremeZavrsetka',
                    'T.Status',
                    'T.NazivUsluge',
                    'T.PacijentEmail',
                    'K.Ime',
                    'K.Prezime',
                    'P.Telefon',
                    'P.DatumRodjenja', // ✅ više info u listi
                    'TM.Naziv as TimNaziv'
                )
                ->whereIn('T.TimID', $teamIds)
                ->where('T.Datum', $today)
                ->where('T.Status', 'Zakazan')
                ->orderBy('T.VremePocetka')
                ->get();
        }

        $selectedId = (int)($request->query('termin') ?? 0);
        $selected = null;

        $patient = null;
        $health = null;
        $card = null;
        $schema = [];        // zub => [Stanje,Napomena]
        $interventions = collect();
        $documents = collect();

        $upper = ["18", "17", "16", "15", "14", "13", "12", "11", "21", "22", "23", "24", "25", "26", "27", "28"];
        $lower = ["48", "47", "46", "45", "44", "43", "42", "41", "31", "32", "33", "34", "35", "36", "37", "38"];

        if ($selectedId > 0) {
            $selected = $this->doctorOwnsTerm($selectedId, $email);
            if ($selected) {
                $pEmail = $selected->PacijentEmail;

                $patient = DB::table('PACIJENT as P')
                    ->join('KORISNIK as K', 'P.Email', '=', 'K.Email')
                    ->select('P.Email', 'K.Ime', 'K.Prezime', 'P.DatumRodjenja', 'P.Telefon', 'P.Adresa')
                    ->where('P.Email', $pEmail)
                    ->first();

                $health = DB::table('ZDRAVSTVENI_PODACI')->where('PacijentEmail', $pEmail)->first();
                $card   = DB::table('STOMATOLOSKI_KARTON')->where('PacijentEmail', $pEmail)->first();

                $schemaRows = DB::table('DENTALNA_SEMA')->where('PacijentEmail', $pEmail)->get();
                foreach ($schemaRows as $r) {
                    $schema[(string)$r->Zub] = ['Stanje' => (string)$r->Stanje, 'Napomena' => $r->Napomena];
                }

                $interventions = DB::table('INTERVENCIJA as I')
                    ->join('TERMIN as T', 'I.TerminID', '=', 'T.TerminID')
                    ->join('KORISNIK as KD', 'I.DoktorEmail', '=', 'KD.Email')
                    ->select(
                        'T.Datum',
                        'T.VremePocetka',
                        'T.NazivUsluge',
                        'I.Zub',
                        'I.Opis',
                        'I.Napomena',
                        'KD.Ime as DoktorIme',
                        'KD.Prezime as DoktorPrezime'
                    )
                    ->where('I.PacijentEmail', $pEmail)
                    ->orderByDesc('T.Datum')
                    ->orderByDesc('T.VremePocetka')
                    ->limit(25)
                    ->get();

                $documents = DB::table('DOKUMENT')
                    ->where('PacijentEmail', $pEmail)
                    ->orderByDesc('DatumDodavanja')
                    ->limit(50)
                    ->get();
            }
        }

        return view('doctor.work', compact(
            'todayTerms',
            'selectedId',
            'selected',
            'patient',
            'health',
            'card',
            'schema',
            'interventions',
            'documents',
            'upper',
            'lower'
        ));
    }

    public function finish(Request $request, int $id)
    {
        $this->ensureDoctor();
        TermMaintenance::cancelExpiredUnrealized();

        $email = Auth::user()->Email;
        $term = $this->doctorOwnsTerm($id, $email);
        abort_unless($term, 404);

        $today = Carbon::today()->toDateString();
        abort_unless($term->Datum === $today, 422);
        abort_unless($term->Status === 'Zakazan', 422);

        $data = $request->validate([
            'PlanTerapije' => ['nullable', 'string', 'max:255'],
            'Preporuke' => ['nullable', 'string', 'max:255'],
            'Beleske' => ['nullable', 'string', 'max:255'],
            'Napomena' => ['nullable', 'string', 'max:255'],

            'intervencije' => ['nullable', 'array'],
            'intervencije.*.Zub' => ['nullable', 'string', 'max:5'],
            'intervencije.*.Opis' => ['required_with:intervencije', 'string', 'max:255'],
            'intervencije.*.Napomena' => ['nullable', 'string', 'max:120'],

            'dental' => ['nullable', 'array'],
            'dental.*.Zub' => ['required_with:dental', 'string', 'max:5'],
            'dental.*.Stanje' => ['required_with:dental', 'string', 'max:80'],
            'dental.*.Napomena' => ['nullable', 'string', 'max:120'],

            'docs' => ['nullable'],
            'docs.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'intervencije.*.Opis.required_with' => 'Opis intervencije je obavezan.',
            'dental.*.Zub.required_with' => 'Zub je obavezan.',
            'dental.*.Stanje.required_with' => 'Stanje zuba je obavezno.',
            'docs.*.mimes' => 'Dozvoljeni formati dokumenata: JPG, PNG, PDF.',
            'docs.*.max' => 'Dokument može biti maksimalno 10MB.',
        ]);

        $pEmail = $term->PacijentEmail;

        DB::transaction(function () use ($data, $pEmail, $email, $id, $today, $request) {
            DB::table('STOMATOLOSKI_KARTON')->updateOrInsert(
                ['PacijentEmail' => $pEmail],
                [
                    'PlanTerapije' => $this->nullIfEmpty($data['PlanTerapije'] ?? null),
                    'Preporuke'   => $this->nullIfEmpty($data['Preporuke'] ?? null),
                    'Beleske'     => $this->nullIfEmpty($data['Beleske'] ?? null),
                    'Napomena'    => $this->nullIfEmpty($data['Napomena'] ?? null),
                ]
            );

            foreach (($data['dental'] ?? []) as $d) {
                $zub = trim((string)($d['Zub'] ?? ''));
                $stanje = trim((string)($d['Stanje'] ?? ''));
                if ($zub === '' || $stanje === '') continue;

                DB::table('DENTALNA_SEMA')->updateOrInsert(
                    ['PacijentEmail' => $pEmail, 'Zub' => $zub],
                    [
                        'Stanje' => $stanje,
                        'Napomena' => $this->nullIfEmpty($d['Napomena'] ?? null),
                    ]
                );
            }

            foreach (($data['intervencije'] ?? []) as $i) {
                $opis = trim((string)($i['Opis'] ?? ''));
                if ($opis === '') continue;

                DB::table('INTERVENCIJA')->insert([
                    'TerminID' => $id,
                    'PacijentEmail' => $pEmail,
                    'DoktorEmail' => $email,
                    'Zub' => $this->nullIfEmpty($i['Zub'] ?? null),
                    'Opis' => $opis,
                    'Napomena' => $this->nullIfEmpty($i['Napomena'] ?? null),
                ]);
            }

            if ($request->hasFile('docs')) {
                foreach ($request->file('docs') as $file) {
                    if (!$file || !$file->isValid()) continue;

                    $putanja = 'db:' . (string) Str::uuid();
                    $orig = $file->getClientOriginalName();
                    $mime = $file->getMimeType() ?: $file->getClientMimeType();
                    $size = (int) $file->getSize();
                    $bytes = file_get_contents($file->getRealPath());

                    if (Schema::hasColumn('DOKUMENT', 'Sadrzaj')) {
                        DB::table('DOKUMENT')->insert([
                            'PacijentEmail' => $pEmail,
                            'Putanja' => $putanja,
                            'Naziv' => pathinfo($orig, PATHINFO_FILENAME) ?: 'Dokument',
                            'Tip' => (str_starts_with((string)$mime, 'image/') ? 'slika' : 'pdf'),
                            'OriginalnoIme' => $orig,
                            'MimeType' => $mime,
                            'Velicina' => $size,
                            'Sadrzaj' => $bytes,
                            'DodaoEmail' => $email,
                            'DatumDodavanja' => $today,
                            'Napomena' => null,
                        ]);
                    }
                }
            }

            DB::table('TERMIN')->where('TerminID', $id)->update([
                'Status' => 'Realizovan',
                'DatumOtkazivanja' => null,
                'Razlog' => null,
            ]);
        });

        return redirect()->route('doctor.work')->with('ok', 'Rad je sačuvan i termin je završen.');
    }

    public function docView(Request $request)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $terminId = (int)($request->query('termin') ?? 0);
        $putanja = (string)($request->query('putanja') ?? '');
        abort_if($terminId <= 0 || $putanja === '', 404);

        $term = $this->doctorOwnsTerm($terminId, $email);
        abort_unless($term, 403);

        $doc = DB::table('DOKUMENT')
            ->where('PacijentEmail', $term->PacijentEmail)
            ->where('Putanja', $putanja)
            ->first();
        abort_unless($doc, 404);
        abort_unless(isset($doc->Sadrzaj), 404);

        $mime = $doc->MimeType ?: 'application/octet-stream';
        $disp = (str_starts_with($mime, 'image/') || $mime === 'application/pdf') ? 'inline' : 'attachment';

        return response($doc->Sadrzaj)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', $disp . '; filename="' . ($doc->OriginalnoIme ?? 'dokument') . '"');
    }

    public function docDownload(Request $request)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $terminId = (int)($request->query('termin') ?? 0);
        $putanja = (string)($request->query('putanja') ?? '');
        abort_if($terminId <= 0 || $putanja === '', 404);

        $term = $this->doctorOwnsTerm($terminId, $email);
        abort_unless($term, 403);

        $doc = DB::table('DOKUMENT')
            ->where('PacijentEmail', $term->PacijentEmail)
            ->where('Putanja', $putanja)
            ->first();
        abort_unless($doc, 404);
        abort_unless(isset($doc->Sadrzaj), 404);

        return response($doc->Sadrzaj)
            ->header('Content-Type', $doc->MimeType ?: 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . ($doc->OriginalnoIme ?? 'dokument') . '"');
    }

    private function nullIfEmpty($v)
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }
}
