<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuranApiService;
use App\Models\AvailableEdition;
use Illuminate\Support\Facades\Log;

class EditionController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        // Middleware untuk admin bisa ditambahkan di sini jika perlu
        // $this->middleware('auth');
        // $this->middleware('admin'); 
    }

    /**
     * Menampilkan daftar edisi yang tersedia dan yang dari API.
     */
    public function index()
    {
        $localEditions = AvailableEdition::orderBy('type')->orderBy('language_code')->get();
        $apiEditionsData = $this->quranApiService->getEditions(); // Ambil semua tipe

        $apiEditions = [];
        if (isset($apiEditionsData['data']) && is_array($apiEditionsData['data'])) {
            $apiEditions = collect($apiEditionsData['data'])->filter(function ($edition) {
                return in_array($edition['type'], ['translation', 'tafsir']);
            })->keyBy('identifier');
        }

        // Tandai edisi API yang sudah ada di lokal
        foreach ($apiEditions as $identifier => $apiEdition) {
            $apiEdition->is_added = $localEditions->contains('api_edition_identifier', $identifier);
        }

        return view('admin.editions.index', compact('localEditions', 'apiEditions'));
    }

    /**
     * Mengambil semua edisi dari API dan menyimpannya ke database lokal.
     * Edisi baru akan ditambahkan dengan is_active_for_users = false (atau true sesuai kebutuhan awal).
     */
    public function syncAndStoreEditions()
    {
        $apiEditionsData = $this->quranApiService->getEditions();

        if (!$apiEditionsData || !isset($apiEditionsData['data'])) {
            Log::error('Failed to fetch editions from API during sync.');
            return back()->withErrors('Gagal mengambil daftar edisi dari API.');
        }

        $translationsAndTafsirs = collect($apiEditionsData['data'])->filter(function ($edition) {
            return in_array($edition['type'], ['translation', 'tafsir']);
        });

        $syncedCount = 0;
        $newlyAddedCount = 0;

        foreach ($translationsAndTafsirs as $apiEdition) {
            try {
                $edition = AvailableEdition::updateOrCreate(
                    ['api_edition_identifier' => $apiEdition['identifier']],
                    [
                        'name' => $apiEdition['name'] . ' (' . $apiEdition['englishName'] . ')',
                        'language_code' => $apiEdition['language'],
                        'type' => $apiEdition['type'],
                        // 'is_active_for_users' => false, // Default untuk yang baru, bisa diubah sesuai kebutuhan
                    ]
                );
                if ($edition->wasRecentlyCreated) {
                    $newlyAddedCount++;
                }
                $syncedCount++;
            } catch (\Exception $e) {
                Log::error("Error syncing edition {$apiEdition['identifier']}: " . $e->getMessage());
                // Lanjutkan ke edisi berikutnya jika ada error
            }
        }

        return redirect()->route('admin.editions.index')
                         ->with('success', "Sinkronisasi selesai. Total {$syncedCount} edisi diproses, {$newlyAddedCount} edisi baru ditambahkan.");
    }

    /**
     * Toggle status is_active_for_users untuk sebuah edisi.
     */
    public function toggleUserAvailability(AvailableEdition $edition)
    {
        $edition->is_active_for_users = !$edition->is_active_for_users;
        $edition->save();

        $status = $edition->is_active_for_users ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Edisi '{$edition->name}' berhasil {$status} untuk pengguna.");
    }

    /**
     * Menambahkan edisi spesifik dari API ke sistem lokal jika belum ada.
     * Ini berbeda dari syncAndStoreEditions yang mencoba mengambil semua.
     */
    public function addApiEditionToLocal(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'name' => 'required|string',
            'language' => 'required|string',
            'type' => 'required|string|in:translation,tafsir',
        ]);

        try {
            AvailableEdition::create([
                'api_edition_identifier' => $request->identifier,
                'name' => $request->name,
                'language_code' => $request->language,
                'type' => $request->type,
                'is_active_for_users' => true, // Default aktif saat ditambahkan manual
            ]);
            return redirect()->route('admin.editions.index')->with('success', "Edisi '{$request->name}' berhasil ditambahkan ke sistem.");
        } catch (\Illuminate\Database\QueryException $e) {
            // Kemungkinan karena unique constraint identifier sudah ada
            if ($e->errorInfo[1] == 1062) { // Kode error untuk duplicate entry MySQL
                return back()->withErrors("Edisi dengan identifier '{$request->identifier}' sudah ada di sistem.")->withInput();
            }
            Log::error("Error adding API edition to local: " . $e->getMessage());
            return back()->withErrors("Gagal menambahkan edisi ke sistem. Error: " . $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error("Error adding API edition to local: " . $e->getMessage());
            return back()->withErrors("Terjadi kesalahan saat menambahkan edisi.")->withInput();
        }
    }
}
