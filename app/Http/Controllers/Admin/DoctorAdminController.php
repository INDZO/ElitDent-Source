<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TermMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorAdminController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'admin', 403);
    }

    private function uniqueTeamName(string $base, ?int $ignoreTeamId = null): string
    {
        $base = trim($base);
        if ($base === '') $base = 'Novi tim';
        $base = mb_substr($base, 0, 75);

        $name = $base;
        $i = 2;

        while (true) {
            $q = DB::table('TIM')->where('Naziv', $name);
            if ($ignoreTeamId !== null) $q->where('TimID', '!=', $ignoreTeamId);
            if (!$q->exists()) break;

            $suffix = " ($i)";
            $name = mb_substr($base, 0, 80 - mb_strlen($suffix)) . $suffix;
            $i++;
        }

        return $name;
    }

    private function getSoloTeamId(string $leaderEmail): ?int
    {
        $teamIds = DB::table('TIM')->where('VodjaEmail', $leaderEmail)->pluck('TimID');

        foreach ($teamIds as $id) {
            $cnt = DB::table('TIM_CLAN')->where('TimID', $id)->count();
            if ($cnt === 1) {
                $isLeaderMember = DB::table('TIM_CLAN')
                    ->where('TimID', $id)
                    ->where('DoktorEmail', $leaderEmail)
                    ->exists();
                if ($isLeaderMember) return (int)$id;
            }
        }
        return null;
    }

    private function ensureSoloTeamForDoctor(string $doctorEmail, string $ime, string $prezime): int
    {
        $existing = $this->getSoloTeamId($doctorEmail);
        if ($existing) return $existing;

        $name = $this->uniqueTeamName(trim($ime . ' ' . $prezime));
        $teamId = (int) DB::table('TIM')->insertGetId([
            'Naziv' => $name,
            'VodjaEmail' => $doctorEmail,
            'Opis' => 'Samostalni tim (automatski)',
        ]);

        DB::table('TIM_CLAN')->updateOrInsert(
            ['TimID' => $teamId, 'DoktorEmail' => $doctorEmail],
            []
        );

        return $teamId;
    }

    private function deleteDoctorCascade(string $email): void
    {
        $leaderTeamIds = DB::table('TIM')->where('VodjaEmail', $email)->pluck('TimID')->all();

        DB::table('TIM_CLAN')->where('DoktorEmail', $email)->delete();

        foreach ($leaderTeamIds as $tid) {
            DB::table('TIM_NUDI_USLUGU')->where('TimID', $tid)->delete();
            DB::table('TIM_CLAN')->where('TimID', $tid)->delete();
            DB::table('TIM')->where('TimID', $tid)->delete();
        }

        DB::table('DOKTOR_NERADNI_DAN')->where('DoktorEmail', $email)->delete();
        DB::table('DOKTOR_RASPOLOZIVOST')->where('DoktorEmail', $email)->delete();

        DB::table('DOKTOR')->where('Email', $email)->delete();
        DB::table('KORISNIK')->where('Email', $email)->delete();
    }

    public function index()
    {
        $this->ensureAdmin();

        $doctors = DB::table('DOKTOR')
            ->join('KORISNIK', 'DOKTOR.Email', '=', 'KORISNIK.Email')
            ->select('DOKTOR.Email', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija', 'DOKTOR.Telefon')
            ->orderBy('KORISNIK.Prezime')
            ->orderBy('KORISNIK.Ime')
            ->get();

        return view('admin.doctors.index', compact('doctors'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('doctorCreate', [
            'Ime' => ['required', 'string', 'max:30', 'regex:/^[\pL\s\'-]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s\'-]+$/u'],
            'Email' => ['required', 'email', 'max:120', 'unique:KORISNIK,Email'],
            'Specijalizacija' => ['required', 'string', 'max:50'],
            'Telefon' => ['required', 'string', 'max:20', 'regex:/^(?:\+381\s?6\d|06\d)(?:[\s\-]?\d){7,8}$/'],
            'Lozinka' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'Email.unique' => 'Nalog na ovoj email adresi već postoji.',
            'Telefon.regex' => 'Telefon unesite u formatu npr. 06X XXX XXXX ili +381 6X XXX XXXX.',
            'Lozinka.min' => 'Lozinka mora imati najmanje 8 karaktera.',
            'Lozinka.regex' => 'Lozinka mora imati bar jedno veliko slovo, jedno malo slovo i jedan broj.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                User::create([
                    'Email' => $data['Email'],
                    'Lozinka' => Hash::make($data['Lozinka']),
                    'Ime' => $data['Ime'],
                    'Prezime' => $data['Prezime'],
                    'Role' => 'doktor',
                ]);

                DB::table('DOKTOR')->insert([
                    'Email' => $data['Email'],
                    'Specijalizacija' => $data['Specijalizacija'],
                    'Telefon' => $data['Telefon'],
                ]);

                DB::table('DOKTOR_RASPOLOZIVOST')->updateOrInsert(
                    ['DoktorEmail' => $data['Email']],
                    ['RadniDan' => 'Pon-Pet', 'VremeOd' => '09:00:00', 'VremeDo' => '17:00:00']
                );

                $this->ensureSoloTeamForDoctor($data['Email'], $data['Ime'], $data['Prezime']);
            });
        } catch (\Throwable $e) {
            return back()
                ->with('err', 'Greška pri dodavanju doktora. Proveri podatke (email/unique/FK).')
                ->with('open_modal', 'doctorCreate')
                ->withInput();
        }

        return redirect()->route('admin.doctors.index')->with('ok', 'Doktor je dodat (kreiran i samostalni tim).');
    }

    public function show(string $email)
    {
        $this->ensureAdmin();

        $doctor = DB::table('DOKTOR')
            ->join('KORISNIK', 'DOKTOR.Email', '=', 'KORISNIK.Email')
            ->select('DOKTOR.Email', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija', 'DOKTOR.Telefon')
            ->where('DOKTOR.Email', $email)
            ->first();

        abort_unless($doctor, 404);

        $soloTeamId = $this->ensureSoloTeamForDoctor($email, $doctor->Ime, $doctor->Prezime);

        $availability = DB::table('DOKTOR_RASPOLOZIVOST')->where('DoktorEmail', $email)->first();
        $offDays = DB::table('DOKTOR_NERADNI_DAN')->where('DoktorEmail', $email)->orderBy('Datum', 'desc')->get();

        $allServices = DB::table('KATALOG_USLUGA')->orderBy('Naziv')->get();
        $doctorServiceNames = DB::table('TIM_NUDI_USLUGU')
            ->where('TimID', $soloTeamId)
            ->pluck('NazivUsluge')
            ->all();

        return view('admin.doctors.show', compact(
            'doctor',
            'availability',
            'offDays',
            'soloTeamId',
            'allServices',
            'doctorServiceNames'
        ));
    }

    public function saveServices(Request $request, string $email)
    {
        $this->ensureAdmin();

        $doctor = DB::table('KORISNIK')->where('Email', $email)->first();
        abort_unless($doctor, 404);

        $data = $request->validateWithBag('doctorServices', [
            'Usluge' => ['nullable', 'array'],
            'Usluge.*' => ['string', 'exists:KATALOG_USLUGA,Naziv'],
        ], [
            'Usluge.*.exists' => 'Izabrana usluga nije validna.',
        ]);

        $soloTeamId = $this->ensureSoloTeamForDoctor($email, $doctor->Ime, $doctor->Prezime);
        $services = array_values(array_unique($data['Usluge'] ?? []));

        DB::transaction(function () use ($soloTeamId, $services) {
            DB::table('TIM_NUDI_USLUGU')->where('TimID', $soloTeamId)->delete();
            foreach ($services as $naziv) {
                DB::table('TIM_NUDI_USLUGU')->updateOrInsert(
                    ['TimID' => $soloTeamId, 'NazivUsluge' => $naziv],
                    []
                );
            }
        });

        return back()->with('ok', 'Usluge doktora su sačuvane.');
    }

    public function update(Request $request, string $email)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('doctorUpdate', [
            'Ime' => ['required', 'string', 'max:30', 'regex:/^[\pL\s\'-]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s\'-]+$/u'],
            'Specijalizacija' => ['required', 'string', 'max:50'],
            'Telefon' => ['required', 'string', 'max:20', 'regex:/^(?:\+381\s?6\d|06\d)(?:[\s\-]?\d){7,8}$/'],
            'NovaLozinka' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'Telefon.regex' => 'Telefon unesite u formatu npr. 06X XXX XXXX ili +381 6X XXX XXXX.',
            'NovaLozinka.min' => 'Lozinka mora imati najmanje 8 karaktera.',
            'NovaLozinka.regex' => 'Lozinka mora imati bar jedno veliko slovo, jedno malo slovo i jedan broj.',
        ]);

        try {
            DB::transaction(function () use ($data, $email) {
                DB::table('KORISNIK')->where('Email', $email)->update([
                    'Ime' => $data['Ime'],
                    'Prezime' => $data['Prezime'],
                ]);

                if (!empty($data['NovaLozinka'])) {
                    DB::table('KORISNIK')->where('Email', $email)->update([
                        'Lozinka' => Hash::make($data['NovaLozinka']),
                    ]);
                }

                DB::table('DOKTOR')->where('Email', $email)->update([
                    'Specijalizacija' => $data['Specijalizacija'],
                    'Telefon' => $data['Telefon'],
                ]);

                $soloId = $this->getSoloTeamId($email);
                if ($soloId) {
                    $newName = $this->uniqueTeamName(trim($data['Ime'] . ' ' . $data['Prezime']), $soloId);
                    DB::table('TIM')->where('TimID', $soloId)->update(['Naziv' => $newName]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('err', 'Greška pri čuvanju podataka doktora.');
        }

        return back()->with('ok', 'Podaci doktora su sačuvani.');
    }

    public function saveAvailability(Request $request, string $email)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('doctorAvail', [
            'RadniDan' => ['required', 'string', 'max:10'],
            'VremeOd' => ['required', 'date_format:H:i'],
            'VremeDo' => ['required', 'date_format:H:i', 'after:VremeOd'],
        ], [
            'VremeDo.after' => 'Vreme do mora biti nakon vremena od.',
        ]);

        DB::table('DOKTOR_RASPOLOZIVOST')->updateOrInsert(
            ['DoktorEmail' => $email],
            [
                'RadniDan' => $data['RadniDan'],
                'VremeOd' => $data['VremeOd'] . ':00',
                'VremeDo' => $data['VremeDo'] . ':00',
            ]
        );

        return back()->with('ok', 'Raspoloživost je sačuvana.');
    }

    public function addOffDay(Request $request, string $email)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('doctorOffday', [
            'Datum' => ['required', 'date', 'after_or_equal:today'],
            'Razlog' => ['nullable', 'string', 'max:120'],
        ], [
            'Datum.required' => 'Datum je obavezan.',
            'Datum.date' => 'Datum nije validan.',
            'Datum.after_or_equal' => 'Datum neradnog dana ne može biti u prošlosti.',
            'Razlog.max' => 'Razlog može imati najviše 120 karaktera.',
        ]);

        DB::table('DOKTOR_NERADNI_DAN')->updateOrInsert(
            ['DoktorEmail' => $email, 'Datum' => $data['Datum']],
            ['Razlog' => $data['Razlog'] ?? null]
        );

        // ✅ NOVO: otkaži sve zakazane termine tog dana za sve timove gde je doktor član
        $cancelled = TermMaintenance::cancelTermsForDoctorOffDay($email, $data['Datum'], $data['Razlog'] ?? null);

        $msg = 'Neradni dan je dodat.';
        if ($cancelled > 0) $msg .= " Otkazano termina: {$cancelled}.";

        return back()->with('ok', $msg);
    }

    public function deleteOffDay(string $email, string $datum)
    {
        $this->ensureAdmin();

        DB::table('DOKTOR_NERADNI_DAN')->where('DoktorEmail', $email)->where('Datum', $datum)->delete();
        return back()->with('ok', 'Neradni dan je obrisan.');
    }

    public function destroy(string $email)
    {
        $this->ensureAdmin();

        try {
            DB::transaction(function () use ($email) {
                $this->deleteDoctorCascade($email);
            });
        } catch (\Throwable $e) {
            return back()->with('err', 'Doktor ne može biti obrisan (verovatno postoje termini/FK veze).');
        }

        return redirect()->route('admin.doctors.index')->with('ok', 'Doktor i povezani timovi su obrisani.');
    }
}
