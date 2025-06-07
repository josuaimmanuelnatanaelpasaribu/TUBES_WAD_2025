<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FavoriteSurat;
use App\Services\QuranApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteSuratController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
    }

    public function index()
    {
        try {
            // Get user's favorite surah numbers
            $favoriteSuratNumbers = Auth::user()
                ->favoriteSurats()
                ->pluck('surat_number')
                ->toArray();

            // Get details for each favorite surah from API
            $favoriteSurahs = collect($favoriteSuratNumbers)->map(function ($number) {
                try {
                    $surah = $this->quranApiService->getSurahByNumber($number);
                    $surah['isFavorite'] = true;
                    return $surah;
                } catch (\Exception $e) {
                    return null;
                }
            })->filter()->values();

            return view('user.quran.favorites', [
                'surahs' => $favoriteSurahs
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat daftar surat favorit.');
        }
    }

    public function toggleFavorite(Request $request, $suratNumber)
    {
        try {
            $user = Auth::user();
            $existingFavorite = $user->favoriteSurats()
                ->where('surat_number', $suratNumber)
                ->first();

            if ($existingFavorite) {
                $existingFavorite->delete();
                $message = 'Surat berhasil dihapus dari favorit.';
                $status = false;
            } else {
                $user->favoriteSurats()->create([
                    'surat_number' => $suratNumber
                ]);
                $message = 'Surat berhasil ditambahkan ke favorit.';
                $status = true;
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'isFavorite' => $status,
                    'message' => $message
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengubah status favorit.'
                ], 500);
            }

            return back()->with('error', 'Gagal mengubah status favorit.');
        }
    }
} 