<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QuranApiService; // Diubah untuk menggunakan service
use App\Models\User\Bookmark;    // Model Bookmark dari database
use App\Models\User\PersonalNote; // Model PersonalNote dari database
use App\Models\User; // Model User, pastikan ini path yang benar ke User model Anda
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Support\Facades\Session; // Masih bisa digunakan untuk pesan flash

class QuranController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        // Middleware 'auth' sudah diterapkan di routes/web.php
    }

    /**
     * Menampilkan halaman utama aplikasi Quran (daftar surah).
     */
    public function index()
    {
        $response = $this->quranApiService->getSurahList();
        $surahs = [];
        if (isset($response['success']) && $response['success']) {
            $surahs = $response['data'];
        } else {
            Log::error('Failed to get surah list for home page: ' . ($response['message'] ?? 'Unknown error from QuranApiService'));
            session()->flash('error', 'Gagal memuat daftar surah. Coba beberapa saat lagi.');
        }

        $lastRead = null;
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->last_read_surah && $user->last_read_ayah) {
                $surahName = $this->quranApiService->getSurahName((int)$user->last_read_surah);
                $lastRead = [
                    'surah_number' => $user->last_read_surah,
                    'ayah_number' => $user->last_read_ayah,
                    'surah_name' => $surahName ?? ('Surah ' . $user->last_read_surah)
                ];
            }
        }
            
        return view('user.quran.index', compact('surahs', 'lastRead'));
    }

    /**
     * Menampilkan detail sebuah surah.
     */
    public function showSurah(Request $request, $surahNumber)
    {
        $user = Auth::user();
        $editionIdentifier = session('active_translation_edition', config('quran_cloud.default_translation_edition', 'en.sahih'));

        $surahDataResponse = $this->quranApiService->getSurahDetail((int)$surahNumber, 'quran-uthmani');
        $translationDataResponse = $this->quranApiService->getSurahDetail((int)$surahNumber, $editionIdentifier);

        if (!$surahDataResponse['success'] || !$translationDataResponse['success']) {
            Log::error("Failed to get surah/translation for surah {$surahNumber}.", [
                'surah_response' => $surahDataResponse,
                'translation_response' => $translationDataResponse
            ]);
            return redirect()->route('home')->with('error', 'Gagal memuat detail surah atau terjemahan.');
        }

        $surahDetails = $surahDataResponse['data'];
        $translationDetails = $translationDataResponse['data'];

        // Kumpulkan semua api_ayat_identifier untuk query batch
        $apiAyatIdentifiers = [];
        if (isset($surahDetails['ayahs']) && is_array($surahDetails['ayahs'])) {
            foreach ($surahDetails['ayahs'] as $ayah) {
                $apiAyatIdentifiers[] = $surahDetails['number'] . ':' . $ayah['numberInSurah'];
            }
        }

        // Ambil bookmark dan notes dalam satu query jika memungkinkan atau dua query terpisah
        $userBookmarks = Bookmark::where('user_id', $user->id)
                                ->whereIn('api_ayat_identifier', $apiAyatIdentifiers)
                                ->pluck('api_ayat_identifier')
                                ->flip(); // flip untuk pencarian cepat dengan isset()

        $userPersonalNotes = PersonalNote::where('user_id', $user->id)
                                    ->whereIn('api_ayat_identifier', $apiAyatIdentifiers)
                                    ->get()
                                    ->keyBy('api_ayat_identifier');


        if (isset($surahDetails['ayahs']) && is_array($surahDetails['ayahs'])) {
            foreach ($surahDetails['ayahs'] as $key => &$ayah) { // Pass by reference to modify
                $apiAyatIdentifier = $surahDetails['number'] . ':' . $ayah['numberInSurah'];
                $ayah['translation_text'] = $translationDetails['ayahs'][$key]['text'] ?? 'Terjemahan tidak tersedia.';
                $ayah['is_bookmarked'] = isset($userBookmarks[$apiAyatIdentifier]);
                $ayah['personal_note'] = $userPersonalNotes->get($apiAyatIdentifier);
                $ayah['api_ayat_identifier'] = $apiAyatIdentifier; // Untuk digunakan di view
            }
        }
        
        // Simpan last read
        $user->last_read_surah = $surahDetails['number'];
        $user->last_read_ayah = 1; // Default ke ayat pertama surah yang dibuka
        $user->save();

        return view('user.quran.show_surat', compact('surahDetails', 'surahNumber'));
    }
    
    /**
     * Menampilkan detail satu ayat (mungkin tidak terlalu sering dipakai jika showSurah sudah lengkap).
     * Rute ini ada di web.php: Route::get('/ayah/{surah}/{ayah}', [QuranController::class, 'showAyah'])->name('ayah.show');
     */
    public function showAyah(Request $request, $surahNumber, $ayahNumberInSurah)
    {
        $user = Auth::user();
        $editionIdentifier = session('active_translation_edition', config('quran_cloud.default_translation_edition', 'en.sahih'));
        $apiAyatIdentifier = $surahNumber . ':' . $ayahNumberInSurah;

        $ayahDataResponse = $this->quranApiService->getAyatDetail((int)$surahNumber, (int)$ayahNumberInSurah, 'quran-uthmani');
        $translationDataResponse = $this->quranApiService->getAyatDetail((int)$surahNumber, (int)$ayahNumberInSurah, $editionIdentifier);

        if (!$ayahDataResponse['success'] || !$translationDataResponse['success']) {
            Log::error("Failed to get ayah details for {$apiAyatIdentifier}.", [
                'ayah_response' => $ayahDataResponse,
                'translation_response' => $translationDataResponse
            ]);
            return back()->with('error', 'Gagal memuat detail ayat atau terjemahan.');
        }

        $ayahDetails = $ayahDataResponse['data'];
        // Tambahkan teks terjemahan ke $ayahDetails
        $ayahDetails['translation_text'] = $translationDataResponse['data']['text'] ?? 'Terjemahan tidak tersedia.';
        
        // Cek bookmark & personal note
        $ayahDetails['is_bookmarked'] = Bookmark::where('user_id', $user->id)
                                             ->where('api_ayat_identifier', $apiAyatIdentifier)
                                             ->exists();
        $ayahDetails['personal_note'] = PersonalNote::where('user_id', $user->id)
                                                 ->where('api_ayat_identifier', $apiAyatIdentifier)
                                                 ->first();
        $ayahDetails['api_ayat_identifier'] = $apiAyatIdentifier;

        // Simpan last read
        $user->last_read_surah = (int)$surahNumber;
        $user->last_read_ayah = (int)$ayahNumberInSurah;
        $user->save();

        // Untuk view, mungkin lebih baik tetap menampilkan dalam konteks surah, atau view khusus ayat
        // Untuk sementara, kita bisa redirect ke showSurah dengan highlight ke ayat tersebut, atau buat view baru.
        // Jika menggunakan view yang sama dengan showSurah, Anda perlu sedikit modifikasi di view
        // return view('user.quran.show_ayah_detail', compact('ayahDetails'));
        // Atau, idealnya, redirect ke showSurah dan scroll ke ayat tersebut
        return redirect()->route('surah.show', ['number' => $surahNumber, 'highlight_ayah' => $ayahNumberInSurah])
                         ->with('ayahDetails', $ayahDetails); // Kirim detail ayat jika mau ditampilkan secara khusus
    }


    public function toggleBookmark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_ayat_identifier' => 'required|string|regex:/^\d+:\d+$/', // Format "surah:ayat"
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = Auth::user();
        $apiAyatIdentifier = $request->api_ayat_identifier;

        $bookmark = Bookmark::where('user_id', $user->id)
                            ->where('api_ayat_identifier', $apiAyatIdentifier)
                            ->first();

        $bookmarked = false;
        if ($bookmark) {
            $bookmark->delete();
            $message = 'Bookmark dihapus.';
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'api_ayat_identifier' => $apiAyatIdentifier,
            ]);
            $bookmarked = true;
            $message = 'Ayat ditandai.';
        }

        return response()->json(['success' => true, 'bookmarked' => $bookmarked, 'message' => $message]);
    }

    public function showBookmarks()
    {
        $user = Auth::user();
        $bookmarksData = Bookmark::where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get();
        
        $bookmarks = [];
        $surahNamesCache = []; // Cache nama surah untuk mengurangi panggilan API

        foreach ($bookmarksData as $bookmark) {
            list($surahNum, $ayahNumInSurah) = explode(':', $bookmark->api_ayat_identifier);

            if (!isset($surahNamesCache[(int)$surahNum])) {
                 $surahInfo = $this->quranApiService->getSurahDetail((int)$surahNum, 'quran-uthmani'); // Hanya ambil info surah, bukan semua ayat
                 if($surahInfo['success']){
                    $surahNamesCache[(int)$surahNum] = $surahInfo['data']['englishName'] ?? ('Surah ' . $surahNum);
                 } else {
                    $surahNamesCache[(int)$surahNum] = 'Surah ' . $surahNum;
                 }
            }
            $surahName = $surahNamesCache[(int)$surahNum];

            // Ambil teks ayat (opsional, bisa berat jika banyak bookmark)
            // Untuk performa, mungkin hanya tampilkan referensi, atau ambil teks saat diklik
            // $ayatDetailResponse = $this->quranApiService->getAyatDetail((int)$surahNum, (int)$ayahNumInSurah, 'quran-uthmani');
            // $ayatText = $ayatDetailResponse['success'] ? $ayatDetailResponse['data']['text'] : 'Teks tidak tersedia';
            
            $bookmarks[] = [
                'id' => $bookmark->id,
                'api_ayat_identifier' => $bookmark->api_ayat_identifier,
                'surah_number' => (int)$surahNum,
                'ayah_number_in_surah' => (int)$ayahNumInSurah,
                'surah_name' => $surahName,
                // 'ayat_text' => $ayatText, // Uncomment jika ingin mengambil teks ayat
                'created_at' => $bookmark->created_at
            ];
        }
        
        return view('user.bookmarks.index', compact('bookmarks'));
    }

    // --- Personal Notes ---
    public function showNotes()
    {
        $user = Auth::user();
        $notesData = PersonalNote::where('user_id', $user->id)
                                ->orderBy('updated_at', 'desc')
                                ->get();

        $notes = [];
        $surahNamesCache = [];

        foreach ($notesData as $note) {
            list($surahNum, $ayahNumInSurah) = explode(':', $note->api_ayat_identifier);

            if (!isset($surahNamesCache[(int)$surahNum])) {
                 $surahInfo = $this->quranApiService->getSurahDetail((int)$surahNum, 'quran-uthmani');
                 if($surahInfo['success']){
                    $surahNamesCache[(int)$surahNum] = $surahInfo['data']['englishName'] ?? ('Surah ' . $surahNum);
                 } else {
                    $surahNamesCache[(int)$surahNum] = 'Surah ' . $surahNum;
                 }
            }
            $surahName = $surahNamesCache[(int)$surahNum];
            
            // $ayatDetailResponse = $this->quranApiService->getAyatDetail((int)$surahNum, (int)$ayahNumInSurah, 'quran-uthmani');
            // $ayatText = $ayatDetailResponse['success'] ? $ayatDetailResponse['data']['text'] : 'Teks tidak tersedia';

            $notes[] = [
                'id' => $note->id,
                'api_ayat_identifier' => $note->api_ayat_identifier,
                'note_content' => $note->note,
                'surah_number' => (int)$surahNum,
                'ayah_number_in_surah' => (int)$ayahNumInSurah,
                'surah_name' => $surahName,
                // 'ayat_text' => $ayatText,
                'updated_at' => $note->updated_at
            ];
        }
        return view('user.notes.index', compact('notes'));
    }

    public function addNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_ayat_identifier' => 'required|string|regex:/^\d+:\d+$/',
            'note' => 'required|string|max:5000', // Batasi panjang catatan
        ]);

        if ($validator->fails()) {
            // Jika request AJAX
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        
        // Update atau buat catatan baru
        $personalNote = PersonalNote::updateOrCreate(
            [
                'user_id' => $user->id,
                'api_ayat_identifier' => $request->api_ayat_identifier,
            ],
            [
                'note' => $request->note,
            ]
        );
        
        $message = $personalNote->wasRecentlyCreated ? 'Catatan berhasil ditambahkan.' : 'Catatan berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'note' => $personalNote]);
        }
        return back()->with('success', $message);
    }
    
    // Rute GET untuk form edit: quran.notes.editForm
    // Route::get('/quran/surah/{surahNumber}/ayah/{ayahNumberInSurah}/notes/{noteId}/edit', [QuranController::class, 'editAyahNoteForm'])->name('quran.notes.editForm');
    public function editAyahNoteForm(Request $request, $surahNumber, $ayahNumberInSurah, PersonalNote $note) // Route model binding
    {
        $user = Auth::user();
        // Pastikan note milik user
        if ($note->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        // Ambil detail ayat untuk konteks
        $apiAyatIdentifier = $surahNumber . ':' . $ayahNumberInSurah;
        $ayahDataResponse = $this->quranApiService->getAyatDetail((int)$surahNumber, (int)$ayahNumberInSurah, 'quran-uthmani');
        $ayahDetails = null;
        if($ayahDataResponse['success']){
            $ayahDetails = $ayahDataResponse['data'];
        } else {
            Log::warning("Gagal mengambil detail ayat {$apiAyatIdentifier} untuk form edit catatan.");
        }

        // Perlu view khusus atau bisa pakai modal di show_surat
        return view('user.notes.edit_form_page', compact('note', 'ayahDetails', 'surahNumber', 'ayahNumberInSurah'));
    }


    // Rute PUT untuk update note: quran.notes.update (menggunakan noteId)
    // Route::put('/quran/notes/{noteId}', [QuranController::class, 'updateAyahNote'])->name('quran.notes.update');
    public function updateAyahNote(Request $request, PersonalNote $note) // Route model binding untuk $note
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
             if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        if ($note->user_id !== $user->id) {
             if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $note->update(['note' => $request->note]);
        
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Catatan berhasil diperbarui.', 'note' => $note]);
        }
        return redirect()->route('notes.show')->with('success', 'Catatan berhasil diperbarui.');
    }

    // Rute DELETE untuk hapus note: quran.notes.delete
    // Route::delete('/quran/notes/{noteId}', [QuranController::class, 'destroyAyahNote'])->name('quran.notes.delete');
    public function destroyAyahNote(Request $request, PersonalNote $note) // Route model binding
    {
        $user = Auth::user();
        if ($note->user_id !== $user->id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $note->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Catatan berhasil dihapus.']);
        }
        return redirect()->route('notes.show')->with('success', 'Catatan berhasil dihapus.');
    }
    
    /**
     * Rute GET untuk form edit (jika dari halaman daftar notes): notes.edit
     * Route::get('/notes/{id}/edit', [QuranController::class, 'editNote'])->name('notes.edit');
     * $id di sini adalah ID PersonalNote
     */
    public function editNote(Request $request, PersonalNote $note) // Route model binding
    {
        $user = Auth::user();
        if ($note->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        list($surahNum, $ayahNumInSurah) = explode(':', $note->api_ayat_identifier);
        
        $ayahDataResponse = $this->quranApiService->getAyatDetail((int)$surahNum, (int)$ayahNumInSurah, 'quran-uthmani');
        $ayahDetails = null;
        if($ayahDataResponse['success']){
            $ayahDetails = $ayahDataResponse['data'];
        } else {
            Log::warning("Gagal mengambil detail ayat {$note->api_ayat_identifier} untuk form edit catatan.");
        }

        return view('user.notes.edit', compact('note', 'ayahDetails')); // View yang berbeda dari editAyahNoteForm
    }

    /**
     * Rute PUT untuk update note (jika dari halaman daftar notes): notes.update
     * Route::put('/notes/{id}', [QuranController::class, 'updateNote'])->name('notes.update');
     * $id di sini adalah ID PersonalNote
     */
    public function updateNote(Request $request, PersonalNote $note) // Route model binding
    {
        // Ini sama dengan updateAyahNote, kita bisa panggil itu atau duplikasi logikanya.
        // Untuk konsistensi, lebih baik jika rute ini juga mengarah ke updateAyahNote jika memungkinkan,
        // atau kita pastikan logikanya identik.
        $validator = Validator::make($request->all(), [
            'note_content' => 'required|string|max:5000', // Sesuaikan nama field dari form
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        if ($note->user_id !== $user->id) {
            return back()->with('error', 'Akses ditolak.');
        }

        $note->update(['note' => $request->note_content]);
        return redirect()->route('notes.show')->with('success', 'Catatan berhasil diperbarui.');
    }

    /**
     * Rute DELETE untuk hapus note (jika dari halaman daftar notes): notes.delete
     * Route::delete('/notes/{id}', [QuranController::class, 'deleteNote'])->name('notes.delete');
     */
    public function deleteNote(Request $request, PersonalNote $note) // Route model binding
    {
        // Mirip destroyAyahNote
        $user = Auth::user();
        if ($note->user_id !== $user->id) {
            return back()->with('error', 'Akses ditolak.');
        }
        $note->delete();
        return redirect()->route('notes.show')->with('success', 'Catatan berhasil dihapus.');
    }


    // Menyimpan posisi terakhir dibaca (last read)
    public function saveLastRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surah_number' => 'required|integer|min:1|max:114',
            'ayah_number' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = Auth::user();
        $user->last_read_surah = $request->surah_number;
        $user->last_read_ayah = $request->ayah_number;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Posisi terakhir dibaca disimpan.']);
    }
    
    // Method API yang sudah ada sebelumnya, mungkin perlu disesuaikan atau dihapus jika tidak dipakai lagi
    // Jika fungsionalitasnya sudah dicakup oleh method controller biasa, bisa dipertimbangkan untuk dihapus.
    // public function getAllSurahsApi() { ... }
    // public function getSurahDetailApi($surahNumber, Request $request) { ... }
    // public function searchQuranApi(Request $request) { ... }
    // public function getAyahDetailApi($ayahReference, Request $request) { ... }
    // ... (dan method API untuk notes/bookmarks yang lama)
}
 