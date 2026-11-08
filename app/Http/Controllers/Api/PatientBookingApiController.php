<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientBookingApiController extends Controller
{
    private const STEP_MIN = 15;

    public function services(Request $request)
    {
        $this->ensurePatient($request);

        $services = Cache::remember('booking:services:v1', now()->addMinutes(10), function () {
            return DB::table('KATALOG_USLUGA')
                ->select('Naziv', 'Opis', 'TrajanjeMinuta', 'Cena')
                ->orderBy('Naziv')
                ->get()
                ->map(fn ($service) => [
                    'id' => $service->Naziv,
                    'naziv' => $service->Naziv,
                    'opis' => $service->Opis,
                    'trajanje' => (int) $service->TrajanjeMinuta,
                    'cena' => (float) $service->Cena,
                ]);
        });

        return response()->json(['services' => $services]);
    }

    public function teams(Request $request)
    {
        $this->ensurePatient($request);

        $data = $request->validate([
            'service_id' => ['required'],
        ]);

        $service = $this->resolveService((string) $data['service_id']);
        abort_unless($service, 404);

        $teams = Cache::remember('booking:teams:' . sha1($service->Naziv), now()->addMinutes(5), function () use ($service) {
            return DB::table('TIM_NUDI_USLUGU as TNU')
                ->join('TIM as T', 'TNU.TimID', '=', 'T.TimID')
                ->join('KORISNIK as K', 'T.VodjaEmail', '=', 'K.Email')
                ->leftJoin('TIM_CLAN as TC', 'T.TimID', '=', 'TC.TimID')
                ->where('TNU.NazivUsluge', $service->Naziv)
                ->select(
                    'T.TimID',
                    'T.Naziv',
                    'T.Opis',
                    'T.VodjaEmail',
                    'K.Ime as VodjaIme',
                    'K.Prezime as VodjaPrezime',
                    DB::raw('COUNT(TC.DoktorEmail) as Clanova')
                )
                ->groupBy('T.TimID', 'T.Naziv', 'T.Opis', 'T.VodjaEmail', 'K.Ime', 'K.Prezime')
                ->orderBy('T.Naziv')
                ->get()
                ->map(fn ($team) => [
                    'id' => (int) $team->TimID,
                    'naziv' => $team->Naziv,
                    'opis' => $team->Opis,
                    'vodja' => trim($team->VodjaIme . ' ' . $team->VodjaPrezime),
                    'clanova' => (int) $team->Clanova,
                ]);
        });

        return response()->json(['teams' => $teams]);
    }

    public function days(Request $request)
    {
        $this->ensurePatient($request);

        $data = $request->validate([
            'service_id' => ['required'],
            'team_id' => ['required', 'integer'],
        ]);

        $service = $this->resolveService((string) $data['service_id']);
        abort_unless($service, 404);

        $teamId = (int) $data['team_id'];
        abort_unless($this->teamOffersService($teamId, $service->Naziv), 404);

        $duration = (int) $service->TrajanjeMinuta;
        $days = Cache::remember($this->daysCacheKey($teamId, $duration), now()->addMinute(), function () use ($teamId, $duration) {
            return $this->availableDaysForTeam(
                $teamId,
                $duration,
                Carbon::today(),
                Carbon::today()->addDays(60)
            );
        });

        return response()->json(['days' => $days]);
    }

    public function slots(Request $request)
    {
        $this->ensurePatient($request);

        $data = $request->validate([
            'service_id' => ['required'],
            'team_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $service = $this->resolveService((string) $data['service_id']);
        abort_unless($service, 404);

        $teamId = (int) $data['team_id'];
        abort_unless($this->teamOffersService($teamId, $service->Naziv), 404);

        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();

        $duration = (int) $service->TrajanjeMinuta;
        $slots = Cache::remember($this->slotsCacheKey($teamId, $duration, $date), now()->addSeconds(10), function () use ($teamId, $duration, $date) {
            return $this->availableSlotsForDate($teamId, $duration, $date);
        });

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (($user->Role ?? null) !== 'pacijent') {
            throw ValidationException::withMessages([
                'user' => ['Korisnik mora biti pacijent.'],
            ]);
        }

        $data = $request->validate([
            'service_id' => ['required'],
            'team_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ], [
            'time.regex' => 'Vreme mora biti u formatu HH:MM.',
        ]);

        $service = $this->resolveService((string) $data['service_id']);
        if (!$service) {
            throw ValidationException::withMessages(['service_id' => ['Usluga ne postoji.']]);
        }

        $teamId = (int) $data['team_id'];
        if (!$this->teamOffersService($teamId, $service->Naziv)) {
            throw ValidationException::withMessages(['team_id' => ['Tim ne postoji ili ne radi izabranu uslugu.']]);
        }

        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
        $slots = $this->availableSlotsForDate($teamId, (int) $service->TrajanjeMinuta, $date);

        if (!in_array($data['time'], $slots, true)) {
            return response()->json([
                'message' => 'Izabrani termin nije dostupan.',
            ], 422);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' ' . $data['time']);
        $end = (clone $start)->addMinutes((int) $service->TrajanjeMinuta);

        $terminId = DB::transaction(function () use ($date, $start, $end, $user, $teamId, $service) {
            return DB::table('TERMIN')->insertGetId([
                'Datum' => $date->toDateString(),
                'VremePocetka' => $start->format('H:i:s'),
                'VremeZavrsetka' => $end->format('H:i:s'),
                'Status' => 'Zakazan',
                'DatumOtkazivanja' => null,
                'Razlog' => null,
                'PacijentEmail' => $user->Email,
                'TimID' => $teamId,
                'NazivUsluge' => $service->Naziv,
            ]);
        });

        Cache::forget($this->daysCacheKey($teamId, (int) $service->TrajanjeMinuta));
        Cache::forget($this->slotsCacheKey($teamId, (int) $service->TrajanjeMinuta, $date));

        return response()->json([
            'message' => 'Termin je uspesno zakazan.',
            'termin' => [
                'id' => $terminId,
                'datum' => $date->toDateString(),
                'vreme_pocetka' => $start->format('H:i:s'),
                'vreme_zavrsetka' => $end->format('H:i:s'),
                'status' => 'Zakazan',
            ],
        ], 201);
    }

    private function ensurePatient(Request $request): void
    {
        abort_unless(($request->user()->Role ?? null) === 'pacijent', 403);
    }

    private function resolveService(string $serviceId): ?object
    {
        $service = DB::table('KATALOG_USLUGA')->where('Naziv', $serviceId)->first();
        if ($service) {
            return $service;
        }

        if (ctype_digit($serviceId)) {
            return DB::table('KATALOG_USLUGA')
                ->orderBy('Naziv')
                ->skip(((int) $serviceId) - 1)
                ->first();
        }

        return null;
    }

    private function daysCacheKey(int $teamId, int $duration): string
    {
        return sprintf('booking:days:%s:%d:%d', Carbon::today()->toDateString(), $teamId, $duration);
    }

    private function slotsCacheKey(int $teamId, int $duration, Carbon $date): string
    {
        return sprintf('booking:slots:%s:%d:%d', $date->toDateString(), $teamId, $duration);
    }

    private function teamOffersService(int $teamId, string $serviceName): bool
    {
        return DB::table('TIM_NUDI_USLUGU')
            ->where('TimID', $teamId)
            ->where('NazivUsluge', $serviceName)
            ->exists();
    }

    private function availableDaysForTeam(int $teamId, int $durationMin, Carbon $from, Carbon $to): array
    {
        $doctors = $this->teamDoctors($teamId);
        if (count($doctors) === 0) {
            return [];
        }

        $availMap = $this->doctorAvailabilityMap($doctors);
        if (count($availMap) !== count($doctors)) {
            return [];
        }

        $teamTimIds = $this->relatedTeamIds($doctors);

        $terms = DB::table('TERMIN')
            ->select('Datum', 'VremePocetka', 'VremeZavrsetka')
            ->whereIn('TimID', $teamTimIds)
            ->whereBetween('Datum', [$from->toDateString(), $to->toDateString()])
            ->where('Status', '!=', 'Otkazan')
            ->get();

        $busyByDate = [];
        foreach ($terms as $term) {
            $busyByDate[$term->Datum][] = [
                $this->toMinutes($term->VremePocetka),
                $this->toMinutes($term->VremeZavrsetka),
            ];
        }

        $off = DB::table('DOKTOR_NERADNI_DAN')
            ->select('Datum')
            ->whereIn('DoktorEmail', $doctors)
            ->whereBetween('Datum', [$from->toDateString(), $to->toDateString()])
            ->get();

        $offByDate = [];
        foreach ($off as $day) {
            $offByDate[$day->Datum] = true;
        }

        $out = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dateString = $current->toDateString();

            if (!empty($offByDate[$dateString])) {
                $current->addDay();
                continue;
            }

            foreach ($doctors as $doctor) {
                if (!$this->isWorkingDay($availMap[$doctor]['RadniDan'], $current)) {
                    $current->addDay();
                    continue 2;
                }
            }

            [$windowStart, $windowEnd] = $this->intersectionWindow($doctors, $availMap);
            $busy = $this->mergeIntervals($busyByDate[$dateString] ?? []);

            if (count($this->generateSlots($current, $windowStart, $windowEnd, $busy, $durationMin)) > 0) {
                $out[] = $dateString;
            }

            $current->addDay();
        }

        return $out;
    }

    private function availableSlotsForDate(int $teamId, int $durationMin, Carbon $date): array
    {
        $doctors = $this->teamDoctors($teamId);
        if (count($doctors) === 0) {
            return [];
        }

        $availMap = $this->doctorAvailabilityMap($doctors);
        if (count($availMap) !== count($doctors)) {
            return [];
        }

        $off = DB::table('DOKTOR_NERADNI_DAN')
            ->whereIn('DoktorEmail', $doctors)
            ->where('Datum', $date->toDateString())
            ->exists();

        if ($off) {
            return [];
        }

        foreach ($doctors as $doctor) {
            if (!$this->isWorkingDay($availMap[$doctor]['RadniDan'], $date)) {
                return [];
            }
        }

        [$windowStart, $windowEnd] = $this->intersectionWindow($doctors, $availMap);
        if ($windowEnd - $windowStart < $durationMin) {
            return [];
        }

        $terms = DB::table('TERMIN')
            ->select('VremePocetka', 'VremeZavrsetka')
            ->whereIn('TimID', $this->relatedTeamIds($doctors))
            ->where('Datum', $date->toDateString())
            ->where('Status', '!=', 'Otkazan')
            ->get();

        $busy = [];
        foreach ($terms as $term) {
            $busy[] = [$this->toMinutes($term->VremePocetka), $this->toMinutes($term->VremeZavrsetka)];
        }

        return $this->generateSlots($date, $windowStart, $windowEnd, $this->mergeIntervals($busy), $durationMin);
    }

    private function teamDoctors(int $teamId): array
    {
        return DB::table('TIM_CLAN')->where('TimID', $teamId)->pluck('DoktorEmail')->all();
    }

    private function relatedTeamIds(array $doctors): array
    {
        return DB::table('TIM_CLAN')
            ->whereIn('DoktorEmail', $doctors)
            ->distinct()
            ->pluck('TimID')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function doctorAvailabilityMap(array $doctors): array
    {
        $rows = DB::table('DOKTOR_RASPOLOZIVOST')->whereIn('DoktorEmail', $doctors)->get();
        $map = [];

        foreach ($rows as $row) {
            $map[$row->DoktorEmail] = [
                'RadniDan' => (string) $row->RadniDan,
                'VremeOd' => (string) $row->VremeOd,
                'VremeDo' => (string) $row->VremeDo,
            ];
        }

        return $map;
    }

    private function intersectionWindow(array $doctors, array $availMap): array
    {
        $start = 0;
        $end = 24 * 60;

        foreach ($doctors as $doctor) {
            $start = max($start, $this->toMinutes($availMap[$doctor]['VremeOd']));
            $end = min($end, $this->toMinutes($availMap[$doctor]['VremeDo']));
        }

        return [$start, $end];
    }

    private function generateSlots(Carbon $date, int $windowStart, int $windowEnd, array $busy, int $durationMin): array
    {
        if ($date->isBefore(Carbon::today())) {
            return [];
        }

        $time = (int) (ceil($windowStart / self::STEP_MIN) * self::STEP_MIN);
        $lastStart = $windowEnd - $durationMin;

        if ($date->isSameDay(Carbon::now())) {
            $nowMin = (Carbon::now()->hour * 60) + Carbon::now()->minute;
            $time = max($time, (int) (ceil($nowMin / self::STEP_MIN) * self::STEP_MIN));
        }

        $slots = [];

        while ($time <= $lastStart) {
            $slotEnd = $time + $durationMin;
            $available = true;

            foreach ($busy as [$busyStart, $busyEnd]) {
                if ($time < $busyEnd && $slotEnd > $busyStart) {
                    $available = false;
                    break;
                }
            }

            if ($available) {
                $slots[] = $this->minToHm($time);
            }

            $time += self::STEP_MIN;
        }

        return $slots;
    }

    private function mergeIntervals(array $intervals): array
    {
        if (count($intervals) === 0) {
            return [];
        }

        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

        $out = [];
        [$currentStart, $currentEnd] = $intervals[0];

        foreach ($intervals as [$start, $end]) {
            if ($start <= $currentEnd) {
                $currentEnd = max($currentEnd, $end);
            } else {
                $out[] = [$currentStart, $currentEnd];
                [$currentStart, $currentEnd] = [$start, $end];
            }
        }

        $out[] = [$currentStart, $currentEnd];

        return $out;
    }

    private function toMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    private function minToHm(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function isWorkingDay(string $workingDays, Carbon $date): bool
    {
        $allowed = $this->parseWorkingDays($workingDays);

        return in_array((int) $date->isoWeekday(), $allowed, true);
    }

    private function parseWorkingDays(string $value): array
    {
        $value = trim($value);

        $map = [
            'Pon' => 1,
            'Uto' => 2,
            'Sre' => 3,
            'Cet' => 4,
            'Pet' => 5,
            'Sub' => 6,
            'Ned' => 7,
            'pon' => 1,
            'uto' => 2,
            'sre' => 3,
            'cet' => 4,
            'pet' => 5,
            'sub' => 6,
            'ned' => 7,
        ];

        if (str_contains($value, '-')) {
            [$from, $to] = array_map('trim', explode('-', $value, 2));
            $start = $map[$from] ?? null;
            $end = $map[$to] ?? null;

            if ($start && $end) {
                return range($start, $end);
            }
        }

        if (str_contains($value, ',')) {
            $days = [];
            foreach (explode(',', $value) as $part) {
                $part = trim($part);
                if (isset($map[$part])) {
                    $days[] = $map[$part];
                }
            }

            return array_values(array_unique($days));
        }

        return isset($map[$value]) ? [$map[$value]] : [1, 2, 3, 4, 5, 6];
    }
}
