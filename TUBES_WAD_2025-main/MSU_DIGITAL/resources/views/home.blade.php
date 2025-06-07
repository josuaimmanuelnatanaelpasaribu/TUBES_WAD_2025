@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="container">
    <div class="row">
        <!-- Surah List Sidebar -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Surat List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush surah-list">
                        @foreach($surahs as $surah)
                            <a href="{{ route('surah.show', $surah['number']) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="surah-number me-2">{{ $surah['number'] }}.</span>
                                    <span class="surah-name">{{ $surah['name'] }}</span>
                                </div>
                                <small class="text-muted">{{ $surah['englishName'] }}</small>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        @if(isset($currentSurah))
                            {{ $currentSurah['name'] }} ({{ $currentSurah['englishName'] }})
                        @else
                            Welcome to Digital Quran
                        @endif
                    </h5>
                    @if(isset($currentSurah))
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" id="toggleArabic">
                                Toggle Arabic
                            </button>
                            <button type="button" class="btn btn-light btn-sm" id="toggleTranslation">
                                Toggle Translation
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if(isset($currentSurah))
                        <div class="quran-content">
                            @foreach($ayahs as $ayah)
                                <div class="ayah-container mb-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="ayah-number badge bg-success">{{ $ayah['numberInSurah'] }}</span>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-success btn-sm bookmark-btn"
                                                    data-surah="{{ $currentSurah['number'] }}"
                                                    data-ayah="{{ $ayah['numberInSurah'] }}">
                                                <i class="bi bi-bookmark{{ in_array($ayah['numberInSurah'], $bookmarks) ? '-fill' : '' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm note-btn"
                                                    data-surah="{{ $currentSurah['number'] }}"
                                                    data-ayah="{{ $ayah['numberInSurah'] }}">
                                                <i class="bi bi-journal-text"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="arabic-text mb-2" style="font-size: 1.8em; line-height: 2;">
                                        {{ $ayah['text'] }}
                                    </div>
                                    <div class="translation-text">
                                        {{ $ayah['translation'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <h3>Welcome to Digital Quran</h3>
                            <p class="lead">Select a Surah from the list to start reading</p>
                            @if($lastRead)
                                <a href="{{ route('surah.show', ['number' => $lastRead['surah']]) }}" 
                                   class="btn btn-success mt-3">
                                    Continue Reading from Surah {{ $lastRead['surah'] }}, Ayah {{ $lastRead['ayah'] }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.surah-list {
    max-height: 80vh;
    overflow-y: auto;
}
.surah-number {
    display: inline-block;
    min-width: 30px;
}
.arabic-text {
    font-family: "Traditional Arabic", "Scheherazade", serif;
    text-align: right;
    direction: rtl;
}
.translation-text {
    color: #666;
}
.ayah-container {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}
.ayah-container:last-child {
    border-bottom: none;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Arabic/Translation visibility
    const toggleArabic = document.getElementById('toggleArabic');
    const toggleTranslation = document.getElementById('toggleTranslation');
    
    if (toggleArabic) {
        toggleArabic.addEventListener('click', function() {
            document.querySelectorAll('.arabic-text').forEach(el => {
                el.classList.toggle('d-none');
            });
        });
    }
    
    if (toggleTranslation) {
        toggleTranslation.addEventListener('click', function() {
            document.querySelectorAll('.translation-text').forEach(el => {
                el.classList.toggle('d-none');
            });
        });
    }

    // Bookmark functionality
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const surah = this.dataset.surah;
            const ayah = this.dataset.ayah;
            
            fetch('/bookmark/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ surah, ayah })
            })
            .then(response => response.json())
            .then(data => {
                const icon = this.querySelector('i');
                if (data.bookmarked) {
                    icon.classList.remove('bi-bookmark');
                    icon.classList.add('bi-bookmark-fill');
                } else {
                    icon.classList.remove('bi-bookmark-fill');
                    icon.classList.add('bi-bookmark');
                }
            });
        });
    });

    // Note functionality
    document.querySelectorAll('.note-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const surah = this.dataset.surah;
            const ayah = this.dataset.ayah;
            window.location.href = `/notes?surah=${surah}&ayah=${ayah}`;
        });
    });
});
</script>
@endpush 