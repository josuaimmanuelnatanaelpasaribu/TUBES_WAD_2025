@extends('layouts.admin_app')

@section('title', 'Manajemen Edisi Audio')

@push('styles')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .sync-button {
        transition: all 0.3s ease;
    }
    .sync-button.rotating i {
        animation: rotate 1s linear infinite;
    }
    @keyframes rotate {
        100% { transform: rotate(360deg); }
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
    }
    .status-badge.active {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    .status-badge.inactive {
        background-color: #f8d7da;
        color: #842029;
    }
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: #28a745;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(24px);
    }
    #loading-indicator {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        background: rgba(255, 255, 255, 0.9);
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .style-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        background-color: #e9ecef;
        color: #495057;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Loading Indicator -->
    <div id="loading-indicator" class="text-center">
        <div class="spinner-border text-primary mb-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-0">Sedang memproses...</p>
    </div>

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Edisi Audio</h1>
        <button class="btn btn-primary sync-button" onclick="syncAudioEditions()">
            <i class="fas fa-sync-alt me-1"></i> Sinkronkan dari API
        </button>
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

    <!-- Audio Editions Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="audioEditionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID API</th>
                            <th>Nama Qari</th>
                            <th>Bahasa</th>
                            <th>Style</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($editions as $edition)
                            <tr>
                                <td>{{ $edition['identifier'] }}</td>
                                <td>
                                    <strong>{{ $edition['qari_name'] }}</strong>
                                </td>
                                <td>{{ $edition['language_name'] }}</td>
                                <td>
                                    @if($edition['style'])
                                        <span class="style-badge">{{ $edition['style'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($edition['is_available'])
                                        <span class="status-badge {{ $edition['is_active_for_users'] ? 'active' : 'inactive' }}">
                                            {{ $edition['is_active_for_users'] ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @else
                                        <span class="status-badge inactive">Belum Tersedia</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($edition['is_available'])
                                        <label class="toggle-switch mb-0" title="Toggle Status">
                                            <input type="checkbox" 
                                                   {{ $edition['is_active_for_users'] ? 'checked' : '' }}
                                                   onchange="toggleAvailability('{{ $edition['local_id'] }}', this)">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <button class="btn btn-danger btn-sm ms-2" 
                                                onclick="removeEdition('{{ $edition['local_id'] }}', this)"
                                                title="Hapus Edisi">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="addEdition('{{ $edition['identifier'] }}', this)"
                                                data-edition='@json($edition)'
                                                title="Tambahkan Edisi">
                                            <i class="fas fa-plus"></i> Tambah
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#audioEditionsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[1, 'asc']], // Sort by Qari name by default
        columnDefs: [
            { orderable: false, targets: [5] } // Disable sorting for action column
        ]
    });
});

function showLoading() {
    document.getElementById('loading-indicator').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loading-indicator').style.display = 'none';
}

function syncAudioEditions() {
    if (!confirm('Apakah Anda yakin ingin menyinkronkan data edisi audio dari API?')) {
        return;
    }

    const syncButton = document.querySelector('.sync-button');
    syncButton.classList.add('rotating');
    syncButton.disabled = true;
    showLoading();

    fetch('{{ route("admin.audio_editions.sync") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal menyinkronkan data.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyinkronkan data.');
    })
    .finally(() => {
        syncButton.classList.remove('rotating');
        syncButton.disabled = false;
        hideLoading();
    });
}

function addEdition(identifier, button) {
    showLoading();
    const editionData = JSON.parse(button.dataset.edition);
    
    fetch('{{ route("admin.audio_editions.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(editionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menambahkan edisi audio.');
    })
    .finally(() => {
        hideLoading();
    });
}

function removeEdition(editionId, button) {
    if (!confirm('Apakah Anda yakin ingin menghapus edisi audio ini?')) {
        return;
    }

    showLoading();
    
    fetch(`{{ url('admin/audio-editions') }}/${editionId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus edisi audio.');
    })
    .finally(() => {
        hideLoading();
    });
}

function toggleAvailability(editionId, checkbox) {
    showLoading();
    
    fetch(`{{ url('admin/audio-editions') }}/${editionId}/toggle-availability`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            checkbox.checked = !checkbox.checked; // Revert the change
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked; // Revert the change
        alert('Terjadi kesalahan saat mengubah status edisi audio.');
    })
    .finally(() => {
        hideLoading();
    });
}
</script>
@endpush 