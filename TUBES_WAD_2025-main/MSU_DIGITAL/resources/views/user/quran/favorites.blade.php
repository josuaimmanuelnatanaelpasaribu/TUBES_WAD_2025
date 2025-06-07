@extends('layouts.app')

@section('title', 'Surat Favorit')

@push('styles')
<style>
    .surah-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #e3e3e3;
        border-radius: 8px;
        position: relative;
    }
    .surah-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .surah-number {
        background-color: #ffc107;
        color: #000;
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
    .badge-revelation {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
    }
    .badge-meccan {
        background-color: #198754;
        color: white;
    }
    .badge-medinan {
        background-color: #0d6efd;
        color: white;
    }
    .favorite-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        background: none;
        border: none;
        padding: 5px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .favorite-btn:hover {
        transform: scale(1.1);
    }
    .favorite-btn i {
        color: #ffc107;
        font-size: 1.2rem;
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #ffc107;
        margin-bottom: 1rem;
    }
    #loading-indicator {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
    }
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <!-- Loading Indicator -->
    <div id="loading-indicator" class="text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Surat Favorit</h1>
            <p class="text-muted mb-0">Daftar surat yang Anda tandai sebagai favorit</p>
        </div>
        <a href="{{ route('quran.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Surat
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(count($surahs) > 0)
        <div class="row">
            @foreach($surahs as $surah)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card surah-card h-100">
                        <!-- Favorite Button -->
                        <button class="favorite-btn" onclick="toggleFavorite({{ $surah['number'] }}, this)" type="button">
                            <i class="bi bi-star-fill"></i>
                        </button>

                        <a href="{{ route('surah.show', $surah['number']) }}" class="text-decoration-none">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="surah-number">{{ $surah['number'] }}</span>
                                    <div class="text-end">
                                        <div class="surah-name-arabic">{{ $surah['name'] }}</div>
                                        <div class="surah-name-translation">{{ $surah['englishName'] }}</div>
                                    </div>
                                </div>
                                <div class="surah-meta d-flex justify-content-between align-items-center">
                                    <span class="badge badge-revelation {{ $surah['revelationType'] === 'Meccan' ? 'badge-meccan' : 'badge-medinan' }}">
                                        {{ $surah['revelationType'] === 'Meccan' ? 'Makkiyah' : 'Madaniyah' }}
                                    </span>
                                    <span>
                                        <i class="bi bi-book"></i> {{ $surah['numberOfAyahs'] }} Ayat
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-star"></i>
                <h3>Belum Ada Surat Favorit</h3>
                <p class="text-muted">Anda belum menandai surat apapun sebagai favorit.</p>
                <a href="{{ route('quran.index') }}" class="btn btn-primary">
                    <i class="bi bi-book"></i> Jelajahi Daftar Surat
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function toggleFavorite(suratNumber, button) {
    const card = button.closest('.col-md-6');
    const loadingIndicator = document.getElementById('loading-indicator');
    const toast = new bootstrap.Toast(document.getElementById('toast'));
    
    showLoading();
    
    fetch(`/quran/favorites/${suratNumber}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove card with animation
            card.style.transition = 'opacity 0.3s ease-out';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                
                // Check if there are no more cards
                const remainingCards = document.querySelectorAll('.surah-card');
                if (remainingCards.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);

            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan saat memproses permintaan.', 'danger');
    })
    .finally(() => {
        hideLoading();
    });
}

function showToast(message, type = 'success') {
    const toastElement = document.getElementById('toast');
    const toastBody = toastElement.querySelector('.toast-body');
    
    toastElement.className = `toast align-items-center text-bg-${type}`;
    toastBody.textContent = message;
    
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

function showLoading() {
    document.getElementById('loading-indicator').style.display = 'block';
}

function hideLoading() {
    document.getElementById('loading-indicator').style.display = 'none';
}
</script>
@endpush 