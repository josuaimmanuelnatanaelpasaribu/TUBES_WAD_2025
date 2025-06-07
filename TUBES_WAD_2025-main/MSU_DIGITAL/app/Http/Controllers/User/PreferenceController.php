<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AvailableEdition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PreferenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Pastikan hanya user yang login bisa akses
    }

    /**
     * Menampilkan halaman opsi preferensi bahasa/terjemahan.
     */
    public function showLanguageOptions(Request $request)
    {
        $activeTranslations = AvailableEdition::where('is_active_for_users', true)
                                            ->where('type', 'translation')
                                            ->orderBy('language_code')
                                            ->orderBy('name')
                                            ->get();

        $currentUserPreference = Auth::user()->preferred_translation_edition_identifier;

        return view('user.preferences.language_options', compact('activeTranslations', 'currentUserPreference'));
    }

    /**
     * Update preferensi bahasa/terjemahan pengguna.
     */
    public function updateLanguagePreference(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'translation_edition_identifier' => 'required|string|exists:available_editions,api_edition_identifier,is_active_for_users,true,type,translation',
        ], [
            'translation_edition_identifier.exists' => 'Pilihan terjemahan tidak valid atau tidak aktif.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        $user->preferred_translation_edition_identifier = $request->translation_edition_identifier;
        $user->save();

        $request->session()->put('preferred_translation_edition', $request->translation_edition_identifier);

        return back()->with('success', 'Preferensi terjemahan berhasil diperbarui.');
    }
}
