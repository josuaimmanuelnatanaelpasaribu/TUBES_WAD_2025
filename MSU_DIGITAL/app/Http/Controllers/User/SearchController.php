<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\SearchHistory;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->input('q');

        // Simpan ke riwayat
        SearchHistory::create([
            'user_id' => Auth::id(),
            'keyword' => $keyword,
        ]);

        // Dummy: hasil pencarian (implementasi Qurannya tergantung databasenya)
        $results = []; // ganti sesuai dengan data Qur’an-mu

        return view('user.search.results', compact('results', 'keyword'));
    }

    public function bookmark($id)
    {
        $search = SearchHistory::findOrFail($id);
        $search->is_bookmarked = !$search->is_bookmarked;
        $search->save();

        return back()->with('success', 'Bookmark diperbarui.');
    }

    public function rename(Request $request, $id)
    {
        $search = SearchHistory::findOrFail($id);
        $search->label = $request->input('label');
        $search->save();

        return back()->with('success', 'Label berhasil diubah.');
    }

    public function destroy($id)
    {
        SearchHistory::destroy($id);
        return back()->with('success', 'Riwayat dihapus.');
    }
}
