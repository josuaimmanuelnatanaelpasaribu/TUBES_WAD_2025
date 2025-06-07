@extends('layouts.app') {{-- Sesuaikan dengan layout utama admin Anda --}}

@section('title', 'Edit Deskripsi Juz ' . $juzNumber)

@section('content')
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1>Edit Deskripsi Kustom untuk Juz {{ $juzNumber }}</h1>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">Formulir Deskripsi Juz</div>
                <div class="card-body">
                    <form action="{{ route('admin.juz_management.update', $juzNumber) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="juz_number_display" class="form-label">Nomor Juz</label>
                            <input type="text" id="juz_number_display" class="form-control" value="{{ $juzNumber }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="custom_description" class="form-label">Deskripsi Kustom</label>
                            <textarea class="form-control @error('custom_description') is-invalid @enderror" 
                                      id="custom_description" 
                                      name="custom_description" 
                                      rows="10">{{ old('custom_description', $juzData->custom_description) }}</textarea>
                            @error('custom_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Masukkan deskripsi atau penjelasan kustom untuk Juz ini (opsional).</small>
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('admin.juz_management.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Deskripsi</button>
                        </div>
                    </form>
                </div>
            </div>
            
            {{-- Jika Anda mengambil $juzApiDetails di controller, Anda bisa menampilkannya di sini --}}
            {{-- Contoh:
            @if(isset($juzApiDetails) && $juzApiDetails['status'] == 'success')
                <div class="card mt-4">
                    <div class="card-header">Informasi Ayat dari API untuk Juz {{ $juzNumber }}</div>
                    <div class="card-body">
                        <p>Total ayat dalam Juz ini (menurut API): {{ count($juzApiDetails['data']['ayahs'] ?? []) }}</p>
                        <p>Ayat pertama: Surah {{ $juzApiDetails['data']['ayahs'][0]['surah']['number'] ?? 'N/A' }} Ayat {{ $juzApiDetails['data']['ayahs'][0]['numberInSurah'] ?? 'N/A' }}</p>
                        @php 
                            $lastAyah = end($juzApiDetails['data']['ayahs']);
                        @endphp
                        <p>Ayat terakhir: Surah {{ $lastAyah['surah']['number'] ?? 'N/A' }} Ayat {{ $lastAyah['numberInSurah'] ?? 'N/A' }} </p>
                        
                    </div>
                </div>
            @elseif(isset($juzApiDetails))
                <div class="alert alert-warning mt-4">Gagal memuat detail ayat dari API untuk Juz ini.</div>
            @endif
            --}}
        </div>
    </div>
</div>
@endsection 