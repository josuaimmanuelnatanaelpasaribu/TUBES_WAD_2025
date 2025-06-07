<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuranApiService;
use App\Models\SuratCustomData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuratManagementController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        // Middleware admin sudah diterapkan pada grup rute, jadi tidak perlu di sini kecuali ada kebutuhan khusus
    }

    /**
     * Menampilkan daftar semua surat beserta data kustomnya.
     */
    public function index()
    {
        $apiSurahsResponse = $this->quranApiService->getSurahList();
        $suratList = [];

        if (isset($apiSurahsResponse['success']) && $apiSurahsResponse['success']) {
            $apiSurahs = $apiSurahsResponse['data'];
            $customData = SuratCustomData::pluck('custom_description', 'surat_number');

            foreach ($apiSurahs as $apiSurah) {
                $suratList[] = [
                    'number' => $apiSurah['number'],
                    'name' => $apiSurah['name'], // Nama Arab
                    'englishName' => $apiSurah['englishName'], // Nama Inggris
                    'revelationType' => $apiSurah['revelationType'],
                    'numberOfAyahs' => $apiSurah['numberOfAyahs'],
                    'custom_description' => $customData->get($apiSurah['number'])
                ];
            }
        } else {
            Log::error('Failed to get surah list for Surat Management: ' . ($apiSurahsResponse['message'] ?? 'Unknown API error'));
            session()->flash('error', 'Gagal mengambil daftar surat dari API. Coba lagi nanti.');
        }

        return view('admin.surat_management.index', compact('suratList'));
    }

    /**
     * Menampilkan form untuk mengedit deskripsi kustom surat tertentu.
     */
    public function edit($surahNumber)
    {
        $apiSurahResponse = $this->quranApiService->getSurahDetail((int)$surahNumber);
        if (!isset($apiSurahResponse['success']) || !$apiSurahResponse['success'] || !isset($apiSurahResponse['data']['number'])) {
            Log::error("Failed to get surah details for editing custom data, surah: {$surahNumber}", [$apiSurahResponse]);
            return redirect()->route('admin.surats.index')->with('error', 'Gagal mengambil detail surat dari API.');
        }
        
        $surahDetail = $apiSurahResponse['data'];
        $customData = SuratCustomData::firstOrNew(['surat_number' => $surahNumber]);

        return view('admin.surat_management.edit', compact('surahDetail', 'customData'));
    }

    /**
     * Menyimpan atau memperbarui deskripsi kustom untuk surat tertentu.
     */
    public function update(Request $request, $surahNumber)
    {
        $validator = Validator::make($request->all(), [
            'custom_description' => 'nullable|string|max:5000', // Max 5000 karakter
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        SuratCustomData::updateOrCreate(
            ['surat_number' => (int)$surahNumber],
            ['custom_description' => $request->custom_description]
        );

        return redirect()->route('admin.surats.index')->with('success', 'Deskripsi kustom untuk Surat No. ' . $surahNumber . ' berhasil disimpan.');
    }
}
