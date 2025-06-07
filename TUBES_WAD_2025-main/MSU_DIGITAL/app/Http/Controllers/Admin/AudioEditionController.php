<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailableEdition;
use App\Services\QuranApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AudioEditionController extends Controller
{
    protected $quranApiService;

    public function __construct(QuranApiService $quranApiService)
    {
        $this->quranApiService = $quranApiService;
    }

    public function index()
    {
        try {
            // Get audio editions from API
            $apiEditions = $this->quranApiService->getEditions('audio');
            
            // Get local audio editions
            $localEditions = AvailableEdition::audio()->get();
            
            // Map API editions with local status
            $mappedEditions = collect($apiEditions)->map(function ($apiEdition) use ($localEditions) {
                $localEdition = $localEditions->firstWhere('api_edition_identifier', $apiEdition['identifier']);
                
                return [
                    'identifier' => $apiEdition['identifier'],
                    'name' => $apiEdition['name'],
                    'language_name' => $apiEdition['language_name'],
                    'qari_name' => $apiEdition['qari_name'] ?? $apiEdition['name'],
                    'style' => $apiEdition['style'] ?? null,
                    'is_available' => $localEdition ? true : false,
                    'is_active_for_users' => $localEdition ? $localEdition->is_active_for_users : false,
                    'local_id' => $localEdition ? $localEdition->id : null,
                ];
            });

            return view('admin.audio_editions.index', [
                'editions' => $mappedEditions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching audio editions: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat daftar edisi audio. Silakan coba lagi nanti.');
        }
    }

    public function store(Request $request)
    {
        try {
            $edition = AvailableEdition::create([
                'api_edition_identifier' => $request->identifier,
                'name' => $request->name,
                'language_name' => $request->language_name,
                'qari_name' => $request->qari_name,
                'style' => $request->style,
                'type' => 'audio',
                'is_active_for_users' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Edisi audio berhasil ditambahkan.',
                'edition' => $edition
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing audio edition: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan edisi audio.'
            ], 500);
        }
    }

    public function toggleAvailability(Request $request, AvailableEdition $edition)
    {
        try {
            if ($edition->type !== 'audio') {
                return response()->json([
                    'success' => false,
                    'message' => 'Edisi ini bukan tipe audio.'
                ], 400);
            }

            $edition->update([
                'is_active_for_users' => !$edition->is_active_for_users
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status edisi audio berhasil diperbarui.',
                'is_active' => $edition->is_active_for_users
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling audio edition availability: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status edisi audio.'
            ], 500);
        }
    }

    public function destroy(AvailableEdition $edition)
    {
        try {
            if ($edition->type !== 'audio') {
                return response()->json([
                    'success' => false,
                    'message' => 'Edisi ini bukan tipe audio.'
                ], 400);
            }

            $edition->delete();

            return response()->json([
                'success' => true,
                'message' => 'Edisi audio berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting audio edition: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus edisi audio.'
            ], 500);
        }
    }
} 