<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuranApiService; // Meskipun tidak digunakan langsung di sini, bisa berguna nanti
use App\Models\JuzCustomData;
use Illuminate\Support\Facades\Validator;

class JuzManagementController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
        // Middleware admin bisa ditambahkan di sini
        // $this->middleware(['auth', 'admin']);
    }

    /**
     * Menampilkan daftar 30 Juz beserta deskripsi kustom.
     */
    public function index()
    {
        $allJuzData = [];
        $customData = JuzCustomData::pluck('custom_description', 'juz_number');

        for ($i = 1; $i <= 30; $i++) {
            $allJuzData[$i] = [
                'juz_number' => $i,
                'custom_description' => $customData->get($i)
            ];
        }

        return view('admin.juz_management.index', compact('allJuzData'));
    }

    /**
     * Menampilkan form untuk mengedit/menambah deskripsi kustom untuk Juz tertentu.
     */
    public function edit($juzNumber)
    {
        if ($juzNumber < 1 || $juzNumber > 30) {
            abort(404, 'Nomor Juz tidak valid.');
        }

        $juzData = JuzCustomData::firstOrNew(['juz_number' => $juzNumber]);
        // Jika Anda ingin mengambil data ayat dari API untuk ditampilkan di halaman edit, lakukan di sini.
        // Contoh: $juzApiDetails = $this->quranApiService->getJuz($juzNumber, 'quran-uthmani');

        return view('admin.juz_management.edit', compact('juzData', 'juzNumber'));
    }

    /**
     * Menyimpan (update atau create) deskripsi kustom untuk Juz tertentu.
     */
    public function update(Request $request, $juzNumber)
    {
        if ($juzNumber < 1 || $juzNumber > 30) {
            abort(400, 'Nomor Juz tidak valid.');
        }

        $validator = Validator::make($request->all(), [
            'custom_description' => 'nullable|string|max:5000', // Sesuaikan max length jika perlu
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        JuzCustomData::updateOrCreate(
            ['juz_number' => $juzNumber],
            ['custom_description' => $request->input('custom_description')]
        );

        return redirect()->route('admin.juz_management.index')->with('success', "Deskripsi kustom untuk Juz {$juzNumber} berhasil diperbarui.");
    }
}
