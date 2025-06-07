@extends('layouts.admin_app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Manajemen Data Kustom Surat')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Kustom Surat</h1>

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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Surat</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Surat</th>
                            <th>Nama Surat (Arab)</th>
                            <th>Nama Surat (Inggris)</th>
                            <th>Jenis</th>
                            <th>Jml. Ayat</th>
                            <th>Deskripsi Kustom</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suratList as $surat)
                            <tr>
                                <td>{{ $surat['number'] }}</td>
                                <td>{{ $surat['name'] }}</td>
                                <td>{{ $surat['englishName'] }}</td>
                                <td>{{ $surat['revelationType'] }}</td>
                                <td>{{ $surat['numberOfAyahs'] }}</td>
                                <td>
                                    @if($surat['custom_description'])
                                        {{ Str::limit($surat['custom_description'], 100) }} {{-- Tampilkan sebagian --}}
                                    @else
                                        <span class="text-muted"><em>Belum ada</em></span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.surats.edit', $surat['number']) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit Deskripsi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data surat atau gagal memuat dari API.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Jika menggunakan DataTables --}}
    {{-- <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script> --}}
    {{-- <script>$(document).ready(function() { $('#dataTable').DataTable(); });</script> --}}
@endpush

@push('styles')
    {{-- <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet"> --}}
@endpush 