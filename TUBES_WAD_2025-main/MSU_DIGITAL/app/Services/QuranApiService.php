<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Untuk logging error

class QuranApiService
{
    protected $baseUrl;
    protected $cacheDuration;

    public function __construct()
    {
        $this->baseUrl = config('alquran_cloud.api_url');
        $this->cacheDuration = config('alquran_cloud.cache_duration');
    }

    private function makeRequest(string $endpoint, array $params = [])
    {
        $cacheKey = 'quran_api_' . md5($endpoint . http_build_query($params));

        // Coba ambil dari cache dulu
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] == 200 && isset($data['data'])) {
                    Cache::put($cacheKey, ['success' => true, 'data' => $data['data']], $this->cacheDuration);
                    return ['success' => true, 'data' => $data['data']];
                } else {
                    Log::error('Quran API Error: Invalid data structure or API error code.', ['endpoint' => $endpoint, 'response_body' => $data]);
                    return ['success' => false, 'message' => $data['status'] ?? 'Invalid data from API.', 'data' => $data ?? null];
                }
            } else {
                Log::error('Quran API HTTP Error: ' . $response->status(), ['endpoint' => $endpoint, 'response_body' => $response->body()]);
                return ['success' => false, 'message' => 'HTTP Error: ' . $response->status()];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Quran API Connection Exception: ' . $e->getMessage(), ['endpoint' => $endpoint]);
            return ['success' => false, 'message' => 'Connection Error: Could not connect to API.'];
        } catch (\Exception $e) {
            Log::error('Quran API General Exception: ' . $e->getMessage(), ['endpoint' => $endpoint]);
            return ['success' => false, 'message' => 'General Error: ' . $e->getMessage()];
        }
    }

    public function getSurahList()
    {
        return $this->makeRequest('/surah');
    }

    public function getSurahDetail(int $surahNumber, string $editionIdentifier = 'quran-uthmani')
    {
        // API endpoint: /surah/{surahNumber} OR /surah/{surahNumber}/{editionIdentifier}
        $endpoint = '/surah/' . $surahNumber;
        if ($editionIdentifier) {
            $endpoint .= '/' . $editionIdentifier;
        }
        return $this->makeRequest($endpoint);
    }

    public function getAyatDetail(int $surahNumber, int $ayatNumberInSurah, string $editionIdentifier = 'quran-uthmani')
    {
        // API endpoint: /ayah/{surahNumber}:{ayatNumberInSurah} OR /ayah/{surahNumber}:{ayatNumberInSurah}/{editionIdentifier}
        $ayatReference = $surahNumber . ':' . $ayatNumberInSurah;
        $endpoint = '/ayah/' . $ayatReference;
        if ($editionIdentifier) {
            $endpoint .= '/' . $editionIdentifier;
        }
        return $this->makeRequest($endpoint);
    }

    public function getEditions(string $type = null, string $language = null, string $format = 'text')
    {
        $params = [];
        if ($type) $params['type'] = $type;
        if ($language) $params['language'] = $language;
        if ($format) $params['format'] = $format; // text or audio
        return $this->makeRequest('/edition', $params);
    }

    public function getJuz(int $juzNumber, string $editionIdentifier = 'quran-uthmani')
    {
        // API endpoint: /juz/{juzNumber}/{editionIdentifier}
        $endpoint = '/juz/' . $juzNumber . '/' . $editionIdentifier;
        return $this->makeRequest($endpoint);
    }

    public function search(string $keyword, string $surahScope = 'all', string $editionIdentifier = 'en.sahih')
    {
        // API endpoint: /search/{keyword}/{surahScope}/{editionIdentifier}
        // surahScope bisa 'all' atau nomor surah, misal '2' untuk Al-Baqarah
        $endpoint = '/search/' . rawurlencode($keyword) . '/' . $surahScope . '/' . $editionIdentifier;
        return $this->makeRequest($endpoint);
    }
    
    /**
     * Mendapatkan informasi beberapa surah berdasarkan nomornya.
     * Berguna untuk mengambil nama surah atau detail lain dengan satu panggilan jika API mendukung, atau iterasi.
     * API Alquran.cloud /surah tidak langsung mendukung list, jadi kita ambil satu per satu atau semua lalu filter.
     * Untuk efisiensi, sebaiknya ambil semua surah sekali dan cache, lalu filter dari situ.
     */
    public function getMultipleSurahInfo(array $surahNumbers)
    {
        $allSurahsResponse = $this->getSurahList();
        if (!$allSurahsResponse['success']) {
            return $allSurahsResponse; // Kembalikan error jika gagal ambil list surah
        }

        $allSurahs = collect($allSurahsResponse['data']);
        $filteredSurahs = $allSurahs->whereIn('number', $surahNumbers)->keyBy('number')->toArray();

        return ['success' => true, 'data' => $filteredSurahs];
    }

    /**
     * Mengambil nama surah berdasarkan nomor surah.
     */
    public function getSurahName(int $surahNumber)
    {
        $cacheKey = 'surah_name_' . $surahNumber;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $surahsResponse = $this->getSurahList();
        if ($surahsResponse['success']) {
            foreach ($surahsResponse['data'] as $surah) {
                Cache::put('surah_name_' . $surah['number'], $surah['englishName'] ?? $surah['name'], $this->cacheDuration); // Cache nama setiap surah
            }
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
        }
        return 'Surah ' . $surahNumber; // Fallback
    }
} 