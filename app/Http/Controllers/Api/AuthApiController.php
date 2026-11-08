<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'ime' => ['required', 'string', 'max:30'],
            'prezime' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:120', 'unique:KORISNIK,Email'],
            'lozinka' => ['required', 'string', 'min:8', 'confirmed'],
            'datum_rodjenja' => ['required', 'date_format:Y-m-d', 'before:today'],
            'telefon' => ['required', 'string', 'max:20'],
            'adresa' => ['required', 'string', 'max:120'],
            'alergije' => ['nullable', 'string', 'max:255'],
            'hronicne_bolesti' => ['nullable', 'string', 'max:255'],
            'terapije' => ['nullable', 'string', 'max:30'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'Email' => $data['email'],
                'Lozinka' => Hash::make($data['lozinka']),
                'Ime' => $data['ime'],
                'Prezime' => $data['prezime'],
                'Role' => 'pacijent',
            ]);

            DB::table('PACIJENT')->insert([
                'Email' => $data['email'],
                'DatumRodjenja' => $data['datum_rodjenja'],
                'Adresa' => $data['adresa'],
                'Telefon' => $data['telefon'],
            ]);

            DB::table('ZDRAVSTVENI_PODACI')->insert([
                'PacijentEmail' => $data['email'],
                'Alergije' => $data['alergije'] ?? null,
                'HronicneBolesti' => $data['hronicne_bolesti'] ?? null,
                'Terapije' => $data['terapije'] ?? null,
                'Napomena' => null,
                'DatumAzuriranja' => now()->toDateString(),
            ]);

            DB::table('STOMATOLOSKI_KARTON')->insert([
                'PacijentEmail' => $data['email'],
                'PlanTerapije' => null,
                'Preporuke' => null,
                'Beleske' => null,
                'Napomena' => null,
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Pacijent je uspesno registrovan.',
            'token' => $user->createToken('angular-api')->plainTextToken,
            'user' => $user,
            'web_redirect_url' => $this->createWebRedirectUrl($request, $user),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'lozinka' => ['required', 'string'],
        ]);

        $user = User::where('Email', $data['email'])->first();

        if (!$user || !Hash::check($data['lozinka'], $user->Lozinka)) {
            return response()->json([
                'message' => 'Neispravan email ili lozinka.',
            ], 401);
        }

        $token = $user->createToken('angular-api')->plainTextToken;
        return response()->json([
            'message' => 'Uspesno ste prijavljeni.',
            'token' => $token,
            'user' => $user,
            'web_redirect_url' => $this->createWebRedirectUrl($request, $user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Uspesno ste se odjavili.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    private function createWebRedirectUrl(Request $request, User $user): string
    {
        $handoff = Str::random(64);
        Cache::put('angular_web_login:' . $handoff, $user->Email, now()->addMinutes(2));

        return $request->getSchemeAndHttpHost() . '/angular-web-login/' . $handoff;
    }
}
