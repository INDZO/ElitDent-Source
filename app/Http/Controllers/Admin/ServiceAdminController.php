<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceAdminController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'admin', 403);
    }

    public function index()
    {
        $this->ensureAdmin();

        $services = DB::table('KATALOG_USLUGA')
            ->orderBy('Naziv')
            ->get();

        $teamCounts = DB::table('TIM_NUDI_USLUGU')
            ->select('NazivUsluge', DB::raw('COUNT(*) as cnt'))
            ->groupBy('NazivUsluge')
            ->pluck('cnt', 'NazivUsluge');

        return view('admin.services.index', compact('services', 'teamCounts'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('serviceCreate', [
            'Naziv' => ['required', 'string', 'max:80', 'unique:KATALOG_USLUGA,Naziv', 'regex:/^(?=.*\pL)[\pL0-9\s\.\,\-\(\)]+$/u'],
            'Opis' => ['required', 'string', 'max:255'],
            'TrajanjeMinuta' => ['required', 'integer', 'min:5', 'max:480'],
            'Cena' => ['required', 'numeric', 'min:0'],
        ], [
            'Naziv.unique' => 'Usluga sa ovim nazivom već postoji.',
            'Naziv.regex' => 'Naziv mora sadržati bar jedno slovo (dozvoljeni su brojevi, razmaci i znaci . , - ( ) ).',
            'TrajanjeMinuta.min' => 'Trajanje mora biti najmanje 5 minuta.',
            'TrajanjeMinuta.max' => 'Trajanje ne može biti veće od 480 minuta.',
            'Cena.numeric' => 'Cena mora biti broj.',
        ]);

        try {
            DB::table('KATALOG_USLUGA')->insert([
                'Naziv' => $data['Naziv'],
                'Opis' => $data['Opis'],
                'TrajanjeMinuta' => (int)$data['TrajanjeMinuta'],
                'Cena' => number_format((float)$data['Cena'], 2, '.', ''),
            ]);
        } catch (\Throwable $e) {
            return back()->with('err', 'Greška pri dodavanju usluge.')->with('open_modal', 'serviceCreate')->withInput();
        }

        return redirect()->route('admin.services.index')->with('ok', 'Usluga je dodata.');
    }

    public function show(string $naziv)
    {
        $this->ensureAdmin();

        $service = DB::table('KATALOG_USLUGA')->where('Naziv', $naziv)->first();
        abort_unless($service, 404);

        $teams = DB::table('TIM_NUDI_USLUGU')
            ->join('TIM', 'TIM_NUDI_USLUGU.TimID', '=', 'TIM.TimID')
            ->select('TIM.TimID', 'TIM.Naziv', 'TIM.VodjaEmail')
            ->where('TIM_NUDI_USLUGU.NazivUsluge', $naziv)
            ->orderBy('TIM.Naziv')
            ->get();

        $termsCount = DB::table('TERMIN')->where('NazivUsluge', $naziv)->count();

        return view('admin.services.show', compact('service', 'teams', 'termsCount'));
    }

    public function update(Request $request, string $naziv)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'Opis' => ['required', 'string', 'max:255'],
            'TrajanjeMinuta' => ['required', 'integer', 'min:5', 'max:480'],
            'Cena' => ['required', 'numeric', 'min:0'],
        ], [
            'TrajanjeMinuta.min' => 'Trajanje mora biti najmanje 5 minuta.',
            'TrajanjeMinuta.max' => 'Trajanje ne može biti veće od 480 minuta.',
            'Cena.numeric' => 'Cena mora biti broj.',
        ]);

        DB::table('KATALOG_USLUGA')
            ->where('Naziv', $naziv)
            ->update([
                'Opis' => $data['Opis'],
                'TrajanjeMinuta' => (int)$data['TrajanjeMinuta'],
                'Cena' => number_format((float)$data['Cena'], 2, '.', ''),
            ]);

        return back()->with('ok', 'Usluga je sačuvana.');
    }

    public function destroy(string $naziv)
    {
        $this->ensureAdmin();

        try {
            DB::table('KATALOG_USLUGA')->where('Naziv', $naziv)->delete();
        } catch (\Throwable $e) {
            return back()->with('err', 'Usluga ne može biti obrisana (verovatno je već korišćena u terminima).');
        }

        return redirect()->route('admin.services.index')->with('ok', 'Usluga je obrisana.');
    }
}
