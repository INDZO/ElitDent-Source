<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientBookingController extends Controller
{
    private const STEP_MIN = 15;

    private function ensurePatient(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'pacijent', 403);
    }

    public function index()
    {
        $this->ensurePatient();

        $services = DB::table('KATALOG_USLUGA')
            ->select('Naziv', 'Opis', 'TrajanjeMinuta', 'Cena')
            ->orderBy('Naziv')
            ->get();

        $rows = DB::table('TIM_NUDI_USLUGU as TNU')
            ->join('TIM as T', 'TNU.TimID', '=', 'T.TimID')
            ->join('KORISNIK as K', 'T.VodjaEmail', '=', 'K.Email')
            ->leftJoin('TIM_CLAN as TC', 'T.TimID', '=', 'TC.TimID')
            ->select(
                'TNU.NazivUsluge',
                'T.TimID',
                'T.Naziv as TimNaziv',
                'T.VodjaEmail',
                'K.Ime as VodjaIme',
                'K.Prezime as VodjaPrezime',
                DB::raw('COUNT(TC.DoktorEmail) as Clanova')
            )
            ->groupBy('TNU.NazivUsluge', 'T.TimID', 'T.Naziv', 'T.VodjaEmail', 'K.Ime', 'K.Prezime')
            ->orderBy('T.TimID')
            ->get();

        $teamsByService = [];
        foreach ($rows as $r) {
            $teamsByService[$r->NazivUsluge][] = [
                'TimID' => (int)$r->TimID,
                'Naziv' => $r->TimNaziv,
                'Vodja' => trim($r->VodjaIme . ' ' . $r->VodjaPrezime),
                'Clanova' => (int)$r->Clanova,
            ];
        }

        return view('patient.booking', [
            'services' => $services,
            'teamsByService' => $teamsByService,
        ]);
    }

    public function days(Request $request)
    {
        $this->ensurePatient();

        $data = $request->validate([
            'usluga' => ['required', 'string'],
            'tim' => ['required', 'integer'],
        ]);

        $svc = DB::table('KATALOG_USLUGA')->where('Naziv', $data['usluga'])->first();
        abort_unless($svc, 404);

        $duration = (int) $svc->TrajanjeMinuta;
        $timId = (int) $data['tim'];

        $from = Carbon::today();
        $to = Carbon::today()->addDays(60);

        $days = $this->availableDaysForTeam($timId, $duration, $from, $to);

        return response()->json(['days' => $days]);
    }

    public function slots(Request $request)
    {
        $this->ensurePatient();

        $data = $request->validate([
            'usluga' => ['required', 'string'],
            'tim' => ['required', 'integer'],
            'datum' => ['required', 'date'],
        ]);

        $svc = DB::table('KATALOG_USLUGA')->where('Naziv', $data['usluga'])->first();
        abort_unless($svc, 404);

        $duration = (int) $svc->TrajanjeMinuta;
        $timId = (int) $data['tim'];
        $date = Carbon::parse($data['datum'])->startOfDay();

        $slots = $this->availableSlotsForDate($timId, $duration, $date);

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request)
    {
        $this->ensurePatient();

        $u = Auth::user();

        $data = $request->validate([
            'usluga' => ['required', 'string'],
            'tim' => ['required', 'integer'],
            'datum' => ['required', 'date'],
            'vreme' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ], [
            'vreme.regex' => 'Vreme mora biti u formatu HH:MM.',
        ]);

        $svc = DB::table('KATALOG_USLUGA')->where('Naziv', $data['usluga'])->first();
        abort_unless($svc, 404);

        $duration = (int) $svc->TrajanjeMinuta;
        $timId = (int) $data['tim'];
        $date = Carbon::parse($data['datum'])->startOfDay();

        $slots = $this->availableSlotsForDate($timId, $duration, $date);
        abort_unless(in_array($data['vreme'], $slots, true), 422);

        $start = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' ' . $data['vreme']);
        $end = (clone $start)->addMinutes($duration);

        DB::table('TERMIN')->insert([
            'Datum' => $date->toDateString(),
            'VremePocetka' => $start->format('H:i:s'),
            'VremeZavrsetka' => $end->format('H:i:s'),
            'Status' => 'Zakazan',
            'DatumOtkazivanja' => null,
            'Razlog' => null,
            'PacijentEmail' => $u->Email,
            'TimID' => $timId,
            'NazivUsluge' => $data['usluga'],
        ]);

        return redirect()->route('patient.appointments')->with('ok', 'Termin je uspešno zakazan.');
    }

    private function availableDaysForTeam(int $timId, int $durationMin, Carbon $from, Carbon $to): array
    {
        $doctors = $this->teamDoctors($timId);
        if (count($doctors) === 0) return [];

        $availMap = $this->doctorAvailabilityMap($doctors);
        if (count($availMap) !== count($doctors)) return []; // neko nema raspoloživost

        $teamTimIds = DB::table('TIM_CLAN')->whereIn('DoktorEmail', $doctors)->distinct()->pluck('TimID')->map(fn($x) => (int)$x)->all();

        $terms = DB::table('TERMIN')
            ->select('Datum', 'VremePocetka', 'VremeZavrsetka', 'Status')
            ->whereIn('TimID', $teamTimIds)
            ->whereBetween('Datum', [$from->toDateString(), $to->toDateString()])
            ->where('Status', '!=', 'Otkazan')
            ->get();

        $busyByDate = [];
        foreach ($terms as $t) {
            $d = $t->Datum;
            $busyByDate[$d][] = [$this->toMinutes($t->VremePocetka), $this->toMinutes($t->VremeZavrsetka)];
        }

        $off = DB::table('DOKTOR_NERADNI_DAN')
            ->select('DoktorEmail', 'Datum')
            ->whereIn('DoktorEmail', $doctors)
            ->whereBetween('Datum', [$from->toDateString(), $to->toDateString()])
            ->get();

        $offByDate = [];
        foreach ($off as $o) {
            $offByDate[$o->Datum] = true;
        }

        $out = [];
        $cur = $from->copy();
        while ($cur->lte($to)) {
            $ds = $cur->toDateString();

            if (!empty($offByDate[$ds])) {
                $cur->addDay();
                continue;
            }

            $ok = true;
            foreach ($doctors as $doc) {
                if (!$this->isWorkingDay($availMap[$doc]['RadniDan'], $cur)) {
                    $ok = false;
                    break;
                }
            }
            if (!$ok) {
                $cur->addDay();
                continue;
            }

            [$winStart, $winEnd] = $this->intersectionWindow($doctors, $availMap);
            if ($winEnd - $winStart < $durationMin) {
                $cur->addDay();
                continue;
            }

            $busy = $this->mergeIntervals($busyByDate[$ds] ?? []);
            $slots = $this->generateSlots($cur, $winStart, $winEnd, $busy, $durationMin);

            if (count($slots) > 0) $out[] = $ds;
            $cur->addDay();
        }

        return $out;
    }

    private function availableSlotsForDate(int $timId, int $durationMin, Carbon $date): array
    {
        $doctors = $this->teamDoctors($timId);
        if (count($doctors) === 0) return [];

        $availMap = $this->doctorAvailabilityMap($doctors);
        if (count($availMap) !== count($doctors)) return [];

        $off = DB::table('DOKTOR_NERADNI_DAN')
            ->whereIn('DoktorEmail', $doctors)
            ->where('Datum', $date->toDateString())
            ->exists();
        if ($off) return [];

        foreach ($doctors as $doc) {
            if (!$this->isWorkingDay($availMap[$doc]['RadniDan'], $date)) return [];
        }

        [$winStart, $winEnd] = $this->intersectionWindow($doctors, $availMap);
        if ($winEnd - $winStart < $durationMin) return [];

        $teamTimIds = DB::table('TIM_CLAN')->whereIn('DoktorEmail', $doctors)->distinct()->pluck('TimID')->map(fn($x) => (int)$x)->all();

        $terms = DB::table('TERMIN')
            ->select('VremePocetka', 'VremeZavrsetka')
            ->whereIn('TimID', $teamTimIds)
            ->where('Datum', $date->toDateString())
            ->where('Status', '!=', 'Otkazan')
            ->get();

        $busy = [];
        foreach ($terms as $t) {
            $busy[] = [$this->toMinutes($t->VremePocetka), $this->toMinutes($t->VremeZavrsetka)];
        }
        $busy = $this->mergeIntervals($busy);

        return $this->generateSlots($date, $winStart, $winEnd, $busy, $durationMin);
    }

    private function teamDoctors(int $timId): array
    {
        return DB::table('TIM_CLAN')->where('TimID', $timId)->pluck('DoktorEmail')->all();
    }

    private function doctorAvailabilityMap(array $doctors): array
    {
        $rows = DB::table('DOKTOR_RASPOLOZIVOST')->whereIn('DoktorEmail', $doctors)->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->DoktorEmail] = [
                'RadniDan' => (string)$r->RadniDan,
                'VremeOd' => (string)$r->VremeOd,
                'VremeDo' => (string)$r->VremeDo,
            ];
        }
        return $map;
    }

    private function intersectionWindow(array $doctors, array $availMap): array
    {
        $start = 0;
        $end = 24 * 60;

        foreach ($doctors as $d) {
            $s = $this->toMinutes($availMap[$d]['VremeOd']);
            $e = $this->toMinutes($availMap[$d]['VremeDo']);
            $start = max($start, $s);
            $end = min($end, $e);
        }
        return [$start, $end];
    }

    private function generateSlots(Carbon $date, int $winStart, int $winEnd, array $busy, int $durationMin): array
    {
        $step = self::STEP_MIN;

        $t = (int)(ceil($winStart / $step) * $step);
        $lastStart = $winEnd - $durationMin;

        $now = Carbon::now();
        if ($date->isSameDay($now)) {
            $nowMin = $now->hour * 60 + $now->minute;
            $t = max($t, (int)(ceil($nowMin / $step) * $step));
        }
        if ($date->isBefore($now->copy()->startOfDay())) return [];

        $slots = [];
        while ($t <= $lastStart) {
            $ok = true;
            $tEnd = $t + $durationMin;

            foreach ($busy as [$bs, $be]) {
                if ($t < $be && $tEnd > $bs) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) $slots[] = $this->minToHm($t);
            $t += $step;
        }

        return $slots;
    }

    private function mergeIntervals(array $arr): array
    {
        if (count($arr) === 0) return [];
        usort($arr, fn($a, $b) => $a[0] <=> $b[0]);

        $out = [];
        [$cs, $ce] = $arr[0];
        foreach ($arr as [$s, $e]) {
            if ($s <= $ce) $ce = max($ce, $e);
            else {
                $out[] = [$cs, $ce];
                [$cs, $ce] = [$s, $e];
            }
        }
        $out[] = [$cs, $ce];
        return $out;
    }

    private function toMinutes(string $time): int
    {
        $parts = explode(':', $time);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        return $h * 60 + $m;
    }

    private function minToHm(int $min): string
    {
        $h = intdiv($min, 60);
        $m = $min % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    private function isWorkingDay(string $radniDan, Carbon $date): bool
    {
        $allowed = $this->parseRadniDan($radniDan);
        $dow = (int) $date->isoWeekday();
        return in_array($dow, $allowed, true);
    }

    private function parseRadniDan(string $s): array
    {
        $s = trim($s);

        $map = [
            'Pon' => 1,
            'Uto' => 2,
            'Sre' => 3,
            'Cet' => 4,
            'Čet' => 4,
            'Pet' => 5,
            'Sub' => 6,
            'Ned' => 7,
            'pon' => 1,
            'uto' => 2,
            'sre' => 3,
            'cet' => 4,
            'čet' => 4,
            'pet' => 5,
            'sub' => 6,
            'ned' => 7,
        ];

        if (str_contains($s, '-')) {
            [$a, $b] = array_map('trim', explode('-', $s, 2));
            $sa = $map[$a] ?? null;
            $sb = $map[$b] ?? null;
            if ($sa && $sb) {
                $out = [];
                for ($i = $sa; $i <= $sb; $i++) $out[] = $i;
                return $out;
            }
        }

        if (str_contains($s, ',')) {
            $out = [];
            foreach (explode(',', $s) as $p) {
                $p = trim($p);
                if (isset($map[$p])) $out[] = $map[$p];
            }
            return array_values(array_unique($out));
        }

        if (isset($map[$s])) return [$map[$s]];

        return [1, 2, 3, 4, 5, 6];
    }
}
