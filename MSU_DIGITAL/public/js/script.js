// script.js - MSU Digital Quran (PHP/Blade Version)

document.addEventListener('DOMContentLoaded', () => {
    console.log('MSU Digital Quran App Initialized (PHP/Blade Mode - Server-Side Notes/Bookmarks)');

    // --- API Base URL for our backend (Laravel API routes) ---
    // Ini digunakan untuk mengambil daftar surah, detail surah, dan search (masih via JS AJAX)
    const APP_API_BASE_URL = '/api'; // Pastikan ini sesuai dengan prefix di routes/api.php

    // --- DOM Elements ---
    const searchInput = document.getElementById('sidebarSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const contentDisplay = document.getElementById('contentDisplay');
    const historyTableBody = document.getElementById('historyTableBody');
    const daftarSurahLink = document.getElementById('daftarSurahLink');
    const surahSublistContainer = document.getElementById('surahSublistContainer');
    // const bookmarksDisplayContainer = document.getElementById('bookmarksDisplayContainer'); // Dihapus

    // --- App State & Data ---
    // let readingHistory = []; // Dikelola oleh backend dan di-render oleh Blade, JS bisa update jika perlu
    // Bookmarks dikelola sepenuhnya oleh server via form POST.

    // --- Event Listeners ---
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            contentDisplay.innerHTML = '<p>Welcome to MSU Digital Quran. Select a Surah or use the search.</p>';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', debounce(async (event) => {
            const query = event.target.value.trim().toLowerCase();
            if (query.length > 2) { // Mulai pencarian setelah beberapa karakter
                await searchQuran(query);
            } else if (query.length === 0) {
                contentDisplay.innerHTML = '<p>Welcome to MSU Digital Quran. Select a Surah or use the search.</p>';
            }
        }, 500));
    }

    if (daftarSurahLink) {
        daftarSurahLink.addEventListener('click', async (e) => {
            e.preventDefault();
            const isDisplayed = surahSublistContainer.style.display === 'block';
            if (!isDisplayed) {
                await fetchAndRenderAllSurahs(); // Ini masih menggunakan API
                surahSublistContainer.style.display = 'block';
            } else {
                surahSublistContainer.style.display = 'none';
            }
            document.querySelectorAll('.sidebar-nav ul li a').forEach(a => a.classList.remove('active'));
            daftarSurahLink.classList.add('active');
            // if (!isDisplayed) contentDisplay.innerHTML = '<p>Select a Surah from the list above.</p>';
        });
    }

    // --- API Call Functions (Interacting with Laravel Backend API for Surah list, details, search) ---

    async function fetchAndRenderAllSurahs() {
        if (surahSublistContainer.children.length > 0 && surahSublistContainer.children[0].textContent !== 'Loading...') {
             // surahSublistContainer.style.display = 'block'; // Pastikan terlihat
            return;
        }
        surahSublistContainer.innerHTML = '<li>Loading...</li>';
        try {
            // Menggunakan nama rute API yang didefinisikan di api.php
            const response = await fetch(`/api/surahs`); // Disesuaikan dengan rute di api.php
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success && result.data) {
                renderSurahListInSidebar(result.data);
            } else {
                surahSublistContainer.innerHTML = `<li>Error: ${result.message || 'Could not load Surahs.'}</li>`;
            }
        } catch (error) {
            console.error('Error fetching all Surahs via API:', error);
            surahSublistContainer.innerHTML = `<li>Error loading Surahs. Check console.</li>`;
        }
    }

    function renderSurahListInSidebar(surahs) {
        surahSublistContainer.innerHTML = ''; 
        surahs.forEach(surah => {
            const listItem = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#surah-${surah.number}`; // Untuk navigasi internal jika diperlukan
            link.textContent = `${surah.number}. ${surah.englishName} (${surah.name})`;
            link.dataset.surahNumber = surah.number;
            link.addEventListener('click', async (e) => {
                e.preventDefault();
                await fetchSurahDetails(surah.number);
                document.querySelectorAll('.sidebar-nav ul li a, .surah-sublist li a').forEach(a => a.classList.remove('active'));
                link.classList.add('active');
                // surahSublistContainer.style.display = 'block'; // Keep open
            });
            listItem.appendChild(link);
            surahSublistContainer.appendChild(listItem);
        });
    }

    async function fetchSurahDetails(surahNumber, edition = 'quran-uthmani') {
        contentDisplay.innerHTML = '<p>Loading Surah details...</p>';
        try {
            const response = await fetch(`/api/surah/${surahNumber}?edition=${edition}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success && result.data) {
                renderSurahAyahs(result.data); // Fungsi ini diubah untuk render form & link
                // Backend menangani update history. Untuk update tabel di client, kita bisa:
                // 1. Panggil fungsi untuk fetch dan render ulang tabel history dari backend (jika ada endpointnya)
                // 2. Atau, jika data history sudah ada di `quran.index` via Blade, biarkan reload halaman yang mengurusnya.
                // Untuk sementara, kita bisa coba update frontend history jika data cukup.
                if (window.updateReadingHistoryTable) { // Cek apakah fungsi global ada (dari quran.index.blade.php)
                    // addToReadingHistoryFrontend(result.data.englishName, 'Full Surah', result.data.number, result.data.name, result.data.ayahs[0]?.number);
                    // Fungsi updateReadingHistoryTable akan mengambil data terbaru dari session via Blade atau API call baru
                    // Untuk sekarang, kita panggil saja update agar memuat ulang data dari session (jika logicnya ada di blade)
                    // window.updateReadingHistoryTable(); // Ini perlu didefinisikan di Blade dan mungkin melakukan fetch
                }
            } else {
                contentDisplay.innerHTML = `<p>Error: ${result.message || `Could not load Surah ${surahNumber}.`}</p>`;
            }
        } catch (error) {
            console.error(`Error fetching Surah ${surahNumber} via API:`, error);
            contentDisplay.innerHTML = `<p>Error loading Surah ${surahNumber}. Check console.</p>`;
        }
    }
    
    function renderSurahAyahs(surahData) {
        contentDisplay.innerHTML = ''; // Clear previous content
        const surahTitle = document.createElement('h3');
        surahTitle.className = 'surah-title-main';
        surahTitle.innerHTML = `${surahData.number}. ${surahData.englishName} <span class="arabic-surah-name">(${surahData.name})</span> - ${surahData.revelationType}`;
        contentDisplay.appendChild(surahTitle);

        const ayahsContainer = document.createElement('div');
        ayahsContainer.className = 'ayahs-container';

        surahData.ayahs.forEach(ayah => {
            const ayahDiv = document.createElement('div');
            ayahDiv.className = 'ayah-item';
            // ayah.number adalah nomor ayat global unik
            // ayah.numberInSurah adalah nomor ayat dalam surah

            // Link untuk Kelola Catatan
            const notesLink = `/quran/surah/${surahData.number}/ayah/${ayah.numberInSurah}/notes`;

            // Form untuk Bookmark
            // Kita butuh CSRF token untuk form POST di Laravel. Ini harus disediakan oleh Blade.
            // Untuk JS, kita bisa ambil dari meta tag jika ada.
            const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
            
            const bookmarkFormHtml = `
                <form action="/quran/bookmark/toggle" method="POST" style="display: inline;">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="surah_number" value="${surahData.number}">
                    <input type="hidden" name="ayah_number_in_surah" value="${ayah.numberInSurah}">
                    <input type="hidden" name="ayah_key_global" value="${ayah.number}">
                    <input type="hidden" name="surah_name" value="${escapeQuotes(surahData.englishName + ' (' + surahData.name + ')')}">
                    <input type="hidden" name="ayah_text" value="${escapeQuotes(ayah.text)}">
                    <input type="hidden" name="redirect_to" value="${window.location.pathname + window.location.search}">
                    <button type="submit" class="btn btn-bookmark">
                        ${ ayah.isBookmarked ? 'Remove Bookmark' : 'Bookmark' } 
                        ${ /* Info isBookmarked ini idealnya datang dari server bersama data Ayah */ '' }
                    </button>
                </form>
            `;
            // Teks 'Bookmark'/'Remove Bookmark' di atas hanya placeholder. Status sebenarnya dikelola server.
            // Untuk UI yang lebih baik, kita butuh info `ayah.isBookmarked` dari server.

            ayahDiv.innerHTML = `
                <div class="ayah-header">
                    <p class="ayah-reference"><strong>${surahData.number}:${ayah.numberInSurah}</strong> (Global: ${ayah.number})</p>
                </div>
                <p class="ayah-text arabic">${ayah.text}</p>
                <div class="ayah-actions">
                    ${bookmarkFormHtml}
                    <a href="${notesLink}" class="btn btn-notes">Personal Notes</a>
                </div>
            `;
            ayahsContainer.appendChild(ayahDiv);
        });
        contentDisplay.appendChild(ayahsContainer);
        
        // Panggil fungsi untuk mengupdate tabel riwayat bacaan jika tersedia
        if (window.updateReadingHistoryTable) {
            window.updateReadingHistoryTable();
        }
    }

    async function searchQuran(keyword) {
        contentDisplay.innerHTML = '<p>Searching...</p>';
        try {
            const response = await fetch(`/api/search?keyword=${encodeURIComponent(keyword)}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success && result.data && result.data.matches && result.data.matches.length > 0) {
                await renderSearchResults(result.data, keyword); // result.data berisi objek dengan property 'matches'
            } else {
                contentDisplay.innerHTML = `<p>No results found for "${keyword}". ${result.message || ''}</p>`;
            }
        } catch (error) {
            console.error(`Error searching Quran for "${keyword}" via API:`, error);
            contentDisplay.innerHTML = `<p>Error performing search for "${keyword}". Check console.</p>`;
        }
    }

    async function renderSearchResults(searchData, keyword) { // searchData adalah objek dari API
        contentDisplay.innerHTML = `<h3>Search Results for "${keyword}" (${searchData.count} matches)</h3>`;
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results-container';
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        for (const match of searchData.matches) {
            // match.surah adalah objek { number, name, englishName, englishNameTranslation, revelationType, numberOfAyahs }
            // match.numberInSurah, match.text (translation), match.edition
            
            // Untuk mendapatkan teks Arab, kita idealnya fetch detail ayah atau pastikan API search mengembalikannya.
            // API search alquran.cloud TIDAK mengembalikan teks Arab.
            // Kita perlu fetch detail ayah spesifik (quran-uthmani)
            let arabicText = 'Loading Arabic text...';
            try {
                const ayahRef = `${match.surah.number}:${match.numberInSurah}`;
                const ayahDetailResponse = await fetch(`/api/ayah/${ayahRef}?editions=quran-uthmani&updateHistory=false`);
                if (ayahDetailResponse.ok) {
                    const ayahDetailResult = await ayahDetailResponse.json();
                    if (ayahDetailResult.success && ayahDetailResult.data && ayahDetailResult.data[0]) {
                        arabicText = ayahDetailResult.data[0].text;
                    } else {
                        arabicText = 'Error loading Arabic text.';
                    }
                } else {
                     arabicText = 'Error fetching Arabic text.';
                }
            } catch (e) {
                console.error('Error fetching Arabic for search result:', e);
                arabicText = 'Failed to load Arabic text.';
            }

            const notesLink = `/quran/surah/${match.surah.number}/ayah/${match.numberInSurah}/notes`;
            const bookmarkFormHtml = `
                <form action="/quran/bookmark/toggle" method="POST" style="display: inline;">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="surah_number" value="${match.surah.number}">
                    <input type="hidden" name="ayah_number_in_surah" value="${match.numberInSurah}">
                    <input type="hidden" name="ayah_key_global" value="${match.number}"> 
                    <input type="hidden" name="surah_name" value="${escapeQuotes(match.surah.englishName + ' (' + match.surah.name + ')')}">
                    <input type="hidden" name="ayah_text" value="${escapeQuotes(arabicText)}">
                    <input type="hidden" name="redirect_to" value="${window.location.pathname + window.location.search}">
                    <button type="submit" class="btn btn-bookmark">Bookmark</button> 
                </form>
            `;
            // Info `match.number` (global ayah number) tidak ada di API search alquran.cloud.
            // Kita perlu mengambilnya dari fetch detail ayah jika ingin bookmark dari hasil search.
            // Untuk sementara, kita bisa set `ayah_key_global` ke `match.surah.number * 1000 + match.numberInSurah` sebagai placeholder
            // Atau, lebih baik, ambil dari hasil fetch `ayahDetailResponse` di atas jika sukses.
            // Jika `ayahDetailResult.data[0].number` ada, gunakan itu.

            const resultDiv = document.createElement('div');
            resultDiv.className = 'search-result-item ayah-item';
            resultDiv.innerHTML = `
                <div class="ayah-header">
                     <p class="ayah-reference"><strong>${match.surah.englishName} (${match.surah.name}), Ayah ${match.numberInSurah}</strong></p>
                </div>
                <p class="ayah-text arabic" id="arabic-text-${match.surah.number}-${match.numberInSurah}">${arabicText}</p>
                <p class="ayah-text translation"><em>${match.text} (Translation: ${match.edition.englishName})</em></p>
                <div class="ayah-actions">
                    ${bookmarkFormHtml.replace('value=""', 'value="'+(arabicText.startsWith('Loading') || arabicText.startsWith('Error') ? '' : arabicText )+'"')}
                    ${/* Perlu ayah_key_global yang benar dari API atau hitung */''}
                    <a href="${notesLink}" class="btn btn-notes">Personal Notes</a>
                </div>
            `;
            resultsContainer.appendChild(resultDiv);
            
            // Jika teks Arab baru dimuat, update form bookmark
            if (!arabicText.startsWith('Loading') && !arabicText.startsWith('Error') && arabicText !== 'Failed to load Arabic text.') {
                const formInDiv = resultDiv.querySelector('form[action="/quran/bookmark/toggle"] input[name="ayah_text"]');
                if(formInDiv) formInDiv.value = escapeQuotes(arabicText);

                // Jika kita dapat nomor ayat global dari detail fetch
                if (typeof ayahDetailResult !== 'undefined' && ayahDetailResult.success && ayahDetailResult.data && ayahDetailResult.data[0] && ayahDetailResult.data[0].number) {
                    const globalAyahNumberInput = resultDiv.querySelector('form[action="/quran/bookmark/toggle"] input[name="ayah_key_global"]');
                    if(globalAyahNumberInput) globalAyahNumberInput.value = ayahDetailResult.data[0].number;
                } else {
                     // Fallback jika tidak ada nomor global, ini kurang ideal
                    const globalAyahNumberInput = resultDiv.querySelector('form[action="/quran/bookmark/toggle"] input[name="ayah_key_global"]');
                    if(globalAyahNumberInput) globalAyahNumberInput.value = match.surah.number * 10000 + match.numberInSurah; // Placeholder kasar
                }
            }
             // Update teks Arab secara dinamis setelah fetch
            const arabicP = document.getElementById(`arabic-text-${match.surah.number}-${match.numberInSurah}`);
            if(arabicP && arabicText !== arabicP.textContent) { // Cek untuk menghindari re-render yang tidak perlu
                 if (!arabicText.startsWith('Loading') && !arabicText.startsWith('Error')) {
                    arabicP.textContent = arabicText;
                 }
            }

        }
        contentDisplay.appendChild(resultsContainer);
        
        if (window.updateReadingHistoryTable) {
            window.updateReadingHistoryTable();
        }
    }


    // --- Reading History Table (Fungsi ini mungkin perlu dipanggil dari Blade setelah load awal atau setelah aksi) ---
    // Fungsi `updateReadingHistoryTable` dipanggil dari Blade:
    // `@push('scripts') <script> function updateReadingHistoryTable() { /* ... fetch and re-render ... */ } </script> @endpush`
    // Atau, `historyTableBody` di-render langsung oleh Blade dan JS tidak perlu mengelolanya.
    // Untuk sekarang, saya akan biarkan fungsi `renderHistoryTable` dan `addToReadingHistoryFrontend`
    // jika ada bagian yang masih ingin menggunakannya secara terbatas.

    let localReadingHistory = []; // Riwayat lokal sementara, mungkin tidak sinkron sempurna dengan session

    function addToReadingHistoryFrontend(surahName, ayahNumberOrRange, surahNumberGlobal, surahArabicName = '', ayahKeyGlobal = null) {
        const timestamp = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        
        const newItemKey = `s${surahNumberGlobal}a${ayahNumberOrRange}`;

        // Hapus item yang sama (berdasarkan surah dan ayat/range) jika ada, untuk memindahkannya ke atas
        localReadingHistory = localReadingHistory.filter(item => {
            const existingItemKey = `s${item.surah_number}a${item.ayat}`;
            return existingItemKey !== newItemKey;
        });

        const newHistoryItem = {
            user: 'You', // Placeholder
            surah: `${surahName}${surahArabicName ? ' (' + surahArabicName + ')' : ''}`,
            ayat: ayahNumberOrRange,
            tanggal: timestamp, // Format tanggal bisa disesuaikan
            surah_number: surahNumberGlobal,
            ayah_key_global: ayahKeyGlobal 
        };

        localReadingHistory.unshift(newHistoryItem); // Tambah ke awal
        if (localReadingHistory.length > 10) { // Batasi jumlah item
            localReadingHistory = localReadingHistory.slice(0, 10);
        }
        renderHistoryTable(localReadingHistory); // Render ulang tabel dengan data lokal
    }

    function renderHistoryTable(historyData) {
        if (!historyTableBody) return;
        historyTableBody.innerHTML = ''; // Kosongkan tabel dulu

        if (historyData.length === 0) {
            const row = historyTableBody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 5; // Sesuaikan dengan jumlah kolom
            cell.textContent = 'No reading history yet.';
            cell.style.textAlign = 'center';
            return;
        }

        historyData.forEach(item => {
            const row = historyTableBody.insertRow();
            row.insertCell().textContent = item.user;
            const surahCell = row.insertCell();
            // Buat link jika surah_number dan ayah_key_global ada
            if (item.surah_number && (item.ayat === 'Full Surah' || item.ayah_key_global)) {
                 const surahLink = document.createElement('a');
                 surahLink.href = '#'; // Atau link ke halaman spesifik jika ada
                 surahLink.textContent = item.surah;
                 surahLink.onclick = async (e) => {
                     e.preventDefault();
                     if(item.ayat === 'Full Surah') {
                        await fetchSurahDetails(item.surah_number);
                     } else {
                        // Jika ingin menampilkan ayat spesifik, perlu fungsi atau logic tambahan
                        // Untuk sekarang, klik riwayat ayat spesifik akan membuka surah penuh
                        await fetchSurahDetails(item.surah_number); 
                        // Bisa scroll ke ayat tertentu jika ID elemennya diketahui/konsisten
                        // setTimeout(() => {
                        //    const ayahEl = document.querySelector(`.ayah-item[data-ayah-global="${item.ayah_key_global}"]`);
                        //    if(ayahEl) ayahEl.scrollIntoView({ behavior: 'smooth' });
                        // }, 500);
                     }
                 };
                 surahCell.appendChild(surahLink);
            } else {
                surahCell.textContent = item.surah;
            }
            row.insertCell().textContent = item.ayat;
            row.insertCell().textContent = item.tanggal;
            
            // Kolom aksi (misal: hapus dari history, jika ada fiturnya)
            // const actionCell = row.insertCell();
            // actionCell.innerHTML = '<button class="btn-delete-history-item" data-id="unique_id_item">X</button>';
        });
    }

    // --- Utility Functions ---
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    function escapeQuotes(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    
    // Panggil fungsi untuk memuat daftar Surah saat sidebar pertama kali coba dibuka,
    // atau jika pengguna langsung ke halaman dan sidebar sudah terbuka (jika ada state yang disimpan).
    // Untuk sekarang, daftar Surah hanya dimuat saat link "Daftar Surah" diklik.
    
    // Inisialisasi awal jika ada data history dari Blade (misalnya, dari session)
    // Ini akan di-render oleh Blade. JS hanya mengelola update dinamis jika ada.
    // Jika `window.initialReadingHistory` di-set oleh Blade di `quran.index.blade.php`,
    // kita bisa memuatnya di sini.
    // if (window.initialReadingHistory && Array.isArray(window.initialReadingHistory)) {
    //     localReadingHistory = window.initialReadingHistory;
    //     renderHistoryTable(localReadingHistory);
    // } else {
    //    renderHistoryTable([]); // Tampilkan tabel kosong jika tidak ada data awal
    // }


    // Jika ada pesan flash dari server (misalnya setelah POST bookmark/catatan)
    // kita bisa tampilkan menggunakan alert atau notifikasi JS sederhana.
    // Ini adalah contoh, idealnya menggunakan komponen notifikasi yang lebih baik.
    const flashMessageSuccess = document.getElementById('flash-message-success');
    const flashMessageError = document.getElementById('flash-message-error');

    if (flashMessageSuccess && flashMessageSuccess.textContent.trim() !== '') {
        // alert('Success: ' + flashMessageSuccess.textContent.trim());
        // Bisa diganti dengan toast notification library
        console.log('Flash Success:', flashMessageSuccess.textContent.trim());
    }
    if (flashMessageError && flashMessageError.textContent.trim() !== '') {
        // alert('Error: ' + flashMessageError.textContent.trim());
        console.log('Flash Error:', flashMessageError.textContent.trim());
    }


    // --- Inisialisasi ---
    // Fetch daftar Surah jika sidebar sudah terlihat (misalnya, jika user kembali ke halaman)
    // atau biarkan user mengklik link "Daftar Surah".
    // Jika Anda ingin daftar Surah otomatis termuat jika kontainernya visible:
    // if (surahSublistContainer && getComputedStyle(surahSublistContainer).display !== 'none') {
    //    fetchAndRenderAllSurahs();
    // }

    // Render tabel history awal (jika tidak di-render sepenuhnya oleh Blade)
    // Jika quran.index.blade.php sudah merender #historyTableBody, ini tidak perlu.
    // renderHistoryTable(localReadingHistory); // Atau ambil dari data yang mungkin disisipkan Blade

});

