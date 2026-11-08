<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientProfileController extends Controller
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

        if (!$health) {
            DB::table('ZDRAVSTVENI_PODACI')->insert([
                'PacijentEmail' => $u->Email,
                'Alergije' => null,
                'HronicneBolesti' => null,
                'Terapije' => null,
                'Napomena' => null,
                'DatumAzuriranja' => now()->toDateString(),
            ]);
            $health = DB::table('ZDRAVSTVENI_PODACI')->where('PacijentEmail', $u->Email)->first();
        }

        return view('patient.profile', compact('u', 'patient', 'health'));
    }

    public function updateProfile(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $data = $request->validateWithBag('profile', [
            'Ime' => ['required', 'string', 'max:30', 'regex:/^[\pL\s\'-]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s\'-]+$/u'],
            'Telefon' => ['required', 'string', 'max:20', 'regex:/^(?:\+381\s?6\d|06\d)(?:[\s\-]?\d){7,8}$/'],
            'Adresa'  => ['required', 'string', 'min:5', 'max:120', 'regex:/^(?=.*\pL)[\pL0-9\s\.,\-\/]{5,120}$/u'],
        ], [
            'Ime.regex' => 'Ime može sadržati samo slova (i razmake).',
            'Prezime.regex' => 'Prezime može sadržati samo slova (i razmake).',
            'Telefon.regex' => 'Telefon unesite u formatu npr. 06X XXX XXXX ili +381 6X XXX XXXX.',
            'Adresa.regex' => 'Adresa mora sadržati bar jedno slovo (npr. "Nemanjina 12, Novi Pazar").',
            'Adresa.min' => 'Adresa je prekratka.',
        ]);

        DB::transaction(function () use ($u, $data) {
            DB::table('KORISNIK')->where('Email', $u->Email)->update([
                'Ime' => $data['Ime'],
                'Prezime' => $data['Prezime'],
            ]);

            DB::table('PACIJENT')->where('Email', $u->Email)->update([
                'Telefon' => $data['Telefon'],
                'Adresa' => $data['Adresa'],
            ]);
        });

        return back()->with('ok', 'Profil je sačuvan.');
    }

    public function updateHealth(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $data = $request->validateWithBag('health', [
            'Alergije' => ['nullable', 'string', 'max:255'],
            'HronicneBolesti' => ['nullable', 'string', 'max:255'],
            'Terapije' => ['nullable', 'string', 'max:30'],
            'Napomena' => ['nullable', 'string', 'max:40'],
        ], [
            'Terapije.max' => 'Terapije mogu imati najviše 30 karaktera.',
            'Napomena.max' => 'Napomena može imati najviše 40 karaktera.',
        ]);

        DB::table('ZDRAVSTVENI_PODACI')
            ->updateOrInsert(
                ['PacijentEmail' => $u->Email],
                [
                    'Alergije' => $this->nullIfEmpty($data['Alergije'] ?? null),
                    'HronicneBolesti' => $this->nullIfEmpty($data['HronicneBolesti'] ?? null),
                    'Terapije' => $this->nullIfEmpty($data['Terapije'] ?? null),
                    'Napomena' => $this->nullIfEmpty($data['Napomena'] ?? null),
                    'DatumAzuriranja' => now()->toDateString(),
                ]
            );

        return back()->with('ok', 'Zdravstveni podaci su sačuvani.');
    }

    private function nullIfEmpty(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
