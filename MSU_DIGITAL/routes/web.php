<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\QuranController;
use App\Http\Controllers\Admin\AyatManagementController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\User\PreferenceController;
use App\Http\Controllers\Admin\JuzManagementController;
use App\Http\Controllers\User\JuzController;
use App\Http\Controllers\User\FavoriteSuratController;
use App\Http\Controllers\Admin\AudioEditionController;
use App\Http\Controllers\User\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::middleware(['auth'])->prefix('search')->group(function () {
    Route::get('/', [SearchController::class, 'search'])->name('search.q');
    Route::post('/{id}/bookmark', [SearchController::class, 'bookmark'])->name('search.bookmark');
    Route::post('/{id}/rename', [SearchController::class, 'rename'])->name('search.rename');
    Route::delete('/{id}', [SearchController::class, 'destroy'])->name('search.delete');
});


// Protected Routes (require authentication)
Route::middleware(['auth'])->group(function () {
    // Home and Quran routes
    Route::get('/home', [QuranController::class, 'index'])->name('home');
    Route::get('/', [QuranController::class, 'index'])->name('quran.index');
    Route::get('/surah/{number}', [QuranController::class, 'showSurat'])->name('surah.show');
    Route::get('/ayah/{surah}/{ayah}', [QuranController::class, 'showAyah'])->name('ayah.show');
    
    // Bookmarks
    Route::post('/bookmark/toggle', [QuranController::class, 'toggleBookmark'])->name('bookmark.toggle');
    Route::get('/bookmarks', [QuranController::class, 'showBookmarks'])->name('bookmarks.show');
    
    // Notes
    Route::get('/notes', [QuranController::class, 'showNotes'])->name('notes.show');
    Route::post('/notes/add', [QuranController::class, 'addNote'])->name('notes.add');
    Route::get('/notes/{id}/edit', [QuranController::class, 'editNote'])->name('notes.edit');
    Route::put('/notes/{id}', [QuranController::class, 'updateNote'])->name('notes.update');
    Route::delete('/notes/{id}', [QuranController::class, 'deleteNote'])->name('notes.delete');
    
    // Last Read
    Route::post('/last-read', [QuranController::class, 'saveLastRead'])->name('last-read.save');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Preferences Routes
    Route::get('user/preferences/language', [PreferenceController::class, 'showLanguageOptions'])->name('user.preferences.language.show');
    Route::post('user/preferences/language', [PreferenceController::class, 'updateLanguagePreference'])->name('user.preferences.language.update');

    // User Juz Reading Progress Routes
    Route::get('user/juz', [JuzController::class, 'index'])->name('user.juz.index');
    Route::get('user/juz/{juzNumber}', [JuzController::class, 'showJuzContent'])->name('user.juz.show_content')->where('juzNumber', '[1-9]|[1-2][0-9]|30');
    Route::post('user/juz/{juzNumber}/update-progress', [JuzController::class, 'updateProgress'])->name('user.juz.update_progress')->where('juzNumber', '[1-9]|[1-2][0-9]|30');
    Route::post('user/juz/{juzNumber}/mark-completed', [JuzController::class, 'markAsCompleted'])->name('user.juz.mark_completed')->where('juzNumber', '[1-9]|[1-2][0-9]|30');
    
    // Favorite Surat Routes
    Route::get('/quran/favorites', [FavoriteSuratController::class, 'index'])->name('quran.favorites');
    Route::post('/quran/favorites/{suratNumber}', [FavoriteSuratController::class, 'toggleFavorite'])->name('quran.favorites.toggle');
});

// Rute untuk halaman pengelolaan catatan (Server-Side Rendered)
Route::get('/quran/surah/{surahNumber}/ayah/{ayahNumberInSurah}/notes', [QuranController::class, 'showAyahNotesPage'])->name('quran.notes.show');
Route::post('/quran/surah/{surahNumber}/ayah/{ayahNumberInSurah}/notes', [QuranController::class, 'storeAyahNote'])->name('quran.notes.add');
Route::get('/quran/surah/{surahNumber}/ayah/{ayahNumberInSurah}/notes/{noteId}/edit', [QuranController::class, 'editAyahNoteForm'])->name('quran.notes.editForm');
Route::put('/quran/notes/{noteId}', [QuranController::class, 'updateAyahNote'])->name('quran.notes.update');
Route::delete('/quran/notes/{noteId}', [QuranController::class, 'destroyAyahNote'])->name('quran.notes.delete');

// API routes yang mungkin masih digunakan oleh JavaScript (misal untuk daftar surah, search)
// dipindahkan ke routes/api.php atau tetap di sini jika hanya untuk AJAX internal sederhana.
// Untuk sekarang, kita biarkan API GET dasar di sini jika script.js masih memerlukannya.
// Namun, API untuk notes dan bookmark CRUD sebaiknya dipanggil dari web routes jika UI murni Blade.

// Contoh API routes (jika masih diperlukan untuk JS)
// Route::get('/api/surahs', [QuranController::class, 'getAllSurahsApi']);
// Route::get('/api/surah/{surahNumber}', [QuranController::class, 'getSurahDetailApi']);
// Route::get('/api/search', [QuranController::class, 'searchQuranApi']);
// Route::get('/api/ayah/{ayahReference}', [QuranController::class, 'getAyahDetailApi']);

