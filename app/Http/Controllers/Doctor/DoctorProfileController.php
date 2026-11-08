<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Support\TermMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorProfileController extends Controller
{
    private function ensureDoctor(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'doktor', 403);
    }

    public function show()
    {
        $this->ensureDoctor();

        TermMaintenance::cancelExpiredUnrealized();

        $email = Auth::user()->Email;

        $doctor = DB::table('DOKTOR')
            ->join('KORISNIK', 'DOKTOR.Email', '=', 'KORISNIK.Email')
            ->select('DOKTOR.Email', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija', 'DOKTOR.Telefon')
            ->where('DOKTOR.Email', $email)
            ->first();

        abort_unless($doctor, 404);

        $availability = DB::table('DOKTOR_RASPOLOZIVOST')->where('DoktorEmail', $email)->first();
        $offDays = DB::table('DOKTOR_NERADNI_DAN')->where('DoktorEmail', $email)->orderBy('Datum', 'desc')->get();

        return view('doctor.profile', compact('doctor', 'availability', 'offDays'));
    }

    public function addOffDay(Request $request)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        $data = $request->validateWithBag('offday', [
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

        $cancelled = TermMaintenance::cancelTermsForDoctorOffDay($email, $data['Datum'], $data['Razlog'] ?? null);

        $msg = 'Neradni dan je dodat.';
        if ($cancelled > 0) $msg .= " Otkazano termina: {$cancelled}.";

        return back()->with('ok', $msg);
    }

    public function deleteOffDay(string $datum)
    {
        $this->ensureDoctor();
        $email = Auth::user()->Email;

        DB::table('DOKTOR_NERADNI_DAN')
            ->where('DoktorEmail', $email)
            ->where('Datum', $datum)
            ->delete();

        return back()->with('ok', 'Neradni dan je obrisan.');
    }
}