/*
                // Fetch Arabic text and potentially other translations for the specific ayah using our backend endpoint
                // The backend /api/quran/ayah/{ref} can get multiple editions
                const ayahDetailsResponse = await fetch(`${APP_API_BASE_URL}/ayah/${ayahRef}?editions=quran-uthmani,${match.edition.identifier}`);
                if (!ayahDetailsResponse.ok) throw new Error('Failed to fetch Ayah details');
                const ayahDetailsResult = await ayahDetailsResponse.json();

                if (ayahDetailsResult.success && ayahDetailsResult.data && ayahDetailsResult.data.length > 0) {
                    const arabicAyah = ayahDetailsResult.data.find(ed => ed.edition.identifier === 'quran-uthmani');
                    const translationAyah = ayahDetailsResult.data.find(ed => ed.edition.identifier === match.edition.identifier);
                    
                    const arabicText = arabicAyah ? arabicAyah.text : 'Error loading Arabic text.';
                    const translationText = translationAyah ? translationAyah.text : match.text; // Fallback to original match text

                    const isBookmarked = bookmarks.some(b => b.ref === ayahRef);
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'search-result-item ayah-item';
                    resultDiv.innerHTML = `
                        <p class="ayah-surah"><strong>Surah ${match.surah.englishName} (${match.surah.name}), Ayah ${match.numberInSurah}</strong></p>
                        <p class="ayah-text arabic">${arabicText}</p>
                        <p class="ayah-text translation"><em>${translationText} (Translation: ${match.edition.englishName})</em></p>
                        <button class="bookmark-btn ${isBookmarked ? 'bookmarked' : ''}" 
                                data-ayah-ref="${ayahRef}" 
                                data-arabic-text="${escapeSingleQuotes(arabicText)}" 
                                data-translation-text="${escapeSingleQuotes(translationText)}">
                                ${isBookmarked ? 'Bookmarked' : 'Bookmark'}
                        </button>
                    `;
                    resultDiv.querySelector('.bookmark-btn').addEventListener('click', function() {
                        handleBookmarkClick(this.dataset.ayahRef, this.dataset.arabicText, this.dataset.translationText);
                    });
                    resultsContainer.appendChild(resultDiv);
                } else {
                    throw new Error(ayahDetailsResult.message || 'Ayah details not found.');
                }
            } catch (error) {
                console.error(`Error fetching/rendering details for matched ayah ${ayahRef}:`, error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'search-result-item error-item';
                errorDiv.innerHTML = `<p>Error loading details for Ayah ${ayahRef} in Surah ${match.surah.englishName}.</p>`;
                resultsContainer.appendChild(errorDiv);
            }
        }
        contentDisplay.appendChild(resultsContainer);
        renderBookmarksDisplay(); // Update bookmark display
    }

    // --- Tafsir/Notes (Placeholder - CUD features for user notes) ---
    // These would require significant UI (forms) and backend for storage if not using local storage.
    function addCustomNote(ayatRef) {
        const note = prompt(`Enter your note/tafsir for Ayah ${ayatRef}:`);
        if (note) {
            console.log(`Note for ${ayatRef}: ${note}`);
            alert('Adding custom notes is not fully implemented with backend yet.');
            // Save to local storage or send to backend
        }
    }

    // --- Reading History (Frontend part, if needed for immediate UI update) ---
    function addToReadingHistoryFrontend(surahName, ayahNumberOrRange, surahNumberGlobal, surahArabicName) {
        const timestamp = new Date().toLocaleDateString();
        const userPlaceholder = historyTableBody.rows.length + 1;

        const newHistoryItem = {
            user: userPlaceholder,
            surah: `${surahName} (${surahArabicName})`,
            ayat: ayahNumberOrRange,
            tanggal: timestamp,
            surah_number: surahNumberGlobal
        };

        // Add to a JS array if needed for other dynamic updates
        // readingHistory.unshift(newHistoryItem);
        // if (readingHistory.length > 10) readingHistory.pop();

        // Update the table directly for immediate feedback
        // This avoids waiting for a full page reload if the backend updates session
        // and the main page is re-rendered by Blade.
        const newRow = historyTableBody.insertRow(0); // Insert at the top
        newRow.insertCell().textContent = newHistoryItem.user;
        const surahCell = newRow.insertCell();
        const surahLink = document.createElement('a');
        surahLink.href = `#surah-${newHistoryItem.surah_number}`;
        surahLink.textContent = newHistoryItem.surah;
        surahLink.dataset.surahNumber = newHistoryItem.surah_number;
        surahLink.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetchSurahDetails(newHistoryItem.surah_number);
        });
        surahCell.appendChild(surahLink);
        newRow.insertCell().textContent = newHistoryItem.ayat;
        newRow.insertCell().textContent = newHistoryItem.tanggal;

        // Limit rows in the table display if it grows too large
        while (historyTableBody.rows.length > 10) {
            historyTableBody.deleteRow(historyTableBody.rows.length - 1);
        }
    }

    // --- Bookmark Functions (Using Local Storage) ---
    function loadBookmarks() {
        const storedBookmarks = localStorage.getItem('quranBookmarks');
        return storedBookmarks ? JSON.parse(storedBookmarks) : [];
    }

    function saveBookmarks() {
        localStorage.setItem('quranBookmarks', JSON.stringify(bookmarks));
    }

    function handleBookmarkClick(ayahRef, arabicText, translationText = '') {
        const existingBookmarkIndex = bookmarks.findIndex(b => b.ref === ayahRef);
        if (existingBookmarkIndex > -1) {
            bookmarks.splice(existingBookmarkIndex, 1); // Unbookmark
            alert(`Ayah ${ayahRef} bookmark removed.`);
        } else {
            bookmarks.push({ ref: ayahRef, arabic: arabicText, translation: translationText, added: new Date().toISOString() });
            alert(`Ayah ${ayahRef} bookmarked!`);
        }
        saveBookmarks();
        updateBookmarkButtonStatus(ayahRef, existingBookmarkIndex === -1);
        renderBookmarksDisplay();
        // Update all relevant bookmark buttons on the page
        document.querySelectorAll(`.bookmark-btn[data-ayah-ref="${ayahRef}"]`).forEach(btn => {
            btn.classList.toggle('bookmarked', existingBookmarkIndex === -1);
            btn.textContent = existingBookmarkIndex === -1 ? 'Bookmarked' : 'Bookmark';
        });
    }

    function updateBookmarkButtonStatus(ayahRef, isBookmarked) {
        const buttons = document.querySelectorAll(`.bookmark-btn[data-ayah-ref="${ayahRef}"]`);
        buttons.forEach(button => {
            button.textContent = isBookmarked ? 'Bookmarked' : 'Bookmark';
            button.classList.toggle('bookmarked', isBookmarked);
        });
    }

    function renderBookmarksDisplay() {
        if (!bookmarksDisplayContainer) return;
        bookmarksDisplayContainer.innerHTML = '';
        if (bookmarks.length === 0) {
            bookmarksDisplayContainer.innerHTML = '<p>No bookmarks yet.</p>';
            return;
        }

        const h4 = document.createElement('h4');
        h4.textContent = 'My Bookmarks';
        bookmarksDisplayContainer.appendChild(h4);

        const ul = document.createElement('ul');
        ul.className = 'bookmarks-list';
        bookmarks.forEach(bm => {
            const li = document.createElement('li');
            li.innerHTML = `
                Surah:Ayah ${bm.ref} - <em>"${bm.arabic ? bm.arabic.substring(0, 50) : 'N/A'}..."</em>
                <button class="view-bookmark-btn" data-ayah-ref="${bm.ref}">View</button>
                <button class="remove-bookmark-btn" data-ayah-ref="${bm.ref}">Remove</button>
            `;
            li.querySelector('.view-bookmark-btn').addEventListener('click', async function() {
                // Logic to fetch and display the bookmarked Ayah
                // This might involve parsing bm.ref and calling fetchSurahDetails or a specific Ayah fetch
                const [surahNum, ayahNumInSurah] = bm.ref.split(':');
                // For simplicity, we can fetch the whole surah and scroll to the ayah, 
                // or have a more specific display for single bookmarked ayahs.
                // For now, let's just log it, a proper display would be more involved.
                console.log('Viewing bookmark:', bm);
                alert('Viewing bookmarked ayah: Feature to display specific bookmarked ayah needs detailed implementation.');
                // Potentially: fetchAyahDetailsForBookmark(bm.ref);
            });
            li.querySelector('.remove-bookmark-btn').addEventListener('click', function() {
                handleBookmarkClick(this.dataset.ayahRef, bm.arabic, bm.translation); // Trigger unbookmark
            });
            ul.appendChild(li);
        });
        bookmarksDisplayContainer.appendChild(ul);
    }

    // --- Utility Functions ---
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    function escapeSingleQuotes(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/'/g, '\\\'');
    }

    // --- Initial Load / Setup ---
    // Fetch initial data if needed (e.g., if sidebar surah list should be pre-loaded without click)
    // await fetchAndRenderAllSurahs(); // Uncomment if you want surah list loaded on page start
    
    // The main Blade view (quran/index.blade.php) can render initial history from session.
    // renderReadingHistory(); // No longer solely JS responsibility if backend provides initial data.
    renderBookmarksDisplay(); // Load and display bookmarks from local storage

    console.log('Event listeners and API functions set up for PHP/Blade.');
});

/*
CSS to add to style.css:

.ayah-text.arabic {
    font-family: 'Traditional Arabic', 'Al Mushaf', 'KFGQPC Uthman Taha Naskh', serif;
    font-size: 1.6em; /* Slightly larger for readability */
    direction: rtl;
    line-height: 1.8;
    text-align: right;
    margin-bottom: 10px;
}

