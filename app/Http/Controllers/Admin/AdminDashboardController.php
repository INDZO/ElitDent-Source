<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'admin', 403);
    }

    public function index()
    {
        $this->ensureAdmin();

        $totalTerms    = DB::table('TERMIN')->count();
        $totalDoctors  = DB::table('DOKTOR')->count();
        $totalPatients = DB::table('PACIJENT')->count();
        $totalTeams    = DB::table('TIM')->count();
        $totalServices = DB::table('KATALOG_USLUGA')->count();

        $today = Carbon::today()->toDateString();
        $todayTerms = DB::table('TERMIN')->where('Datum', $today)->count();

        $statusCounts = DB::table('TERMIN')
            ->select('Status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('Status')
            ->orderByDesc('cnt')
            ->get();

        $maxStatus = max(1, (int) ($statusCounts->max('cnt') ?? 1));

        $statusBars = $statusCounts->map(function ($s) use ($maxStatus) {
            $cnt = (int)$s->cnt;
            return [
                'status' => (string)$s->Status,
                'cnt' => $cnt,
                'pct' => (int) round(($cnt / $maxStatus) * 100),
            ];
        })->values();

        $from = Carbon::today()->subDays(6)->toDateString();
        $to   = Carbon::today()->toDateString();

        $perDayMap = DB::table('TERMIN')
            ->select('Datum', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('Datum', [$from, $to])
            ->groupBy('Datum')
            ->pluck('cnt', 'Datum')
            ->toArray();

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $c = Carbon::today()->subDays($i);
            $date = $c->toDateString();
            $days[] = $date;
        }

        $maxDay = 1;
        foreach ($days as $d) {
            $maxDay = max($maxDay, (int)($perDayMap[$d] ?? 0));
        }

        $spark = [];
        for ($i = 6; $i >= 0; $i--) {
            $c = Carbon::today()->subDays($i);
            $date = $c->toDateString();
            $cnt = (int)($perDayMap[$date] ?? 0);
            $h = (int) max(10, round(($cnt / $maxDay) * 140));
            $spark[] = [
                'label' => $c->format('d.m'),
                'cnt' => $cnt,
                'h' => $h,
            ];
        }

        return view('admin.dashboard', compact(
            'totalTerms',
            'totalDoctors',
            'totalPatients',
            'totalTeams',
            'totalServices',
            'todayTerms',
            'statusBars',
            'spark'
        ));
    }
}