// Komentari atau hapus rute API untuk notes dan bookmark jika sudah tidak dipakai JavaScript
// Route::prefix('api/quran')->group(function () {
//     Route::get('/notes/{surahNumber}/{ayahNumber}', [QuranController::class, 'getVerseNotesApi']);
//     Route::post('/notes/{surahNumber}/{ayahNumber}', [QuranController::class, 'addVerseNoteApi']);
//     Route::put('/notes/{noteId}', [QuranController::class, 'updateVerseNoteApi']); // Pastikan path unik atau ada info surah/ayah
//     Route::delete('/notes/{noteId}', [QuranController::class, 'deleteVerseNoteApi']); // Sama seperti di atas

//     Route::get('/bookmarks', [QuranController::class, 'getBookmarksApi']);
//     Route::post('/bookmarks', [QuranController::class, 'addBookmarkApi']);
//     Route::delete('/bookmarks/{ayahKeyGlobal}', [QuranController::class, 'removeBookmarkApi']);
// });

// Jika Anda ingin rute dashboard terpisah (meskipun saat ini mengarah ke index yang sama)
// Route::get('/dashboard', [QuranController::class, 'index'])->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Anda mungkin ingin menambahkan middleware di sini, contoh: ->middleware(['auth', 'admin']) 

    // Ayat Management Routes
    Route::get('surah/{surahNumber}/ayats', [AyatManagementController::class, 'showSurahAyats'])->name('ayats.show_surah_ayats');
    
    // AdminNote Routes
    Route::post('admin-notes/{apiAyatIdentifier}', [AyatManagementController::class, 'storeAdminNote'])->name('admin_notes.store');
    Route::put('admin-notes/{note}', [AyatManagementController::class, 'updateAdminNote'])->name('admin_notes.update'); // Menggunakan Route Model Binding
    Route::delete('admin-notes/{note}', [AyatManagementController::class, 'destroyAdminNote'])->name('admin_notes.destroy'); // Menggunakan Route Model Binding

    // GlobalKeyword Routes
    // Format {apiEntityIdentifier} bisa "surah:2" atau "ayat:2:255". Titik dua (:) mungkin perlu di-encode di URL atau di-handle dengan regex di route.
    // Untuk semplicitas, kita asumsikan identifier ini aman untuk URL.
    Route::post('global-keywords/{apiEntityIdentifier}/{entityType}', [AyatManagementController::class, 'storeGlobalKeyword'])->name('global_keywords.store');
    Route::delete('global-keywords/{keyword}', [AyatManagementController::class, 'destroyGlobalKeyword'])->name('global_keywords.destroy'); // Menggunakan Route Model Binding

    // Edition Management Routes
    Route::get('editions', [EditionController::class, 'index'])->name('editions.index');
    Route::post('editions/sync', [EditionController::class, 'syncAndStoreEditions'])->name('editions.sync');
    Route::post('editions/add-from-api', [EditionController::class, 'addApiEditionToLocal'])->name('editions.add_from_api');
    Route::patch('editions/{edition}/toggle-availability', [EditionController::class, 'toggleUserAvailability'])->name('editions.toggle_availability');

    // Juz Management Routes
    Route::get('juz-management', [JuzManagementController::class, 'index'])->name('juz_management.index');
    Route::get('juz-management/{juzNumber}/edit', [JuzManagementController::class, 'edit'])->name('juz_management.edit')->where('juzNumber', '[1-9]|[1-2][0-9]|30'); // Hanya 1-30
    Route::put('juz-management/{juzNumber}', [JuzManagementController::class, 'update'])->name('juz_management.update')->where('juzNumber', '[1-9]|[1-2][0-9]|30'); // Hanya 1-30

    // Surat Management Routes
    Route::get('surat-management', [\App\Http\Controllers\Admin\SuratManagementController::class, 'index'])->name('surats.index');
    Route::get('surat-management/{surahNumber}/edit', [\App\Http\Controllers\Admin\SuratManagementController::class, 'edit'])->name('surats.edit')->where('surahNumber', '[1-9]|[1-9][0-9]|1[0-1][0-4]'); // Validasi 1-114
    Route::put('surat-management/{surahNumber}', [\App\Http\Controllers\Admin\SuratManagementController::class, 'update'])->name('surats.update')->where('surahNumber', '[1-9]|[1-9][0-9]|1[0-1][0-4]'); // Validasi 1-114

    // Audio Edition Management Routes
    Route::get('audio-editions', [AudioEditionController::class, 'index'])->name('audio_editions.index');
    Route::post('audio-editions', [AudioEditionController::class, 'store'])->name('audio_editions.store');
    Route::patch('audio-editions/{edition}/toggle-availability', [AudioEditionController::class, 'toggleAvailability'])
        ->name('audio_editions.toggle_availability');
    Route::delete('audio-editions/{edition}', [AudioEditionController::class, 'destroy'])->name('audio_editions.destroy');
});
