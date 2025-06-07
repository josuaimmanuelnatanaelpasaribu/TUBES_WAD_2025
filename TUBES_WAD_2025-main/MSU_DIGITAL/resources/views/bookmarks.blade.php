@extends('layouts.app')

@section('title', 'Bookmarks')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Your Bookmarks</h5>
                </div>
                <div class="card-body">
                    @if(count($bookmarkedAyahs) > 0)
                        <div class="bookmarks-list">
                            @foreach($bookmarkedAyahs as $item)
                                <div class="bookmark-item mb-4 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">
                                            {{ $item['surah']['name'] }} ({{ $item['surah']['englishName'] }})
                                            - Ayah {{ $item['ayah']['numberInSurah'] }}
                                        </h6>
                                        <div class="btn-group">
                                            <a href="{{ route('surah.show', ['number' => $item['surah']['number']]) }}" 
                                               class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-book"></i> Read Surah
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm remove-bookmark"
                                                    data-surah="{{ $item['surah']['number'] }}"
                                                    data-ayah="{{ $item['ayah']['numberInSurah'] }}">
                                                <i class="bi bi-bookmark-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="arabic-text mb-2" style="font-size: 1.5em; line-height: 2;">
                                        {{ $item['ayah']['text'] }}
                                    </div>
                                    <div class="translation-text text-muted">
                                        {{ $item['ayah']['translation'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-bookmark text-muted" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">No Bookmarks Yet</h4>
                            <p class="text-muted">Start reading the Quran and bookmark your favorite ayahs.</p>
                            <a href="{{ route('home') }}" class="btn btn-success mt-2">
                                Start Reading
                            </a>
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
.arabic-text {
    font-family: "Traditional Arabic", "Scheherazade", serif;
    text-align: right;
    direction: rtl;
}
.bookmark-item {
    transition: all 0.3s ease;
}
.bookmark-item:hover {
    background-color: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-bookmark').forEach(btn => {
        btn.addEventListener('click', function() {
            const surah = this.dataset.surah;
            const ayah = this.dataset.ayah;
            
            if (confirm('Are you sure you want to remove this bookmark?')) {
                fetch('/bookmark/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ surah, ayah })
                })
                .then(response => response.json())
                .then(() => {
                    // Remove the bookmark item from the UI
                    this.closest('.bookmark-item').remove();
                    
                    // If no more bookmarks, show empty state
                    if (document.querySelectorAll('.bookmark-item').length === 0) {
                        location.reload();
                    }
                });
            }
        });
    });
});
</script>
@endpush 