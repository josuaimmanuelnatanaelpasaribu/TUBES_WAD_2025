@extends('layouts.app')

@section('title', 'Bookmark Saya')

@push('styles')
<style>
    .bookmark-card {
        transition: transform 0.2s;
    }
    .bookmark-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .bookmark-date {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Bookmark Saya</h1>
            <p class="text-muted">Daftar ayat yang telah Anda tandai</p>
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

    @if(count($bookmarks) > 0)
        <div class="row">
            @foreach($bookmarks as $bookmark)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card bookmark-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $bookmark['surah_name'] }}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                Ayat {{ $bookmark['ayah_number'] }}
                            </h6>
                            <p class="bookmark-date">
                                <i class="bi bi-clock"></i> 
                                {{ \Carbon\Carbon::parse($bookmark['created_at'])->diffForHumans() }}
                            </p>
                            <a href="{{ route('surah.show', ['number' => $bookmark['surah_number'], 'highlight_ayah' => $bookmark['ayah_number']]) }}" 
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-book"></i> Baca
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Anda belum memiliki bookmark. 
            <a href="{{ route('home') }}" class="alert-link">Mulai membaca</a> dan tandai ayat favorit Anda.
        </div>
    @endif
</div>
@endsection 