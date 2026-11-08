<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showAuth(Request $request)
    {
        $step = $request->query('step');
        $showHealth = ($step === 'health') && Auth::check() && (Auth::user()->Role ?? null) === 'pacijent';


        $active = 'login';


        if ($request->query('tab') === 'register') {
            $active = 'register';
        }


        $form = old('_form');
        if ($form === 'login') $active = 'login';
        if ($form === 'register') $active = 'register';


        if (session('errors')?->getBag('login')?->any()) $active = 'login';
        if (session('errors')?->getBag('register')?->any()) $active = 'register';


        if ($showHealth) $active = 'register';

        $health = null;
        if ($showHealth) {
            $health = DB::table('ZDRAVSTVENI_PODACI')
                ->where('PacijentEmail', Auth::user()->Email)
                ->first();
        }

        return view('login', [
            'active' => $active,
            'showHealth' => $showHealth,
            'health' => $health,
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validateWithBag('login', [
            'Email' => ['required', 'email'],
            'Lozinka' => ['required', 'string'],
            '_form' => ['required', 'in:login'],
        ]);

        $user = User::where('Email', $data['Email'])->first();

        if (!$user || !Hash::check($data['Lozinka'], $user->Lozinka)) {
            return back()
                ->withErrors(['Email' => 'Neispravan email ili lozinka.'], 'login')
                ->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function register(Request $request)
    {
        $data = $request->validateWithBag('register', [
            '_form' => ['required', 'in:register'],

            'Ime' => ['required', 'string', 'max:30', 'regex:/^[\pL\s\'-]+$/u'],
            'Prezime' => ['required', 'string', 'max:40', 'regex:/^[\pL\s\'-]+$/u'],

            'Email' => ['required', 'email', 'max:120', 'unique:KORISNIK,Email'],

            'Lozinka' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'confirmed'],

            'DatumRodjenja' => ['required', 'date_format:Y-m-d', 'before:today', 'after_or_equal:1900-01-01'],
            'Telefon' => [
                'required',
                'string',
                'regex:/^(?:\+381|0)6\d[\s\-]?\d{3}[\s\-]?\d{3,4}$/'
            ],
            'Adresa'  => ['required', 'string', 'min:5', 'max:120', 'regex:/^(?=.*\pL)[\pL0-9\s\.,\-\/]{5,120}$/u'],
        ], [
            'Ime.regex' => 'Ime može sadržati samo slova (i razmake).',
            'Prezime.regex' => 'Prezime može sadržati samo slova (i razmake).',
            'Email.unique' => 'Nalog na ovoj email adresi već postoji.',

            'Lozinka.min' => 'Lozinka mora imati najmanje 8 karaktera.',
            'Lozinka.regex' => 'Lozinka mora imati bar jedno veliko slovo, jedno malo slovo i jedan broj.',
            'Lozinka.confirmed' => 'Potvrda lozinke se ne poklapa.',

            'Telefon.regex' => 'Telefon unesite u formatu npr. 06X XXX XXXX ili +381 6X XXX XXXX.',
            'Adresa.regex'  => 'Adresa mora sadržati bar jedno slovo (npr. "Nemanjina 12, Novi Pazar").',
            'Adresa.min'    => 'Adresa je prekratka.',
            'DatumRodjenja.date_format' => 'Datum rođenja mora biti u formatu YYYY-MM-DD.',
            'DatumRodjenja.before' => 'Datum rođenja ne može biti u budućnosti.',
            'DatumRodjenja.after_or_equal' => 'Datum rođenja mora biti realan (posle 1900).',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'Email' => $data['Email'],
                'Lozinka' => Hash::make($data['Lozinka']),
                'Ime' => $data['Ime'],
                'Prezime' => $data['Prezime'],
                'Role' => 'pacijent',
            ]);

            DB::table('PACIJENT')->insert([
                'Email' => $data['Email'],
                'DatumRodjenja' => $data['DatumRodjenja'],
                'Adresa' => $data['Adresa'],
                'Telefon' => $data['Telefon'],
            ]);


            DB::table('ZDRAVSTVENI_PODACI')->insert([
                'PacijentEmail' => $data['Email'],
                'Alergije' => null,
                'HronicneBolesti' => null,
                'Terapije' => null,
                'Napomena' => null,
                'DatumAzuriranja' => now()->toDateString(),
            ]);

            DB::table('STOMATOLOSKI_KARTON')->insert([
                'PacijentEmail' => $data['Email'],
                'PlanTerapije' => null,
                'Preporuke' => null,
                'Beleske' => null,
                'Napomena' => null,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();


        return redirect('/login?step=health&tab=register');
    }

    public function saveHealth(Request $request)
    {
        if (!Auth::check() || (Auth::user()->Role ?? null) !== 'pacijent') abort(403);

        $data = $request->validateWithBag('health', [
            'Alergije' => ['nullable', 'string', 'max:255'],
            'HronicneBolesti' => ['nullable', 'string', 'max:255'],
            'Terapije' => ['nullable', 'string', 'max:30'],
            'Napomena' => ['nullable', 'string', 'max:40'],
        ]);

        DB::table('ZDRAVSTVENI_PODACI')
            ->where('PacijentEmail', Auth::user()->Email)
            ->update([
                'Alergije' => isset($data['Alergije']) && trim($data['Alergije']) !== '' ? trim($data['Alergije']) : null,
                'HronicneBolesti' => isset($data['HronicneBolesti']) && trim($data['HronicneBolesti']) !== '' ? trim($data['HronicneBolesti']) : null,
                'Terapije' => isset($data['Terapije']) && trim($data['Terapije']) !== '' ? trim($data['Terapije']) : null,
                'Napomena' => isset($data['Napomena']) && trim($data['Napomena']) !== '' ? trim($data['Napomena']) : null,
                'DatumAzuriranja' => now()->toDateString(),
            ]);

        return redirect()->route('dashboard');
    }

    public function skipHealth()
    {
        if (!Auth::check() || (Auth::user()->Role ?? null) !== 'pacijent') abort(403);
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect($this->frontendUrl('login', ['logged_out' => 1]));
    }

    public function angularWebLogin(Request $request, string $handoff)
    {
        $email = Cache::pull('angular_web_login:' . $handoff);

        if (!$email) {
            return redirect($this->frontendUrl('login'));
        }

        $user = User::where('Email', $email)->first();

        if (!$user || !in_array($user->Role, ['admin', 'doktor', 'pacijent'], true)) {
            return redirect($this->frontendUrl('login'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function angularWebLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($this->frontendUrl('login', ['logged_out' => 1]));
    }

    private function frontendUrl(string $route, array $query = []): string
    {
        $configured = config('app.frontend_url');
        $base = $configured ? rtrim($configured, '/') : url('/app/#');
        $url = $base . '/' . ltrim($route, '/');

        return $query ? $url . '?' . http_build_query($query) : $url;
    }
}
