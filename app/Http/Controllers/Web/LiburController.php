<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Libur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LiburController extends Controller
{
    public function index()
    {
        $liburs = Libur::orderBy('tanggal', 'desc')->paginate(15);
        return view('libur.index', compact('liburs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:liburs,tanggal',
            'nama_libur' => 'required|string|max:100',
        ]);

        Libur::create($request->all());

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2024|max:2035',
            'sertakan_cuti_bersama' => 'nullable|boolean',
        ]);

        $tahun = (int) $request->input('tahun');
        $sertakanCutiBersama = $request->boolean('sertakan_cuti_bersama');
        $url = 'https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

        try {
            $response = Http::timeout(15)->get($url);
            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil data kalender libur nasional dari Google Calendar.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Tidak dapat terhubung ke server kalender libur: ' . $e->getMessage());
        }

        $body = str_replace(["\r\n ", "\r\n\t"], '', $response->body());
        $body = str_replace("\r\n", "\n", $body);

        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $body, $matches);

        $ditambahkan = 0;
        $sudahAda = 0;

        foreach ($matches[1] ?? [] as $event) {
            if (!preg_match('/DTSTART;VALUE=DATE:(\d{8})/', $event, $dMatch)) {
                continue;
            }
            if (!preg_match('/SUMMARY:(.+)/', $event, $sMatch)) {
                continue;
            }

            $rawDate = $dMatch[1];
            if ((int) substr($rawDate, 0, 4) !== $tahun) {
                continue;
            }

            $desc = preg_match('/DESCRIPTION:(.+)/', $event, $descMatch) ? trim($descMatch[1]) : '';
            if (stripos($desc, 'Hari libur nasional') === false && stripos($desc, 'Public holiday') === false) {
                continue;
            }

            $summary = trim($sMatch[1]);
            $summary = str_replace(['\,', '\;', '\:', '\\\\'], [',', ';', ':', '\\'], $summary);

            if (!$sertakanCutiBersama && stripos($summary, 'Cuti Bersama') !== false) {
                continue;
            }

            $tanggal = substr($rawDate, 0, 4) . '-' . substr($rawDate, 4, 2) . '-' . substr($rawDate, 6, 2);
            $namaLibur = Str::limit($summary, 100, '');

            $record = Libur::firstOrCreate(
                ['tanggal' => $tanggal],
                ['nama_libur' => $namaLibur]
            );

            if ($record->wasRecentlyCreated) {
                $ditambahkan++;
            } else {
                $sudahAda++;
            }
        }

        if ($ditambahkan === 0 && $sudahAda === 0) {
            return back()->with('error', "Data hari libur nasional untuk tahun {$tahun} belum tersedia di kalender.");
        }

        return back()->with('success', "Sinkronisasi {$tahun} selesai: {$ditambahkan} hari libur baru ditambahkan ({$sudahAda} sudah ada).");
    }

    public function destroy(Libur $libur)
    {
        $libur->delete();
        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
