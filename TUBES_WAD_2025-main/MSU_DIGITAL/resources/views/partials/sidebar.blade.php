<aside class="sidebar">
    <div class="sidebar-search">
        <input type="text" placeholder="Value" id="sidebarSearchInput">
        <button class="clear-search-btn" id="clearSearchBtn">X</button>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="{{-- route('dashboard') --}}" class="active">Dashboard</a></li>
            <li><a href="#" id="daftarJuzLink">Daftar Juz</a></li>
            <li><a href="#" id="daftarSurahLink">Daftar Surah</a>
                <ul class="surah-sublist" id="surahSublistContainer" style="display: none;">
                    <!-- Daftar surah akan dimuat di sini oleh PHP/JS -->
                </ul>
            </li>
        </ul>
    </nav>
</aside> 