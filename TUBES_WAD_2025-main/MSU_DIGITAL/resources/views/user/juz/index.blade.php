@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Progres Baca Juz')

@section('content')
<div class="container mt-5 mb-5">
    <h1>Progres Baca Juz Al-Qur'an</h1>
    <p class="lead">Lacak progres bacaan Juz Anda di sini.</p>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @foreach ($allJuzInfo as $juz)
            <div class="col">
                <div class="card h-100 {{ $juz['is_completed'] ? 'border-success' : '' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Juz {{ $juz['juz_number'] }}</h5>
                        @if ($juz['is_completed'])
                            <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Selesai</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($juz['custom_description'])
                            <p class="card-text"><small class="text-muted">{{ Str::limit($juz['custom_description'], 100) }}</small></p>
                        @endif
                        
                        <div class="mb-2">
                            <div class="progress" role="progressbar" aria-label="Progres Juz {{ $juz['juz_number'] }}" aria-valuenow="{{ $juz['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100" style="height: 20px;">
                                <div class="progress-bar {{ $juz['is_completed'] ? 'bg-success' : 'bg-primary' }} progress-bar-striped progress-bar-animated" 
                                     style="width: {{ $juz['progress_percentage'] }}%">
                                     {{ $juz['progress_percentage'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('user.juz.show_content', $juz['juz_number']) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-book"></i> Lihat Isi Juz
                            </a>
                            <div>
                                @if (!$juz['is_completed'])
                                    <button type="button" class="btn btn-sm btn-outline-info btn-update-progress me-1" 
                                            data-bs-toggle="modal" data-bs-target="#updateProgressModal"
                                            data-juz-number="{{ $juz['juz_number'] }}"
                                            data-current-progress="{{ $juz['progress_percentage'] }}">
                                        <i class="bi bi-pencil-square"></i> Update Progres
                                    </button>
                                    <form action="{{ route('user.juz.mark_completed', $juz['juz_number']) }}" method="POST" class="d-inline form-mark-completed">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-lg"></i> Tandai Selesai
                                        </button>
                                    </form>
                                @else
                                     <form action="{{ route('user.juz.update_progress', $juz['juz_number']) }}" method="POST" class="d-inline form-reset-progress">
                                        @csrf
                                        <input type="hidden" name="progress_percentage" value="0">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset Progres
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal Update Progress -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-labelledby="updateProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateProgressForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProgressModalLabel">Update Progres Juz X</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="juzNumberInput" name="juz_number_modal_field">
                    <div class="mb-3">
                        <label for="progressPercentageRange" class="form-label">Progres (<span id="progressValueLabel">0</span>%)</label>
                        <input type="range" class="form-range" min="0" max="100" step="1" id="progressPercentageRange" name="progress_percentage">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts') {{-- Atau sesuaikan dengan cara Anda load JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const updateProgressModal = document.getElementById('updateProgressModal');
    if (updateProgressModal) {
        const form = updateProgressModal.querySelector('#updateProgressForm');
        const juzNumberInput = updateProgressModal.querySelector('#juzNumberInput'); // Redundant, juz num is in action
        const rangeInput = updateProgressModal.querySelector('#progressPercentageRange');
        const valueLabel = updateProgressModal.querySelector('#progressValueLabel');
        const modalLabel = updateProgressModal.querySelector('#updateProgressModalLabel');

        updateProgressModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const juzNumber = button.dataset.juzNumber;
            const currentProgress = button.dataset.currentProgress;
            
            modalLabel.textContent = `Update Progres Juz ${juzNumber}`;
            form.action = `{{ url('user/juz') }}/${juzNumber}/update-progress`; // Construct URL
            // juzNumberInput.value = juzNumber; // Tidak perlu jika action sudah benar
            rangeInput.value = currentProgress;
            valueLabel.textContent = currentProgress;
        });

        rangeInput.addEventListener('input', function () {
            valueLabel.textContent = this.value;
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Hentikan submit form standar
            const formData = new FormData(form);
            const actionUrl = form.action;

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), // Pastikan Anda punya CSRF token di meta
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    // Tutup modal
                    const modal = bootstrap.Modal.getInstance(updateProgressModal);
                    modal.hide();
                    // Reload halaman untuk lihat perubahan (atau update UI secara dinamis)
                    window.location.reload(); 
                } else if (data.errors) {
                    alert('Error: ' + Object.values(data.errors).join('\n'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate progres.');
            });
        });
    }

    // Handle form mark-completed & reset-progress (jika ingin AJAX juga)
    document.querySelectorAll('.form-mark-completed, .form-reset-progress').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!confirm(form.classList.contains('form-mark-completed') ? 'Yakin ingin menandai Juz ini selesai?' : 'Yakin ingin mereset progres Juz ini?')) {
                return;
            }
            const formData = new FormData(form);
            const actionUrl = form.action;

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    window.location.reload();
                } else {
                    alert('Gagal memproses permintaan.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        });
    });
});

// Pastikan Anda punya <meta name="csrf-token" content="{{ csrf_token() }}"> di layout utama Anda
</script>
@endpush 