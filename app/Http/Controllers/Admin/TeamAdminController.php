<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamAdminController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'admin', 403);
    }

    private function isSoloTeam(int $teamId): bool
    {
        $team = DB::table('TIM')->where('TimID', $teamId)->first();
        if (!$team) return false;

        $cnt = DB::table('TIM_CLAN')->where('TimID', $teamId)->count();
        if ($cnt !== 1) return false;

        return DB::table('TIM_CLAN')
            ->where('TimID', $teamId)
            ->where('DoktorEmail', $team->VodjaEmail)
            ->exists();
    }

    private function soloTeamIds(): array
    {
        $ids = [];
        $teams = DB::table('TIM')->select('TimID', 'VodjaEmail')->get();

        foreach ($teams as $t) {
            $cnt = DB::table('TIM_CLAN')->where('TimID', $t->TimID)->count();
            if ($cnt === 1) {
                $isLeader = DB::table('TIM_CLAN')
                    ->where('TimID', $t->TimID)
                    ->where('DoktorEmail', $t->VodjaEmail)
                    ->exists();
                if ($isLeader) $ids[] = (int)$t->TimID;
            }
        }
        return $ids;
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

        $soloIds = $this->soloTeamIds();

        $teamsQ = DB::table('TIM')
            ->join('KORISNIK', 'TIM.VodjaEmail', '=', 'KORISNIK.Email')
            ->select('TIM.TimID', 'TIM.Naziv', 'TIM.VodjaEmail', 'TIM.Opis', 'KORISNIK.Ime', 'KORISNIK.Prezime')
            ->orderBy('TIM.Naziv');

        if (count($soloIds) > 0) {
            $teamsQ->whereNotIn('TIM.TimID', $soloIds);
        }

        $teams = $teamsQ->get();

        $memberCounts = DB::table('TIM_CLAN')
            ->select('TimID', DB::raw('COUNT(*) as cnt'))
            ->groupBy('TimID')
            ->pluck('cnt', 'TimID');

        $serviceCounts = DB::table('TIM_NUDI_USLUGU')
            ->select('TimID', DB::raw('COUNT(*) as cnt'))
            ->groupBy('TimID')
            ->pluck('cnt', 'TimID');

        $doctors = DB::table('DOKTOR')
            ->join('KORISNIK', 'DOKTOR.Email', '=', 'KORISNIK.Email')
            ->select('DOKTOR.Email', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija')
            ->orderBy('KORISNIK.Prezime')->orderBy('KORISNIK.Ime')
            ->get();

        $services = DB::table('KATALOG_USLUGA')->orderBy('Naziv')->get();

        return view('admin.teams.index', compact('teams', 'memberCounts', 'serviceCounts', 'doctors', 'services'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validateWithBag('teamCreate', [
            'VodjaEmail' => ['required', 'email', 'exists:DOKTOR,Email'],
            'Naziv' => ['required', 'string', 'max:80', 'unique:TIM,Naziv'],
            'Opis' => ['required', 'string', 'max:255'],

            'Doktori' => ['nullable', 'array'],
            'Doktori.*' => ['email', 'exists:DOKTOR,Email'],

            'Usluge' => ['nullable', 'array'],
            'Usluge.*' => ['string', 'exists:KATALOG_USLUGA,Naziv'],
        ], [
            'Naziv.unique' => 'Tim sa ovim nazivom već postoji.',
        ]);

        $leader = $data['VodjaEmail'];
        $otherDoctors = array_values(array_unique(array_filter($data['Doktori'] ?? [], fn($e) => $e && $e !== $leader)));
        $members = array_values(array_unique(array_merge([$leader], $otherDoctors)));

        if (count($members) === 1) {
            return back()
                ->with('err', 'Tim sa jednim članom se ne kreira ručno. Samostalni tim doktora se kreira automatski.')
                ->with('open_modal', 'teamCreate')
                ->withInput();
        }

        $services = array_values(array_unique($data['Usluge'] ?? []));

        try {
            $teamId = DB::transaction(function () use ($data, $leader, $members, $services) {
                $teamId = (int) DB::table('TIM')->insertGetId([
                    'Naziv' => $data['Naziv'],
                    'VodjaEmail' => $leader,
                    'Opis' => $data['Opis'],
                ]);

                foreach ($members as $email) {
                    DB::table('TIM_CLAN')->updateOrInsert(['TimID' => $teamId, 'DoktorEmail' => $email], []);
                }

                foreach ($services as $naziv) {
                    DB::table('TIM_NUDI_USLUGU')->updateOrInsert(['TimID' => $teamId, 'NazivUsluge' => $naziv], []);
                }

                return $teamId;
            });
        } catch (\Throwable $e) {
            return back()->with('err', 'Greška pri kreiranju tima.')->with('open_modal', 'teamCreate')->withInput();
        }

        return redirect()->route('admin.teams.show', ['id' => $teamId])->with('ok', 'Tim je kreiran.');
    }

    public function show(int $id)
    {
        $this->ensureAdmin();

        if ($this->isSoloTeam($id)) {
            return redirect()->route('admin.teams.index')->with('err', 'Samostalni tim doktora se ne uređuje preko Timova.');
        }

        $team = DB::table('TIM')
            ->join('KORISNIK', 'TIM.VodjaEmail', '=', 'KORISNIK.Email')
            ->select('TIM.TimID', 'TIM.Naziv', 'TIM.VodjaEmail', 'TIM.Opis', 'KORISNIK.Ime', 'KORISNIK.Prezime')
            ->where('TIM.TimID', $id)
            ->first();
        abort_unless($team, 404);

        $members = DB::table('TIM_CLAN')
            ->join('KORISNIK', 'TIM_CLAN.DoktorEmail', '=', 'KORISNIK.Email')
            ->leftJoin('DOKTOR', 'TIM_CLAN.DoktorEmail', '=', 'DOKTOR.Email')
            ->select('TIM_CLAN.DoktorEmail', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija')
            ->where('TIM_CLAN.TimID', $id)
            ->orderBy('KORISNIK.Prezime')->orderBy('KORISNIK.Ime')
            ->get();

        $selectedDoctors = $members->pluck('DoktorEmail')->all();

        $teamServices = DB::table('TIM_NUDI_USLUGU')->where('TimID', $id)->orderBy('NazivUsluge')->get();
        $selectedServices = $teamServices->pluck('NazivUsluge')->all();

        $doctors = DB::table('DOKTOR')
            ->join('KORISNIK', 'DOKTOR.Email', '=', 'KORISNIK.Email')
            ->select('DOKTOR.Email', 'KORISNIK.Ime', 'KORISNIK.Prezime', 'DOKTOR.Specijalizacija')
            ->orderBy('KORISNIK.Prezime')->orderBy('KORISNIK.Ime')
            ->get();

        $services = DB::table('KATALOG_USLUGA')->orderBy('Naziv')->get();

        return view('admin.teams.show', compact('team', 'members', 'doctors', 'services', 'selectedDoctors', 'selectedServices'));
    }

    public function update(Request $request, int $id)
    {
        $this->ensureAdmin();

        if ($this->isSoloTeam($id)) {
            return redirect()->route('admin.teams.index')->with('err', 'Samostalni tim doktora se ne uređuje preko Timova.');
        }

        $team = DB::table('TIM')->where('TimID', $id)->first();
        abort_unless($team, 404);

        $data = $request->validateWithBag('teamUpdate', [
            'VodjaEmail' => ['required', 'email', 'exists:DOKTOR,Email'],
            'Naziv' => ['required', 'string', 'max:80', 'unique:TIM,Naziv,' . $id . ',TimID'],
            'Opis' => ['required', 'string', 'max:255'],

            'Doktori' => ['nullable', 'array'],
            'Doktori.*' => ['email', 'exists:DOKTOR,Email'],

            'Usluge' => ['nullable', 'array'],
            'Usluge.*' => ['string', 'exists:KATALOG_USLUGA,Naziv'],
        ]);

        $leader = $data['VodjaEmail'];

        $existingMembers = DB::table('TIM_CLAN')->where('TimID', $id)->pluck('DoktorEmail')->all();

        if ($request->has('MembersIntent')) {
            $memberInput = (array) $request->input('Doktori', []);
        } elseif ($request->has('Doktori')) {
            $memberInput = (array) ($data['Doktori'] ?? []);
        } else {
            $memberInput = $existingMembers;
        }

        $otherDoctors = array_values(array_unique(array_filter($memberInput, fn($e) => $e && $e !== $leader)));
        $members = array_values(array_unique(array_merge([$leader], $otherDoctors)));

        if (count($members) < 2) {
            return back()
                ->with('err', 'Tim mora imati najmanje 2 člana (vođa + bar jedan doktor).')
                ->withInput();
        }

        $existingServices = DB::table('TIM_NUDI_USLUGU')->where('TimID', $id)->pluck('NazivUsluge')->all();

        if ($request->has('ServicesIntent')) {
            $services = (array) $request->input('Usluge', []); // može biti prazno -> obriše sve
        } elseif ($request->has('Usluge')) {
            $services = (array) ($data['Usluge'] ?? []);
        } else {
            $services = $existingServices;
        }

        $services = array_values(array_unique(array_filter($services)));

        DB::transaction(function () use ($id, $data, $leader, $members, $services) {
            DB::table('TIM')->where('TimID', $id)->update([
                'Naziv' => $data['Naziv'],
                'VodjaEmail' => $leader,
                'Opis' => $data['Opis'],
            ]);

            DB::table('TIM_CLAN')->where('TimID', $id)->delete();
            foreach ($members as $email) {
                DB::table('TIM_CLAN')->updateOrInsert(['TimID' => $id, 'DoktorEmail' => $email], []);
            }

            DB::table('TIM_NUDI_USLUGU')->where('TimID', $id)->delete();
            foreach ($services as $naziv) {
                DB::table('TIM_NUDI_USLUGU')->updateOrInsert(['TimID' => $id, 'NazivUsluge' => $naziv], []);
            }
        });

        return back()->with('ok', 'Tim je sačuvan.');
    }

    public function destroy(int $id)
    {
        $this->ensureAdmin();

        if ($this->isSoloTeam($id)) {
            $team = DB::table('TIM')->where('TimID', $id)->first();
            try {
                DB::transaction(function () use ($team) {
                    $this->deleteDoctorCascade($team->VodjaEmail);
                });
            } catch (\Throwable $e) {
                return back()->with('err', 'Samostalni tim nije moguće obrisati (verovatno postoje termini/FK veze).');
            }
            return redirect()->route('admin.doctors.index')->with('ok', 'Samostalni tim je obrisan zajedno sa doktorom.');
        }

        try {
            DB::table('TIM')->where('TimID', $id)->delete();
        } catch (\Throwable $e) {
            return back()->with('err', 'Tim ne može biti obrisan (verovatno je vezan za termine).');
        }

        return redirect()->route('admin.teams.index')->with('ok', 'Tim je obrisan.');
    }
}
