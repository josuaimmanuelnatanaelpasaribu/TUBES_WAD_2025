@extends('layouts.app')

@section('title', 'MSU Digital Quran - Utama')

@section('content')
<div class="content-display" id="contentDisplay">
    {{-- Konten dinamis (detail surah, hasil pencarian) akan dimuat di sini oleh PHP/JS --}}
    <p>Selamat datang di MSU Digital Quran. Pilih Surah dari sidebar atau gunakan pencarian.</p>
</div>

<div class="history-section">
    <h2>History Bacaan</h2>
    <div class="history-controls">
        <input type="text" placeholder="Search History" id="historySearchInput">
        <select id="historySortSelect">
            <option value="terbaru">Terbaru</option>
            <option value="terlama">Terlama</option>
        </select>
    </div>
    <table class="history-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Surah</th>
                <th>Ayat</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody id="historyTableBody">
            {{-- Riwayat bacaan akan dimuat di sini --}}
            @if(isset($readingHistory) && count($readingHistory) > 0)
                @foreach($readingHistory as $item)
                    <tr>
                        <td>{{ $item['user'] ?? 'N/A' }}</td>
                        <td><a href="#" data-surah-number="{{ $item['surah_number'] }}">{{ $item['surah'] }}</a></td>
                        <td>{{ $item['ayat'] }}</td>
                        <td>{{ $item['tanggal'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="4">Belum ada riwayat bacaan.</td></tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Placeholder untuk bagian bookmarks jika ingin dirender server-side --}}
<div id="bookmarksDisplayContainer" style="margin-top: 20px;">
    {{-- Bookmarks akan ditampilkan di sini --}}
</div>
@endsection

@push('scripts')
<script>
    // Script spesifik untuk halaman ini bisa ditambahkan di sini
    // Misalnya, event listener untuk link surah di tabel history
    document.querySelectorAll('#historyTableBody a[data-surah-number]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const surahNumber = this.dataset.surahNumber;
            // Panggil fungsi JS untuk memuat detail surah (akan kita definisikan di script.js)
            if (typeof fetchSurahDetails === 'function') {
                fetchSurahDetails(surahNumber);
            }
        });
    });
</script>
@endpush 