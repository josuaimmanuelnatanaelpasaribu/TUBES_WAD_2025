<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuranApiService;
use App\Models\User\JuzReadingProgress;
use App\Models\JuzCustomData; // Untuk mengambil deskripsi kustom jika ada
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Untuk logging

class JuzController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        $this->middleware('auth'); // Semua method di sini memerlukan user login
    }

    /**
     * Menampilkan daftar 30 Juz beserta progres baca user.
     */
    public function index()
    {
        $user = Auth::user();
        $allJuzInfo = [];
        $readingProgress = JuzReadingProgress::where('user_id', $user->id)
                                             ->get()
                                             ->keyBy('juz_number');
        $customDescriptions = JuzCustomData::pluck('custom_description', 'juz_number');

        for ($i = 1; $i <= 30; $i++) {
            $progress = $readingProgress->get($i);
            $allJuzInfo[$i] = [
                'juz_number' => $i,
                'progress_percentage' => $progress ? $progress->progress_percentage : 0,
                'is_completed' => $progress ? $progress->is_completed : false,
                'custom_description' => $customDescriptions->get($i),
                // Tambahkan info lain dari API jika perlu, misal nama umum Juz atau cakupan surat/ayat
            ];
        }

        return view('user.juz.index', compact('allJuzInfo'));
    }

    /**
     * Menampilkan konten (ayat-ayat) dari sebuah Juz.
     */
    public function showJuzContent(Request $request, $juzNumber)
    {
        if ($juzNumber < 1 || $juzNumber > 30) {
            abort(404, 'Nomor Juz tidak valid.');
        }

        $arabicEdition = 'quran-uthmani'; // Edisi default untuk teks Arab
        $translationEdition = session('active_translation_edition', config('quran_cloud.default_translation_edition', 'id.indonesian'));

        $juzArabicData = $this->quranApiService->getJuz($juzNumber, $arabicEdition);
        $juzTranslationData = $this->quranApiService->getJuz($juzNumber, $translationEdition);

        if (!$juzArabicData || !isset($juzArabicData['data']['ayahs'])) {
            Log::error("Failed to fetch Arabic content for Juz {$juzNumber} with edition {$arabicEdition}");
            return back()->withErrors('Gagal mengambil konten Arab untuk Juz ini dari API.')->withInput();
        }

        $processedAyahs = [];
        $ayahsArabic = collect($juzArabicData['data']['ayahs'])->keyBy(function ($item) {
            return $item['surah']['number'] . ':' . $item['numberInSurah'];
        });

        $ayahsTranslation = [];
        if ($juzTranslationData && isset($juzTranslationData['data']['ayahs'])) {
            $ayahsTranslation = collect($juzTranslationData['data']['ayahs'])->keyBy(function ($item) {
                return $item['surah']['number'] . ':' . $item['numberInSurah'];
            });
        }

        foreach ($ayahsArabic as $identifier => $arabicAyah) {
            $translationText = $ayahsTranslation->has($identifier) ? $ayahsTranslation->get($identifier)['text'] : 'Terjemahan tidak tersedia untuk edisi ini.';
            $processedAyahs[] = [
                'numberInSurah' => $arabicAyah['numberInSurah'],
                'text' => $arabicAyah['text'], // Teks Arab
                'translation_text' => $translationText,
                'surah_number' => $arabicAyah['surah']['number'],
                'surah_name' => $arabicAyah['surah']['name'],
                'api_ayat_identifier' => $identifier,
                 // Anda bisa tambahkan info note pribadi & bookmark di sini jika perlu di halaman ini
            ];
        }
        
        $juzData = $juzArabicData['data']; // Mengambil informasi umum Juz dari data Arab
        unset($juzData['ayahs']); // Hapus array ayahs mentah

        return view('user.juz.show_content', compact('juzData', 'processedAyahs', 'juzNumber', 'translationEdition'));
    }

    /**
     * Update progres baca Juz.
     */
    public function updateProgress(Request $request, $juzNumber)
    {
        if ($juzNumber < 1 || $juzNumber > 30) {
            return response()->json(['error' => 'Nomor Juz tidak valid.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $progressPercentage = $request->input('progress_percentage');
        $isCompleted = ($progressPercentage == 100);

        JuzReadingProgress::updateOrCreate(
            ['user_id' => $user->id, 'juz_number' => $juzNumber],
            [
                'progress_percentage' => $progressPercentage,
                'is_completed' => $isCompleted
            ]
        );

        return response()->json(['message' => 'Progres Juz berhasil diperbarui.', 'is_completed' => $isCompleted, 'progress' => $progressPercentage]);
    }

    /**
     * Tandai Juz sebagai selesai dibaca.
     */
    public function markAsCompleted($juzNumber)
    {
        if ($juzNumber < 1 || $juzNumber > 30) {
             return response()->json(['error' => 'Nomor Juz tidak valid.'], 400);
        }

        $user = Auth::user();

        JuzReadingProgress::updateOrCreate(
            ['user_id' => $user->id, 'juz_number' => $juzNumber],
            [
                'progress_percentage' => 100,
                'is_completed' => true
            ]
        );

        // return back()->with('success', "Juz {$juzNumber} berhasil ditandai selesai.");
        // Jika ini via AJAX, kembalikan JSON
        return response()->json(['message' => "Juz {$juzNumber} berhasil ditandai selesai.", 'is_completed' => true, 'progress' => 100]);
    }
}
