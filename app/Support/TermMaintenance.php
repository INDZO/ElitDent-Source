<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TermMaintenance
{
    public static function cancelExpiredUnrealized(string $reason = 'Termin nije realizovan na vreme.'): int
    {
        $today = Carbon::today()->toDateString();

        return DB::table('TERMIN')
            ->where('Status', 'Zakazan')
            ->where('Datum', '<', $today)
            ->update([
                'Status' => 'Otkazan',
                'DatumOtkazivanja' => $today,
                'Razlog' => mb_substr($reason, 0, 200),
            ]);
    }

    public static function cancelTermsForDoctorOffDay(string $doctorEmail, string $dateYmd, ?string $offDayReason = null): int
    {
        $today = Carbon::today()->toDateString();

        $teamIds = DB::table('TIM_CLAN')
            ->where('DoktorEmail', $doctorEmail)
            ->pluck('TimID')
            ->map(fn($x) => (int)$x)
            ->all();

        if (count($teamIds) === 0) return 0;

        $reason = 'Neradni dan doktora.';
        $r = trim((string)$offDayReason);
        if ($r !== '') $reason .= ' ' . $r;

        return DB::table('TERMIN')
            ->where('Datum', $dateYmd)
            ->where('Status', 'Zakazan')
            ->whereIn('TimID', $teamIds)
            ->update([
                'Status' => 'Otkazan',
                'DatumOtkazivanja' => $today,
                'Razlog' => mb_substr($reason, 0, 200),
            ]);
    }
}
