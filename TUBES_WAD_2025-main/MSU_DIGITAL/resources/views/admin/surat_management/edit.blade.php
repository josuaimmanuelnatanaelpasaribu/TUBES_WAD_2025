@extends('layouts.admin_app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Edit Deskripsi Kustom Surat')

@push('styles')
<style>
    .surah-info {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .arabic-name {
        font-size: 2rem;
        direction: rtl;
        text-align: right;
        margin-bottom: 10px;
    }
    .surah-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .badge-revelation {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
    .badge-meccan {
        background-color: #28a745;
        color: white;
    }
    .badge-medinan {
        background-color: #007bff;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Deskripsi Kustom Surat</h1>
        <a href="{{ route('admin.surats.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- Surah Info Card -->
            <div class="card shadow mb-4">
                <div class="card-body surah-info">
                    <div class="arabic-name">{{ $surahDetail['name'] }}</div>
                    <h2 class="h4">{{ $surahDetail['englishName'] }}</h2>
                    <div class="surah-meta">
                        <span class="badge badge-revelation {{ $surahDetail['revelationType'] === 'Meccan' ? 'badge-meccan' : 'badge-medinan' }}">
                            {{ $surahDetail['revelationType'] }}
                        </span>
                        <span class="ms-3">
                            <i class="fas fa-book-open"></i> {{ $surahDetail['numberOfAyahs'] }} Ayat
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Form Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Deskripsi</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.surats.update', $surahDetail['number']) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="custom_description">Deskripsi Kustom</label>
                            <textarea class="form-control @error('custom_description') is-invalid @enderror" 
                                      id="custom_description" 
                                      name="custom_description" 
                                      rows="6" 
                                      placeholder="Masukkan deskripsi kustom untuk surat ini...">{{ old('custom_description', $customData->custom_description) }}</textarea>
                            <small class="form-text text-muted">
                                Deskripsi ini akan ditampilkan kepada pengguna saat melihat detail surat.
                                Anda dapat menambahkan informasi tambahan, konteks historis, atau penjelasan khusus tentang surat ini.
                            </small>
                            @error('custom_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('admin.surats.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-expand textarea as user types
    const textarea = document.getElementById('custom_description');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>
@endpush 