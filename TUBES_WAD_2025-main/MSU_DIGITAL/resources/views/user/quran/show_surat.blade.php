@extends('layouts.app') {{-- Atau layout user Anda --}}

@section('title', $surahDetails['englishName'] ?? 'Surah Detail')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
    .ayah-text {
        font-size: 1.8rem; /* Ukuran font Arab */
        line-height: 2.5;
        text-align: right;
        margin-bottom: 0.5rem;
        direction: rtl;
    }
    .translation-text {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        color: #555;
        text-align: left;
        direction: ltr;
    }
    .ayah-container {
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }
    .ayah-number-badge {
        float: left; 
        margin-right: 10px; 
        font-size: 0.9rem;
        background-color: #28a745; /* Warna hijau seperti di gambar */
        color: white;
        padding: 5px 8px;
        border-radius: 50%;
        min-width: 28px; /* Agar buletannya konsisten */
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .ayah-actions {
        float: right;
        font-size: 1.2rem;
    }
    .ayah-actions .btn-bookmark, .ayah-actions .btn-add-note {
        color: #6c757d;
        padding: 0.2rem 0.4rem;
        text-decoration: none;
        border: none;
        background: none;
    }
    .ayah-actions .btn-bookmark.bookmarked,
    .ayah-actions .btn-bookmark:hover,
    .ayah-actions .btn-add-note:hover {
        color: #28a745;
    }
    .sticky-surah-header {
        position: sticky;
        top: 60px; /* Sesuaikan dengan tinggi navbar Anda */
        background-color: white;
        z-index: 1000;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }
    .surah-title-container {
        background-color: #28a745; /* Warna hijau header */
        color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .btn-audio {
        color: #6c757d;
        padding: 0.2rem 0.4rem;
        text-decoration: none;
        border: none;
        background: none;
        cursor: pointer;
    }
    
    .btn-audio:hover {
        color: #28a745;
    }
    
    .btn-audio.playing {
        color: #28a745;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .audio-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 10px;
    }

    .audio-progress {
        flex-grow: 1;
        height: 4px;
        background: #e9ecef;
        border-radius: 2px;
        position: relative;
        cursor: pointer;
    }

    .audio-progress-bar {
        height: 100%;
        background: #28a745;
        border-radius: 2px;
        width: 0%;
    }

    .audio-time {
        font-size: 0.8rem;
        color: #6c757d;
        min-width: 45px;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    @if(isset($surahDetails) && $surahDetails)
        <div class="surah-title-container text-center sticky-surah-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    {{-- Tombol Navigasi Surah Sebelumnya (jika bukan surah pertama) --}}
                    @if($surahDetails['number'] > 1)
                        <a href="{{ route('surah.show', $surahDetails['number'] - 1) }}" class="btn btn-light btn-sm">&laquo; Sebelumnya</a>
                    @endif
                </div>
                <h2 class="h4 mb-0">{{ $surahDetails['name'] }} <small>({{ $surahDetails['englishName'] }})</small></h2>
                <div>
                    {{-- Tombol Navigasi Surah Berikutnya (jika bukan surah terakhir) --}}
                    @if($surahDetails['number'] < 114)
                        <a href="{{ route('surah.show', $surahDetails['number'] + 1) }}" class="btn btn-light btn-sm">Berikutnya &raquo;</a>
                    @endif
                </div>
            </div>
            <div class="mt-2">
                <small>{{ $surahDetails['revelationType'] }} - {{ $surahDetails['numberOfAyahs'] }} ayat</small>
            </div>
            <div class="mt-2">
                <button id="toggleArabic" class="btn btn-outline-light btn-sm">Toggle Teks Arab</button>
                <button id="toggleTranslation" class="btn btn-outline-light btn-sm">Toggle Terjemahan</button>
            </div>
        </div>

        <div class="mt-3">
            @if($surahDetails['number'] != 1 && $surahDetails['number'] != 9) {{-- Kecuali Al-Fatihah & At-Taubah --}}
                <div class="ayah-container bismillah-container">
                    <p class="ayah-text arabic-text">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</p>
                    <p class="translation-text translation-text-content">Dengan nama Allah Yang Maha Pengasih, Maha Penyayang.</p>
                </div>
            @endif

            @foreach ($surahDetails['ayahs'] as $ayah)
                <div class="ayah-container" id="ayah-{{ $ayah['numberInSurah'] }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="ayah-number-badge">{{ $ayah['numberInSurah'] }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="ayah-text arabic-text">{{ $ayah['text'] }}</p>
                            <p class="translation-text translation-text-content">{{ $ayah['translation_text'] }}</p>
                            <div class="audio-controls" id="audio-controls-{{ $ayah['numberInSurah'] }}">
                                <button class="btn-audio" 
                                        data-ayah-number="{{ $ayah['numberInSurah'] }}"
                                        data-audio-url="{{ $ayah['audioUrl'] ?? '' }}"
                                        title="Play Audio">
                                    <i class="bi bi-play-circle"></i>
                                </button>
                                <div class="audio-progress">
                                    <div class="audio-progress-bar"></div>
                                </div>
                                <span class="audio-time">00:00</span>
                            </div>
                        </div>
                        <div class="ayah-actions ms-2">
                            <button class="btn-bookmark {{ $ayah['is_bookmarked'] ? 'bookmarked' : '' }}" 
                                    data-identifier="{{ $ayah['api_ayat_identifier'] }}" 
                                    title="{{ $ayah['is_bookmarked'] ? 'Hapus Bookmark' : 'Tambah Bookmark' }}">
                                <i class="bi {{ $ayah['is_bookmarked'] ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
                            </button>
                            <button class="btn-add-note" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#personalNoteModal"
                                    data-ayat-identifier="{{ $ayah['api_ayat_identifier'] }}"
                                    data-ayat-text="{{ Str::limit($ayah['text'], 100) }}"
                                    data-note-content="{{ $ayah['personal_note'] ? $ayah['personal_note']->note : '' }}"
                                    data-note-id="{{ $ayah['personal_note'] ? $ayah['personal_note']->id : '' }}"
                                    title="Tambah/Edit Catatan Pribadi">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="alert alert-danger">Gagal memuat detail surat atau surat tidak ditemukan.</div>
        <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Daftar Surat</a>
    @endif
</div>

<!-- Modal Catatan Pribadi -->
<div class="modal fade" id="personalNoteModal" tabindex="-1" aria-labelledby="personalNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="personalNoteForm" method="POST" action="{{ route('notes.add') }}"> {{-- Action akan diupdate oleh JS --}}
                @csrf
                <input type="hidden" name="_method" id="note_method" value="POST"> 
                <input type="hidden" name="api_ayat_identifier" id="modal_api_ayat_identifier">
                <input type="hidden" name="note_id" id="modal_note_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="personalNoteModalLabel">Catatan Pribadi untuk Ayat...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Ayat:</strong> <small id="modal_ayat_text_preview"></small></p>
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Catatan Anda:</label>
                        <textarea class="form-control" id="note_content" name="note" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="deleteNoteButton" style="display:none;">Hapus Catatan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Arabic Text
    const toggleArabicBtn = document.getElementById('toggleArabic');
    if (toggleArabicBtn) {
        toggleArabicBtn.addEventListener('click', function() {
            document.querySelectorAll('.arabic-text').forEach(el => {
                el.style.display = el.style.display === 'none' ? '' : 'none';
            });
        });
    }

    // Toggle Translation Text
    const toggleTranslationBtn = document.getElementById('toggleTranslation');
    if (toggleTranslationBtn) {
        toggleTranslationBtn.addEventListener('click', function() {
            document.querySelectorAll('.translation-text-content').forEach(el => {
                el.style.display = el.style.display === 'none' ? '' : 'none';
            });
        });
    }

    // Handle Bookmark Toggles
    document.querySelectorAll('.btn-bookmark').forEach(btn => {
        btn.addEventListener('click', function() {
            const identifier = this.dataset.identifier;
            const icon = this.querySelector('i');
            
            fetch('/bookmark/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    api_ayat_identifier: identifier
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle bookmark icon
                    if (data.is_bookmarked) {
                        icon.classList.remove('bi-bookmark');
                        icon.classList.add('bi-bookmark-fill');
                        this.classList.add('bookmarked');
                        this.title = 'Hapus Bookmark';
                    } else {
                        icon.classList.remove('bi-bookmark-fill');
                        icon.classList.add('bi-bookmark');
                        this.classList.remove('bookmarked');
                        this.title = 'Tambah Bookmark';
                    }
                    
                    // Show success message
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message || 'Gagal memproses bookmark');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Terjadi kesalahan saat memproses bookmark');
            });
        });
    });

    // Handle Personal Notes
    const personalNoteModal = document.getElementById('personalNoteModal');
    if (personalNoteModal) {
        personalNoteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const identifier = button.dataset.ayatIdentifier;
            const ayatText = button.dataset.ayatText;
            const noteContent = button.dataset.noteContent;
            const noteId = button.dataset.noteId;
            
            const form = this.querySelector('#personalNoteForm');
            const textarea = this.querySelector('#note_content');
            const preview = this.querySelector('#modal_ayat_text_preview');
            const deleteButton = this.querySelector('#deleteNoteButton');
            
            preview.textContent = ayatText;
            textarea.value = noteContent;
            form.querySelector('#modal_api_ayat_identifier').value = identifier;
            form.querySelector('#modal_note_id').value = noteId;
            
            // Show/hide delete button based on whether note exists
            deleteButton.style.display = noteId ? 'block' : 'none';
            
            // Update form action and method based on whether we're creating or updating
            if (noteId) {
                form.action = `/notes/${noteId}`;
                form.querySelector('#note_method').value = 'PUT';
            } else {
                form.action = '/notes/add';
                form.querySelector('#note_method').value = 'POST';
            }
        });

        // Handle form submission
        const noteForm = personalNoteModal.querySelector('#personalNoteForm');
        noteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const method = this.querySelector('#note_method').value;
            
            fetch(this.action, {
                method: method === 'PUT' ? 'POST' : method,
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(personalNoteModal).hide();
                    
                    // Update UI
                    const button = document.querySelector(`[data-ayat-identifier="${formData.get('api_ayat_identifier')}"]`);
                    if (button) {
                        button.dataset.noteContent = formData.get('note');
                        button.dataset.noteId = data.note.id;
                        button.querySelector('i').style.color = '#28a745'; // Highlight icon to show note exists
                    }
                    
                    // Show success message
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message || 'Gagal menyimpan catatan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Terjadi kesalahan saat menyimpan catatan');
            });
        });

        // Handle note deletion
        const deleteButton = personalNoteModal.querySelector('#deleteNoteButton');
        deleteButton.addEventListener('click', function() {
            const noteId = personalNoteModal.querySelector('#modal_note_id').value;
            if (!noteId) return;

            if (confirm('Anda yakin ingin menghapus catatan ini?')) {
                fetch(`/notes/${noteId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(personalNoteModal).hide();
                        
                        // Update UI
                        const button = document.querySelector(`[data-ayat-identifier="${document.querySelector('#modal_api_ayat_identifier').value}"]`);
                        if (button) {
                            button.dataset.noteContent = '';
                            button.dataset.noteId = '';
                            button.querySelector('i').style.color = '#6c757d'; // Reset icon color
                        }
                        
                        // Show success message
                        showAlert('success', data.message);
                    } else {
                        showAlert('danger', data.message || 'Gagal menghapus catatan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Terjadi kesalahan saat menghapus catatan');
                });
            }
        });
    }

    // Audio Player Functionality
    let currentAudio = null;
    let currentButton = null;

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function resetPreviousAudio() {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            if (currentButton) {
                const icon = currentButton.querySelector('i');
                icon.classList.remove('bi-pause-circle');
                icon.classList.add('bi-play-circle');
                currentButton.classList.remove('playing');
                
                // Reset progress bar
                const controls = currentButton.closest('.audio-controls');
                const progressBar = controls.querySelector('.audio-progress-bar');
                const timeDisplay = controls.querySelector('.audio-time');
                progressBar.style.width = '0%';
                timeDisplay.textContent = '00:00';
            }
        }
    }

    document.querySelectorAll('.btn-audio').forEach(button => {
        button.addEventListener('click', function() {
            const audioUrl = this.dataset.audioUrl;
            const icon = this.querySelector('i');
            const controls = this.closest('.audio-controls');
            const progressBar = controls.querySelector('.audio-progress-bar');
            const timeDisplay = controls.querySelector('.audio-time');
            const progressContainer = controls.querySelector('.audio-progress');

            if (currentAudio && currentButton === this) {
                // Pause current audio
                if (currentAudio.paused) {
                    currentAudio.play();
                    icon.classList.remove('bi-play-circle');
                    icon.classList.add('bi-pause-circle');
                    this.classList.add('playing');
                } else {
                    currentAudio.pause();
                    icon.classList.remove('bi-pause-circle');
                    icon.classList.add('bi-play-circle');
                    this.classList.remove('playing');
                }
                return;
            }

            // Reset previous audio if exists
            resetPreviousAudio();

            // Create new audio
            currentAudio = new Audio(audioUrl);
            currentButton = this;

            currentAudio.addEventListener('loadedmetadata', () => {
                timeDisplay.textContent = formatTime(currentAudio.duration);
            });

            currentAudio.addEventListener('timeupdate', () => {
                const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
                progressBar.style.width = `${progress}%`;
                timeDisplay.textContent = formatTime(currentAudio.currentTime);
            });

            currentAudio.addEventListener('ended', () => {
                icon.classList.remove('bi-pause-circle');
                icon.classList.add('bi-play-circle');
                this.classList.remove('playing');
                progressBar.style.width = '0%';
                timeDisplay.textContent = '00:00';
                currentAudio = null;
                currentButton = null;
            });

            // Add click event for progress bar
            progressContainer.addEventListener('click', function(e) {
                if (currentAudio) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const width = rect.width;
                    const percentage = x / width;
                    currentAudio.currentTime = currentAudio.duration * percentage;
                }
            });

            // Play new audio
            currentAudio.play();
            icon.classList.remove('bi-play-circle');
            icon.classList.add('bi-pause-circle');
            this.classList.add('playing');
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto dismiss after 3 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
});
</script>
@endpush 