.ayah-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
    margin-bottom: 10px;
}
.ayah-item:last-child {
    border-bottom: none;
}

.ayah-number {
    font-weight: bold;
    color: #1abc9c;
    margin-right: 10px;
    float: left; /* Align number to the left of Arabic text if not RTL context */
}
html[dir="rtl"] .ayah-number {
    float: right;
    margin-left: 10px;
    margin-right: 0;
}

.ayah-text.translation {
    font-style: italic;
    color: #555;
    font-size: 0.95em;
    margin-top: 5px;
}

.bookmark-btn {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.8em;
    margin-top: 5px;
}
.bookmark-btn:hover {
    background-color: #2980b9;
}
.bookmark-btn.bookmarked {
    background-color: #2ecc71; /* Green when bookmarked */
}
.bookmark-btn.bookmarked:hover {
    background-color: #27ae60;
}

.surah-sublist {
    list-style: none;
    padding-left: 20px; /* Indent sublist */
    max-height: 300px; /* Example max height */
    overflow-y: auto;   /* Scroll if too many surahs */
    display: none; /* Hidden by default, shown on click */
}
.surah-sublist li a {
    font-size: 0.95em;
    padding: 8px 15px;
}

#bookmarksDisplayContainer {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}
#bookmarksDisplayContainer h4 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}
.bookmarks-list {
    list-style: none;
    padding: 0;
}
.bookmarks-list li {
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.bookmarks-list li:last-child {
    border-bottom: none;
}
.bookmarks-list .remove-bookmark-btn, .bookmarks-list .view-bookmark-btn {
    font-size: 0.8em;
    padding: 3px 7px;
    margin-left: 5px;
}

.search-result-item.error-item p {
    color: red;
}

*/ 