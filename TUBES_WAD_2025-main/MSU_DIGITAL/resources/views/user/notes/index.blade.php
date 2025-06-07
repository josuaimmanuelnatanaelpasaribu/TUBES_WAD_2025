@extends('layouts.app')

@section('title', 'Catatan Saya')

@push('styles')
<style>
    .note-card {
        transition: transform 0.2s;
    }
    .note-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .note-date {
        font-size: 0.8rem;
        color: #6c757d;
    }
    .note-content {
        font-size: 0.95rem;
        color: #333;
        white-space: pre-wrap;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Catatan Saya</h1>
            <p class="text-muted">Daftar catatan pribadi untuk ayat-ayat Al-Quran</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(count($notes) > 0)
        <div class="row">
            @foreach($notes as $note)
                <div class="col-md-6 mb-3">
                    <div class="card note-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">{{ $note['surah_name'] }}</h5>
                                <span class="badge bg-primary">Ayat {{ $note['ayah_number'] }}</span>
                            </div>
                            <p class="note-content mb-3">{{ $note['note_content'] }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="note-date mb-0">
                                    <i class="bi bi-clock"></i> 
                                    {{ \Carbon\Carbon::parse($note['updated_at'])->diffForHumans() }}
                                </p>
                                <div class="btn-group">
                                    <a href="{{ route('surah.show', ['number' => $note['surah_number'], 'highlight_ayah' => $note['ayah_number']]) }}" 
                                       class="btn btn-primary btn-sm">
                                        <i class="bi bi-book"></i> Baca Ayat
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editNoteModal"
                                            data-note-id="{{ $note['id'] }}"
                                            data-note-content="{{ $note['note_content'] }}"
                                            data-surah-name="{{ $note['surah_name'] }}"
                                            data-ayah-number="{{ $note['ayah_number'] }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Anda belum memiliki catatan. 
            <a href="{{ route('home') }}" class="alert-link">Mulai membaca</a> dan buat catatan untuk ayat yang ingin Anda pelajari.
        </div>
    @endif
</div>

<!-- Modal Edit Catatan -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editNoteForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editNoteModalLabel">Edit Catatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Ayat:</strong> <span id="modal_ayat_info"></span></p>
                    <div class="mb-3">
                        <label for="edit_note_content" class="form-label">Catatan:</label>
                        <textarea class="form-control" id="edit_note_content" name="note" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="deleteNoteBtn">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editNoteModal = document.getElementById('editNoteModal');
    if (editNoteModal) {
        editNoteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const noteId = button.dataset.noteId;
            const noteContent = button.dataset.noteContent;
            const surahName = button.dataset.surahName;
            const ayahNumber = button.dataset.ayahNumber;
            
            const form = this.querySelector('#editNoteForm');
            const textarea = this.querySelector('#edit_note_content');
            const ayatInfo = this.querySelector('#modal_ayat_info');
            
            form.action = `/notes/${noteId}`;
            textarea.value = noteContent;
            ayatInfo.textContent = `${surahName} Ayat ${ayahNumber}`;
            
            // Setup delete button
            const deleteBtn = this.querySelector('#deleteNoteBtn');
            deleteBtn.onclick = function() {
                if (confirm('Anda yakin ingin menghapus catatan ini?')) {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = `/notes/${noteId}`;
                    
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                    
                    deleteForm.appendChild(methodInput);
                    deleteForm.appendChild(csrfInput);
                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                }
            };
        });
    }
});
</script>
@endpush 