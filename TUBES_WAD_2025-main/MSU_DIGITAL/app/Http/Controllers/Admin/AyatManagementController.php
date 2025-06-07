<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuranApiService; // Asumsi path ini benar
use App\Models\AdminNote;
use App\Models\GlobalKeyword;
use Illuminate\Support\Facades\Validator; // Untuk validasi

class AyatManagementController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        // Anda mungkin ingin menambahkan middleware admin di sini
        // $this->middleware('auth'); // Contoh jika menggunakan auth bawaan Laravel
        // $this->middleware('admin'); // Contoh jika ada middleware admin kustom
    }

    /**
     * Menampilkan daftar ayat dalam satu surat beserta catatan dan kata kunci terkait.
     */
    public function showSurahAyats(Request $request, $surahNumber)
    {
        $editionIdentifier = $request->input('edition', 'quran-uthmani'); 
        $surahDetail = $this->quranApiService->getSurahDetail($surahNumber, $editionIdentifier);

        if (!$surahDetail || !isset($surahDetail['data'])) {
            return back()->withErrors('Gagal mengambil detail surat dari API.');
        }

        $ayats = $surahDetail['data']['ayahs'] ?? [];
        $processedAyats = [];

        foreach ($ayats as $ayat) {
            $apiAyatIdentifier = $surahNumber . ':' . $ayat['numberInSurah'];
            $ayat['api_ayat_identifier'] = $apiAyatIdentifier; // Tambahkan ini untuk kemudahan di Blade
            $ayat['admin_notes'] = AdminNote::where('api_ayat_identifier', $apiAyatIdentifier)->get();
            $ayat['global_keywords'] = GlobalKeyword::where('api_entity_identifier', $apiAyatIdentifier)
                                                ->where('entity_type', 'ayat')
                                                ->get();
            $processedAyats[] = $ayat;
        }
        
        $surahData = $surahDetail['data'];
        // unset($surahData['ayahs']); // Kita mungkin masih butuh informasi ayats asli di surahData, atau tidak.
                                     // Untuk saat ini, biarkan saja. Jika tidak, data ayat hanya ada di processedAyats

        return view('admin.ayat_management.show_surah_ayats', compact('surahData', 'processedAyats', 'surahNumber', 'editionIdentifier'));
    }

    /**
     * Simpan AdminNote baru.
     */
    public function storeAdminNote(Request $request, $apiAyatIdentifier)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            // 'admin_id' => 'nullable|exists:users,id', // Sesuaikan jika perlu
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        AdminNote::create([
            'api_ayat_identifier' => $apiAyatIdentifier,
            'content' => $request->content,
            'admin_id' => auth()->id(), // Mengambil ID admin yang sedang login, pastikan auth sudah di-setup
        ]);

        return back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Update AdminNote yang ada.
     */
    public function updateAdminNote(Request $request, AdminNote $note) // Route model binding
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Optional: Pastikan admin yang mengupdate adalah pemilik atau memiliki hak
        // if ($note->admin_id !== auth()->id() && !auth()->user()->isAdmin()) { // Contoh authorization
        //     return back()->withErrors('Anda tidak berhak mengubah catatan ini.');
        // }

        $note->update(['content' => $request->content]);

        return back()->with('success', 'Catatan berhasil diperbarui.');
    }

    /**
     * Hapus AdminNote.
     */
    public function destroyAdminNote(AdminNote $note) // Route model binding
    {
        // Optional: Pastikan admin yang menghapus adalah pemilik atau memiliki hak
        // if ($note->admin_id !== auth()->id() && !auth()->user()->isAdmin()) { // Contoh authorization
        //     return back()->withErrors('Anda tidak berhak menghapus catatan ini.');
        // }
        
        $note->delete();

        return back()->with('success', 'Catatan berhasil dihapus.');
    }

    /**
     * Simpan GlobalKeyword baru.
     */
    public function storeGlobalKeyword(Request $request, $apiEntityIdentifier, $entityType)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|max:255|unique:global_keywords,keyword,NULL,id,api_entity_identifier,'.$apiEntityIdentifier.',entity_type,'.$entityType,
        ], [
            'keyword.unique' => 'Kata kunci ini sudah ada untuk entitas ini.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        GlobalKeyword::create([
            'keyword' => $request->keyword,
            'api_entity_identifier' => $apiEntityIdentifier, // misal "surah:2" atau "ayat:2:255"
            'entity_type' => $entityType, // 'surah' atau 'ayat'
        ]);

        return back()->with('success', 'Kata kunci global berhasil ditambahkan.');
    }

    /**
     * Hapus GlobalKeyword.
     */
    public function destroyGlobalKeyword(GlobalKeyword $keyword) // Route model binding
    {
        $keyword->delete();
        return back()->with('success', 'Kata kunci global berhasil dihapus.');
    }
}
