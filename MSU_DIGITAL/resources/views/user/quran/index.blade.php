@extends('layouts.app')

@section('title', 'Daftar Surat Al-Quran')

@push('styles')
<style>
    .surah-card {
        transition: transform 0.2s;
        border: 1px solid #e3e3e3;
        border-radius: 8px;
    }
    .surah-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .surah-number {
        background-color: #28a745;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
    .surah-name-arabic {
        font-size: 1.5rem;
        color: #333;
        text-align: right;
        direction: rtl;
    }
    .surah-name-translation {
        font-size: 0.9rem;
        color: #666;
    }
    .surah-meta {
        font-size: 0.8rem;
        color: #666;
    }
    .last-read-banner {
        background-color: #e8f5e9;
        border-left: 4px solid #28a745;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Al-Quran Digital</h1>
            <p class="text-muted">Baca, tandai, dan buat catatan untuk ayat-ayat Al-Quran</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($lastRead)
        <div class="card mb-4 last-read-banner">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-bookmark-check-fill text-success"></i> 
                    Terakhir Dibaca
                </h5>
                <p class="card-text">
                    {{ $lastRead['surah_name'] }} (Surah {{ $lastRead['surah_number'] }})
                    Ayat {{ $lastRead['ayah_number'] }}
                </p>
                <a href="{{ route('surah.show', ['number' => $lastRead['surah_number'], 'highlight_ayah' => $lastRead['ayah_number']]) }}" 
                   class="btn btn-success btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> 
                    Lanjutkan Membaca
                </a>
            </div>
        </div>
    @endif

    <div class="row">
        @foreach($surahs as $surah)
            <div class="col-md-6 col-lg-4 mb-3">
                <a href="{{ route('surah.show', $surah['number']) }}" class="text-decoration-none">
                    <div class="card surah-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="surah-number">{{ $surah['number'] }}</span>
                                <div class="text-end">
                                    <div class="surah-name-arabic">{{ $surah['name'] }}</div>
                                    <div class="surah-name-translation">{{ $surah['englishName'] }}</div>
                                </div>
                            </div>
                            <div class="surah-meta d-flex justify-content-between">
                                <span>{{ $surah['revelationType'] }}</span>
                                <span>{{ $surah['numberOfAyahs'] }} Ayat</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any JavaScript functionality here if needed
});
</script>
@endpush 