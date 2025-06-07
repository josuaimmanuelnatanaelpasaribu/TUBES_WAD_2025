<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AvailableEdition; // Untuk memeriksa validitas edisi
use Illuminate\Support\Facades\Config; // Untuk menyimpan preferensi secara global jika perlu

class SetLocaleFromPreference
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $preferredEditionIdentifier = $request->session()->get(
                'preferred_translation_edition', 
                $user->preferred_translation_edition_identifier
            );

            $defaultTranslationEdition = config('quran_cloud.default_translation_edition', 'id.indonesian'); // Ambil dari config
            $editionToUse = $defaultTranslationEdition;

            if ($preferredEditionIdentifier) {
                // Validasi apakah edisi yang dipilih masih aktif dan merupakan terjemahan
                $isValidAndActive = AvailableEdition::where('api_edition_identifier', $preferredEditionIdentifier)
                                                  ->where('is_active_for_users', true)
                                                  ->where('type', 'translation') // Pastikan tipenya terjemahan
                                                  ->exists();
                if ($isValidAndActive) {
                    $editionToUse = $preferredEditionIdentifier;
                } else {
                    // Jika preferensi tidak valid/aktif, mungkin reset di DB & session
                    if ($user->preferred_translation_edition_identifier === $preferredEditionIdentifier) {
                        $user->preferred_translation_edition_identifier = null;
                        $user->save();
                    }
                    $request->session()->forget('preferred_translation_edition');
                    // Fallback ke default sudah diatur
                }
            }
            
            // Simpan edisi yang akan digunakan ke session agar mudah diakses
            $request->session()->put('active_translation_edition', $editionToUse);

            // Anda juga bisa menyimpannya dalam config untuk request saat ini agar bisa diakses global
            // Config::set('app.current_translation_edition', $editionToUse);
        }

        return $next($request);
    }
}
