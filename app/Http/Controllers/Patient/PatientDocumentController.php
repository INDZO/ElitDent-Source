<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientDocumentController extends Controller
{
    private function ensurePatient(): void
    {
        abort_unless(Auth::check() && (Auth::user()->Role ?? null) === 'pacijent', 403);
    }

    public function store(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $data = $request->validate([
            'Naziv' => ['nullable', 'string', 'max:80'],
            'file'  => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'Napomena' => ['nullable', 'string', 'max:120'],
        ], [
            'file.required' => 'Izaberi dokument.',
            'file.mimes' => 'Dozvoljeni formati su JPG, PNG i PDF.',
            'file.max' => 'Maksimalna veličina fajla je 10MB.',
        ]);

        $file = $request->file('file');
        $bytes = file_get_contents($file->getRealPath());
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $size = (int) $file->getSize();
        $orig = $file->getClientOriginalName();

        $putanja = 'db:' . (string) Str::uuid();
        $naziv = trim((string)($data['Naziv'] ?? ''));
        if ($naziv === '') $naziv = pathinfo($orig, PATHINFO_FILENAME) ?: 'Dokument';

        $tip = (str_starts_with((string)$mime, 'image/')) ? 'slika' : 'pdf';

        DB::table('DOKUMENT')->insert([
            'PacijentEmail' => $u->Email,
            'Putanja' => $putanja,
            'Naziv' => $naziv,
            'Tip' => $tip,
            'OriginalnoIme' => $orig,
            'MimeType' => $mime,
            'Velicina' => $size,
            'Sadrzaj' => $bytes,
            'DodaoEmail' => $u->Email,
            'DatumDodavanja' => now()->toDateString(),
            'Napomena' => $data['Napomena'] ?? null,
        ]);

        return back()->with('ok', 'Dokument je dodat.');
    }

    private function getPutanja(Request $request): string
    {
        return (string)($request->query('putanja') ?? $request->input('putanja') ?? '');
    }

    public function view(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $putanja = $this->getPutanja($request);
        abort_if($putanja === '', 404);

        $doc = DB::table('DOKUMENT')
            ->where('PacijentEmail', $u->Email)
            ->where('Putanja', $putanja)
            ->first();

        abort_unless($doc, 404);

        $mime = $doc->MimeType ?: 'application/octet-stream';
        $disposition = (str_starts_with($mime, 'image/') || $mime === 'application/pdf') ? 'inline' : 'attachment';

        return response($doc->Sadrzaj)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', $disposition . '; filename="' . ($doc->OriginalnoIme ?? 'dokument') . '"');
    }

    public function download(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $putanja = $this->getPutanja($request);
        abort_if($putanja === '', 404);

        $doc = DB::table('DOKUMENT')
            ->where('PacijentEmail', $u->Email)
            ->where('Putanja', $putanja)
            ->first();

        abort_unless($doc, 404);

        return response($doc->Sadrzaj)
            ->header('Content-Type', $doc->MimeType ?: 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . ($doc->OriginalnoIme ?? 'dokument') . '"');
    }

    public function destroy(Request $request)
    {
        $this->ensurePatient();
        $u = Auth::user();

        $putanja = $this->getPutanja($request);
        abort_if($putanja === '', 404);

        DB::table('DOKUMENT')
            ->where('PacijentEmail', $u->Email)
            ->where('Putanja', $putanja)
            ->delete();

        return back()->with('ok', 'Dokument je obrisan.');
    }
}
