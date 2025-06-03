<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuranController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes untuk data Quran dasar (digunakan oleh JavaScript untuk UI dinamis)
Route::get('/surahs', [QuranController::class, 'getAllSurahsApi'])->name('api.surahs.all');
Route::get('/surah/{surahNumber}', [QuranController::class, 'getSurahDetailApi'])->name('api.surah.detail');
Route::get('/search', [QuranController::class, 'searchQuranApi'])->name('api.search');
Route::get('/ayah/{ayahReference}', [QuranController::class, 'getAyahDetailApi'])->name('api.ayah.detail');

/*
// Rute API untuk Catatan dan Bookmark (MUNGKIN TIDAK DIPERLUKAN LAGI JIKA UI MURNI BLADE)
// Komentari atau hapus jika JavaScript tidak lagi menggunakannya secara langsung untuk CRUD.
// Operasi CRUD untuk catatan dan bookmark sekarang lebih difokuskan melalui web routes dan form HTML.

Route::prefix('quran')->group(function () {
    // Notes API (jika masih ada bagian JS yang butuh ini)
    Route::get('/notes/{surahNumber}/{ayahNumber}', [QuranController::class, 'getVerseNotesApi'])->name('api.notes.get');
    Route::post('/notes/{surahNumber}/{ayahNumber}', [QuranController::class, 'addVerseNoteApi'])->name('api.notes.add');
    Route::put('/notes/{noteId}', [QuranController::class, 'updateVerseNoteApi'])->name('api.notes.update');
    Route::delete('/notes/{noteId}', [QuranController::class, 'deleteVerseNoteApi'])->name('api.notes.delete');

    // Bookmarks API (jika masih ada bagian JS yang butuh ini)
    Route::get('/bookmarks', [QuranController::class, 'getBookmarksApi'])->name('api.bookmarks.get');
    Route::post('/bookmarks', [QuranController::class, 'addBookmarkApi'])->name('api.bookmarks.add');
    Route::delete('/bookmarks/{ayahKeyGlobal}', [QuranController::class, 'removeBookmarkApi'])->name('api.bookmarks.delete');
});
*/ 