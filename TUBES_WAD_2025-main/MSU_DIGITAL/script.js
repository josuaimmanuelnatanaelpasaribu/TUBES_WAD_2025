// script.js - MSU Digital Quran

document.addEventListener('DOMContentLoaded', () => {
    console.log('MSU Digital Quran App Initialized');

    // --- API Base URL ---
    const API_BASE_URL = 'http://api.alquran.cloud/v1';

    // --- DOM Elements ---
    const searchInput = document.querySelector('.sidebar-search input[type="text"]');
    const clearSearchBtn = document.querySelector('.clear-search-btn');
    const contentDisplay = document.querySelector('.content-display');
    const historyTableBody = document.querySelector('.history-table tbody');
    const daftarSurahLink = document.querySelector('.sidebar-nav ul li a[href="#"]'); // Assuming the second link is Daftar Surah
    const daftarSurahNav = document.querySelector('.sidebar-nav ul li:nth-child(3) a'); // More specific selector for "Daftar Surah"
    const sidebarNav = document.querySelector('.sidebar-nav ul');

    // --- App State & Data ---
    let searchHistory = []; // For keywords
    let readingHistory = []; // For Surah/Ayah interactions, to populate "History Bacaan"
    let bookmarks = []; // For bookmarked ayahs
    let allSurahs = []; // To store the list of all surahs

    // --- Event Listeners ---
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            contentDisplay.innerHTML = ''; // Clear content display
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', debounce((event) => {
            const query = event.target.value.trim().toLowerCase();
            if (query) {
                searchQuran(query);
            } else {
                contentDisplay.innerHTML = ''; // Clear if search query is empty
            }
        }, 500));
    }

    // --- API Functions ---

    /**
     * Fetches all Surahs from the API.
     */
    async function fetchAllSurahs() {
        try {
            const response = await fetch(`${API_BASE_URL}/surah`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            allSurahs = data.data; // Assuming the surahs are in data.data
            console.log('All Surahs fetched:', allSurahs);
            renderSurahListInSidebar(allSurahs);
            // By default, load the first surah or a welcome message
            if (allSurahs.length > 0) {
                // fetchSurahDetails(allSurahs[0].number); // Load first surah by default
                contentDisplay.innerHTML = '<p>Select a Surah from the list or use the search.</p>';
            }
        } catch (error) {
            console.error('Error fetching all Surahs:', error);
            contentDisplay.innerHTML = '<p>Error loading Surah list. Please try again later.</p>';
        }
    }

    /**
     * Renders the list of Surahs in the sidebar.
     */
    function renderSurahListInSidebar(surahs) {
        const surahListContainer = document.createElement('ul');
        surahListContainer.className = 'surah-sublist'; // For styling if needed

        surahs.forEach(surah => {
            const listItem = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#surah-${surah.number}`;
            link.textContent = `${surah.number}. ${surah.englishName} (${surah.name})`;
            link.dataset.surahNumber = surah.number;
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchSurahDetails(surah.number);
                // Update active class
                document.querySelectorAll('.sidebar-nav ul li a, .surah-sublist li a').forEach(a => a.classList.remove('active'));
                link.classList.add('active');

            });
            listItem.appendChild(link);
            surahListContainer.appendChild(listItem);
        });

        // Attach to the "Daftar Surah" link or a dedicated container
        const daftarSurahLi = daftarSurahNav.parentElement;
        // Clear previous sublist if any
        const existingSublist = daftarSurahLi.querySelector('.surah-sublist');
        if (existingSublist) {
            existingSublist.remove();
        }
        // daftarSurahLi.appendChild(surahListContainer); // Appends as a sub-menu

        // For now, let's make "Daftar Surah" clickable to toggle the list
        daftarSurahNav.addEventListener('click', (e) => {
            e.preventDefault();
            const sublist = daftarSurahLi.querySelector('.surah-sublist');
            if (sublist) {
                sublist.style.display = sublist.style.display === 'none' ? 'block' : 'none';
            } else {
                 // Clear previous sublist if any before appending new one
                const currentSublist = daftarSurahLi.querySelector('.surah-sublist');
                if (currentSublist) {
                    currentSublist.remove();
                }
                daftarSurahLi.appendChild(surahListContainer);
                surahListContainer.style.display = 'block';
            }
             // Update active class
            document.querySelectorAll('.sidebar-nav ul li a').forEach(a => a.classList.remove('active'));
            daftarSurahNav.classList.add('active');
            contentDisplay.innerHTML = '<p>Select a Surah from the list above.</p>';
        });
    }


    /**
     * Fetches details (ayahs) of a specific Surah.
     * Defaults to 'quran-uthmani' edition for Arabic text.
     */
    async function fetchSurahDetails(surahNumber, edition = 'quran-uthmani') {
        try {
            const response = await fetch(`${API_BASE_URL}/surah/${surahNumber}/${edition}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            console.log(`Details for Surah ${surahNumber}:`, data.data);
            renderSurahAyahs(data.data);
            addToReadingHistory(data.data.englishName, 'Full Surah', data.data.number);
        } catch (error) {
            console.error(`Error fetching Surah ${surahNumber}:`, error);
            contentDisplay.innerHTML = `<p>Error loading Surah ${surahNumber}. Please try again later.</p>`;
        }
    }

    /**
     * Renders the Ayahs of a Surah in the main content display.
     */
    function renderSurahAyahs(surahData) {
        contentDisplay.innerHTML = ''; // Clear previous content
        const surahTitle = document.createElement('h3');
        surahTitle.textContent = `${surahData.number}. ${surahData.englishName} (${surahData.name}) - ${surahData.revelationType}`;
        contentDisplay.appendChild(surahTitle);

        const ayahsContainer = document.createElement('div');
        ayahsContainer.className = 'ayahs-container';

        surahData.ayahs.forEach(ayah => {
            const ayahDiv = document.createElement('div');
            ayahDiv.className = 'ayah-item';
            ayahDiv.innerHTML = `
                <p class="ayah-number"><strong>${ayah.numberInSurah}</strong></p>
                <p class="ayah-text arabic">${ayah.text}</p>
                <button class="bookmark-btn" data-ayah-ref="${surahData.number}:${ayah.numberInSurah}">Bookmark</button>
            `;
            // Add event listener for bookmark button
            ayahDiv.querySelector('.bookmark-btn').addEventListener('click', function() {
                bookmarkAyat(this.dataset.ayahRef, ayah.text);
            });
            ayahsContainer.appendChild(ayahDiv);
        });
        contentDisplay.appendChild(ayahsContainer);
    }

    /**
     * Searches the Quran based on a keyword.
     * API endpoint: /search/{{keyword}}/{{surah_number or 'all'}}/{{edition or language}}
     * We'll search all surahs and default to 'en.asad' for English translation results.
     * Then fetch quran-uthmani for arabic text.
     */
    async function searchQuran(keyword, edition = 'en.asad') {
        contentDisplay.innerHTML = '<p>Searching...</p>';
        try {
            // First search in a translation to find matches
            const response = await fetch(`${API_BASE_URL}/search/${keyword}/all/${edition}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const searchResult = await response.json();
            console.log(`Search results for "${keyword}":`, searchResult.data);

            if (searchResult.data && searchResult.data.matches && searchResult.data.matches.length > 0) {
                renderSearchResults(searchResult.data.matches, keyword);
                addToSearchHistory(keyword); // Add keyword search to history
            } else {
                contentDisplay.innerHTML = `<p>No results found for "${keyword}".</p>`;
            }
        } catch (error) {
            console.error(`Error searching Quran for "${keyword}":`, error);
            contentDisplay.innerHTML = `<p>Error performing search for "${keyword}". Please try again later.</p>`;
        }
    }

    /**
     * Renders search results in the main content display.
     * Fetches Arabic text for each matched Ayah.
     */
    async function renderSearchResults(matches, keyword) {
        contentDisplay.innerHTML = `<h3>Search Results for "${keyword}"</h3>`;
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results-container';

        for (const match of matches) {
            const ayahRef = `${match.surah.number}:${match.numberInSurah}`;
            try {
                // Fetch Arabic text for the specific ayah
                const arabicAyahResponse = await fetch(`${API_BASE_URL}/ayah/${ayahRef}/quran-uthmani`);
                if (!arabicAyahResponse.ok) throw new Error('Failed to fetch Arabic text');
                const arabicAyahData = await arabicAyahResponse.json();
                const arabicText = arabicAyahData.data.text;

                // Fetch translation text (already available in match.text, but could re-fetch for consistency or other editions)
                // const translationText = match.text; // from en.asad by default from searchQuran

                const resultDiv = document.createElement('div');
                resultDiv.className = 'search-result-item ayah-item'; // Re-use ayah-item styling
                resultDiv.innerHTML = `
                    <p class="ayah-surah"><strong>Surah ${match.surah.englishName} (${match.surah.name}), Ayah ${match.numberInSurah}</strong></p>
                    <p class="ayah-text arabic">${arabicText}</p>
                    <p class="ayah-text translation"><em>${match.text} (Translation: ${match.edition.englishName})</em></p>
                    <button class="bookmark-btn" data-ayah-ref="${ayahRef}">Bookmark</button>
                `;
                 resultDiv.querySelector('.bookmark-btn').addEventListener('click', function() {
                    bookmarkAyat(this.dataset.ayahRef, arabicText, match.text);
                });
                resultsContainer.appendChild(resultDiv);
            } catch (error) {
                console.error(`Error fetching details for matched ayah ${ayahRef}:`, error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'search-result-item';
                errorDiv.innerHTML = `<p>Error loading details for Ayah ${ayahRef} in Surah ${match.surah.englishName}.</p>`;
                resultsContainer.appendChild(errorDiv);
            }
        }
        contentDisplay.appendChild(resultsContainer);
    }


    // --- CRUD & Feature Functions (Placeholders for Create, Update, Delete Tafsir/Notes) ---

    function addAyatData(data) { // For custom Tafsir/notes
        console.log('Adding Custom Ayat Data/Tafsir:', data);
        // This would involve UI for input and local storage or backend integration.
        alert('Feature to add custom notes/tafsir is not yet implemented.');
    }

    function updateData(id, updatedData) {
        console.log(`Updating custom data for ID ${id}:`, updatedData);
        alert('Feature to update custom notes/tafsir is not yet implemented.');
    }

    function deleteData(id) {
        console.log(`Deleting custom data for ID ${id}`);
        alert('Feature to delete custom notes/tafsir is not yet implemented.');
    }

    // --- History Functions ---

    /**
     * Adds a keyword search query to the search history.
     */
    function addToSearchHistory(query) {
        const timestamp = new Date().toLocaleString();
        // Avoid duplicate consecutive searches
        if (searchHistory.length === 0 || searchHistory[0].query !== query) {
            searchHistory.unshift({ type: 'keyword', query, timestamp });
            if (searchHistory.length > 10) searchHistory.pop();
            // This history is for keywords, "History Bacaan" is different
            // renderGenericSearchHistory(); // If we had a separate display for this
        }
    }

    /**
     * Adds an interaction (e.g., reading a Surah/Ayah) to the reading history.
     * This populates the "History Bacaan" table.
     */
    function addToReadingHistory(surahName, ayahNumberOrRange, surahNumberGlobal) {
        const timestamp = new Date().toLocaleDateString(); // Just date for "Tanggal"
        // For "User" column, assuming a single user or placeholder
        const userPlaceholder = readingHistory.length + 1; // Simple incrementing ID for demo

        // Avoid duplicate consecutive entries for the same Surah read
        if (readingHistory.length === 0 || readingHistory[0].surah !== surahName || readingHistory[0].ayat !== ayahNumberOrRange) {
            readingHistory.unshift({
                user: userPlaceholder,
                surah: surahName,
                ayat: ayahNumberOrRange, // Could be specific Ayah number or "Full Surah"
                tanggal: timestamp,
                surahNumber: surahNumberGlobal // Store for potential re-load
            });
            if (readingHistory.length > 10) readingHistory.pop(); // Keep history to a certain size
            renderReadingHistory();
        }
    }

    /**
     * Renders the reading history in the "History Bacaan" table.
     */
    function renderReadingHistory() {
        if (!historyTableBody) {
            console.warn('History table body not found for rendering reading history.');
            return;
        }
        historyTableBody.innerHTML = ''; // Clear existing rows
        readingHistory.forEach(item => {
            const row = historyTableBody.insertRow();
            row.insertCell().textContent = item.user;
            const surahCell = row.insertCell();
            const surahLink = document.createElement('a');
            surahLink.href = `#surah-${item.surahNumber}`;
            surahLink.textContent = item.surah;
            surahLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetchSurahDetails(item.surahNumber);
            });
            surahCell.appendChild(surahLink);
            row.insertCell().textContent = item.ayat;
            row.insertCell().textContent = item.tanggal;
        });
    }


    // --- Bookmark Functions ---
    /**
     * Bookmarks an Ayat. Stores reference and text.
     * ayahRef is like "2:255" (SurahNumber:AyahNumberInSurah)
     * arabicText and translationText are for display if needed
     */
    function bookmarkAyat(ayahRef, arabicText, translationText = '') {
        const existingBookmark = bookmarks.find(b => b.ref === ayahRef);
        if (!existingBookmark) {
            bookmarks.push({ ref: ayahRef, arabic: arabicText, translation: translationText, added: new Date() });
            console.log(`Ayat ${ayahRef} bookmarked.`);
            alert(`Ayat ${ayahRef} bookmarked!`);
            // Update UI - e.g., change bookmark button appearance
            updateBookmarkButtonStatus(ayahRef, true);
        } else {
            // Optionally allow unbookmarking from the same button
            removeBookmark(ayahRef);
            // console.log(`Ayat ${ayahRef} is already bookmarked. Unbookmarking.`);
            // alert(`Ayat ${ayahRef} is already bookmarked. Click again to unbookmark or manage from bookmarks section.`);
        }
        renderBookmarks(); // Re-render a bookmark section if it exists
    }

    function removeBookmark(ayahRef) {
        const index = bookmarks.findIndex(b => b.ref === ayahRef);
        if (index > -1) {
            bookmarks.splice(index, 1);
            console.log(`Ayat ${ayahRef} bookmark removed.`);
            alert(`Ayat ${ayahRef} bookmark removed!`);
            updateBookmarkButtonStatus(ayahRef, false);
        }
        renderBookmarks();
    }

    function updateBookmarkButtonStatus(ayahRef, isBookmarked) {
        const buttons = document.querySelectorAll(`.bookmark-btn[data-ayah-ref="${ayahRef}"]`);
        buttons.forEach(button => {
            button.textContent = isBookmarked ? 'Bookmarked' : 'Bookmark';
            button.classList.toggle('bookmarked', isBookmarked);
        });
    }
    
    /**
     * Renders bookmarks (e.g., in a dedicated section or modal).
     * For now, just logs to console.
     */
    function renderBookmarks() {
        console.log("Current Bookmarks:", bookmarks);
        // This function would populate a dedicated bookmarks display area.
        // Example: A new div in main-content or a modal.
        // For now, let's add a simple list to the top of contentDisplay if there are bookmarks.
        const existingBookmarksDisplay = contentDisplay.querySelector('.bookmarks-display');
        if (existingBookmarksDisplay) existingBookmarksDisplay.remove();

        if (bookmarks.length > 0) {
            const bookmarksDiv = document.createElement('div');
            bookmarksDiv.className = 'bookmarks-display';
            bookmarksDiv.innerHTML = '<h4>My Bookmarks:</h4>';
            const ul = document.createElement('ul');
            bookmarks.forEach(bm => {
                const li = document.createElement('li');
                li.innerHTML = `Surah:Ayah ${bm.ref} - <em>"${bm.arabic.substring(0, 30)}..."</em> 
                              <button class="remove-bookmark-btn" data-ayah-ref="${bm.ref}">Remove</button>`;
                li.querySelector('.remove-bookmark-btn').addEventListener('click', function() {
                    removeBookmark(this.dataset.ayahRef);
                });
                ul.appendChild(li);
            });
            bookmarksDiv.appendChild(ul);
            // contentDisplay.prepend(bookmarksDiv); // Add to top of current content
        }
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

    // --- Initial Load / Setup ---
    fetchAllSurahs(); // Fetch list of Surahs on page load
    renderReadingHistory(); // Initial render of (empty or loaded from storage) reading history
    renderBookmarks(); // Render any existing bookmarks (e.g. from local storage in future)

    console.log('Event listeners and API functions set up.');
});

// CSS for .arabic class (add to style.css or here in a <style> tag if quick)
// .ayah-text.arabic { font-family: 'Traditional Arabic', 'Al Mushaf', 'KFGQPC Uthman Taha Naskh', serif; font-size: 1.5em; direction: rtl; }
// .bookmark-btn.bookmarked { background-color: #28a745; color: white; }
// .bookmarks-display { margin-top: 20px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; }
// .bookmarks-display h4 { margin-top:0; }
// .bookmarks-display ul { list-style: none; padding-left: 0;}
// .bookmarks-display li { margin-bottom: 5px; padding: 5px; border-bottom: 1px dashed #eee; }

// Example function to simulate fetching Quran data (replaced by API calls)
// async function fetchQuranData() { /* ... */ } 