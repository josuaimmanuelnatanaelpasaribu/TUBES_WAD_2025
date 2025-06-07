@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Isi Juz ' . $juzNumber)

@push('styles') {{-- Atau sesuaikan cara Anda load CSS --}}
<style>
    .ayat-container {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }
    .ayat-text-arabic {
        font-size: 1.8rem; /* Ukuran font Arab lebih besar */
        direction: rtl;
        text-align: right;
        margin-bottom: 0.5rem;
        font-family: 'Amiri Quran', 'Traditional Arabic', serif; /* Contoh font Arab */
    }
    .ayat-translation {
        font-size: 0.95rem;
        text-align: left;
        color: #555;
    }
    .ayat-info {
        font-size: 0.8rem;
        color: #777;
        margin-top: 0.5rem;
    }
    .surah-header {
        background-color: #f8f9fa;
        padding: 0.75rem 1.25rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 0.25rem;
        font-size: 1.5rem;
        font-weight: 500;
        text-align: center;
    }
     /* Pastikan Anda memiliki font Arab yang baik. Contoh Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Amiri+Quran&display=swap');
</style>
@endpush

@section('content')
<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Isi Juz {{ $juzNumber }}</h1>
        <a href="{{ route('user.juz.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Juz</a>
    </div>
    <p class="lead">Menampilkan ayat-ayat untuk Juz {{ $juzNumber }}. Terjemahan dalam edisi: <strong>{{ $translationEdition }}</strong>.</p>

    @if (empty($processedAyahs))
        <div class="alert alert-warning" role="alert">
            Tidak ada ayat yang dapat ditampilkan untuk Juz ini atau terjadi kesalahan saat mengambil data.
        </div>
    @else
        @php $currentSurahNumber = null; @endphp
        @foreach ($processedAyahs as $ayat)
            @if ($currentSurahNumber !== $ayat['surah_number'])
                @php $currentSurahNumber = $ayat['surah_number']; @endphp
                <div class="surah-header">
                    Surah {{ $ayat['surah_name'] }} ({{ $ayat['surah_number'] }})
                </div>
            @endif

            <div class="ayat-container card shadow-sm mb-3">
                <div class="card-body">
                    <p class="ayat-text-arabic">{{ $ayat['text'] }} <span class="badge bg-secondary rounded-pill">{{ $ayat['numberInSurah'] }}</span></p>
                    <p class="ayat-translation">{{ $ayat['translation_text'] }}</p>
                    <div class="ayat-info">
                        {{ $ayat['surah_name'] }}:{{ $ayat['numberInSurah'] }} (Identifier API: {{ $ayat['api_ayat_identifier'] }})
                        {{-- Di sini Anda bisa menambahkan tombol untuk bookmark atau catatan pribadi per ayat jika diperlukan --}}
                        {{-- Contoh Tombol Bookmark (memerlukan JS & Controller terpisah) --}}
                        {{-- <button class="btn btn-sm btn-outline-danger ms-2 btn-bookmark" data-ayat-id="{{ $ayat['api_ayat_identifier'] }}"><i class="bi bi-heart"></i></button> --}}
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection 