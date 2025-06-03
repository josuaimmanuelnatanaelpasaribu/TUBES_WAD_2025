@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Pilihan Bahasa Terjemahan')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Pilih Bahasa Terjemahan Pilihan Anda</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
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

                    <form method="POST" action="{{ route('user.preferences.language.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="translation_edition_identifier" class="form-label">Bahasa Terjemahan</label>
                            <select class="form-select @error('translation_edition_identifier') is-invalid @enderror" id="translation_edition_identifier" name="translation_edition_identifier" required>
                                <option value="">-- Pilih Terjemahan --</option>
                                @if($activeTranslations->count() > 0)
                                    @foreach ($activeTranslations as $edition)
                                        <option value="{{ $edition->api_edition_identifier }}" 
                                                {{ ($currentUserPreference == $edition->api_edition_identifier || old('translation_edition_identifier') == $edition->api_edition_identifier) ? 'selected' : '' }}>
                                            {{ $edition->name }} ({{ $edition->language_code }})
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>Tidak ada edisi terjemahan yang aktif saat ini.</option>
                                @endif
                            </select>
                            @error('translation_edition_identifier')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Preferensi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Pastikan layout Anda memuat Bootstrap CSS & JS jika menggunakan kelas di atas --}}
{{-- Contoh: <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> --}} 