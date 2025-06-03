<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ayat Surah {{ $surahData['name'] ?? \'\' }} - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; }
        .ayat-text { font-size: 1.5rem; direction: rtl; text-align: right; }
        .action-buttons form { display: inline-block; margin-left: 5px; }
        .list-group-item span { display: block; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1>Kelola Ayat Surah: {{ $surahData['name'] }} ({{ $surahData['englishName'] }})</h1>
        <p>Nomor Surah: {{ $surahData['number'] }}</p>
        <p>Arti: {{ $surahData['englishNameTranslation'] }}</p>
        <p>Jenis: {{ $surahData['revelationType'] }}</p>
        <p>Jumlah Ayat: {{ $surahData['numberOfAyahs'] }}</p>
        <p>Edisi Teks: {{ $editionIdentifier }}</p>

        <hr>

        @if (session(\'success\'))
            <div class="alert alert-success">{{ session(\'success\') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2>Ayat-ayat</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>No. Ayat</th>
                        <th>Teks Arab</th>
                        <th style="min-width: 300px;">Catatan Admin</th>
                        <th style="min-width: 300px;">Kata Kunci Global</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($processedAyats as $ayat)
                        <tr>
                            <td>{{ $ayat['numberInSurah'] }}</td>
                            <td class="ayat-text">{{ $ayat['text'] }}</td>
                            <td>
                                {{-- Catatan Admin --}}
                                @if ($ayat['admin_notes']->isNotEmpty())
                                    <ul class="list-group mb-2">
                                        @foreach ($ayat['admin_notes'] as $note)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>{{ Str::limit($note->content, 100) }}</span>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-note"
                                                            data-bs-toggle="modal" data-bs-target="#adminNoteModal"
                                                            data-note-id="{{ $note->id }}"
                                                            data-note-content="{{ $note->content }}"
                                                            data-api-ayat-identifier="{{ $ayat['api_ayat_identifier'] }}"
                                                            data-action-url="{{ route('admin.admin_notes.update', $note) }}">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('admin.admin_notes.destroy', $note) }}" method="POST" onsubmit="return confirm(\'Yakin ingin menghapus catatan ini?\');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p><small>Belum ada catatan.</small></p>
                                @endif
                                <button type="button" class="btn btn-sm btn-success btn-add-note"
                                        data-bs-toggle="modal" data-bs-target="#adminNoteModal"
                                        data-api-ayat-identifier="{{ $ayat['api_ayat_identifier'] }}"
                                        data-action-url="{{ route('admin.admin_notes.store', $ayat['api_ayat_identifier']) }}">
                                    + Tambah Catatan
                                </button>
                            </td>
                            <td>
                                {{-- Kata Kunci Global --}}
                                @if ($ayat['global_keywords']->isNotEmpty())
                                    <ul class="list-group mb-2">
                                        @foreach ($ayat['global_keywords'] as $keyword)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>{{ $keyword->keyword }}</span>
                                                <form action="{{ route('admin.global_keywords.destroy', $keyword) }}" method="POST" onsubmit="return confirm(\'Yakin ingin menghapus kata kunci ini?\');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p><small>Belum ada kata kunci.</small></p>
                                @endif
                                <button type="button" class="btn btn-sm btn-success btn-manage-keywords"
                                        data-bs-toggle="modal" data-bs-target="#globalKeywordModal"
                                        data-api-entity-identifier="{{ $ayat['api_ayat_identifier'] }}"
                                        data-entity-type="ayat"
                                        data-existing-keywords="{{ json_encode($ayat['global_keywords']->map(function($kw) { return ['id' => $kw->id, 'keyword' => $kw->keyword]; })) }}">
                                    Kelola Kata Kunci
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada ayat ditemukan untuk surah ini dengan edisi yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal AdminNote -->
    <div class="modal fade" id="adminNoteModal" tabindex="-1" aria-labelledby="adminNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="adminNoteForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" id="adminNoteMethod">
                    <input type="hidden" name="api_ayat_identifier_modal" id="adminNoteApiAyatIdentifierModal"> {{-- Jika action store butuh ini di body, bukan di URL --}}
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminNoteModalLabel">Tambah/Edit Catatan Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="adminNoteContent" class="form-label">Isi Catatan</label>
                            <textarea class="form-control" id="adminNoteContent" name="content" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal GlobalKeyword -->
    <div class="modal fade" id="globalKeywordModal" tabindex="-1" aria-labelledby="globalKeywordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="globalKeywordModalLabel">Kelola Kata Kunci Global</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Kata Kunci yang Sudah Ada:</h6>
                    <ul class="list-group mb-3" id="existingKeywordsList">
                        {{-- Akan diisi oleh JavaScript --}}
                    </ul>
                    <hr>
                    <h6>Tambah Kata Kunci Baru:</h6>
                    <form id="addGlobalKeywordForm" method="POST" action="">
                        @csrf
                        <input type="hidden" name="api_entity_identifier_kw_modal" id="keywordApiEntityIdentifierModal">
                        <input type="hidden" name="entity_type_kw_modal" id="keywordEntityTypeModal">
                        <div class="mb-3">
                            <label for="newKeywordText" class="form-label">Kata Kunci</label>
                            <input type="text" class="form-control" id="newKeywordText" name="keyword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Tambah Kata Kunci</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener(\'DOMContentLoaded\', function () {
            // AdminNote Modal
            const adminNoteModal = document.getElementById(\'adminNoteModal\');
            const adminNoteForm = document.getElementById(\'adminNoteForm\');
            const adminNoteContent = document.getElementById(\'adminNoteContent\');
            const adminNoteMethod = document.getElementById(\'adminNoteMethod\');
            const adminNoteApiAyatIdentifierModal = document.getElementById(\'adminNoteApiAyatIdentifierModal\');
            const adminNoteModalLabel = document.getElementById(\'adminNoteModalLabel\');

            document.querySelectorAll(\'.btn-add-note\').forEach(button => {
                button.addEventListener(\'click\', function () {
                    const apiAyatIdentifier = this.dataset.apiAyatIdentifier;
                    const actionUrl = this.dataset.actionUrl;

                    adminNoteForm.action = actionUrl;
                    adminNoteMethod.value = \'POST\';
                    adminNoteContent.value = \'\';
                    adminNoteApiAyatIdentifierModal.value = apiAyatIdentifier; // Jika dibutuhkan di form body
                    adminNoteModalLabel.textContent = \'Tambah Catatan Admin untuk Ayat \' + apiAyatIdentifier;
                });
            });

            document.querySelectorAll(\'.btn-edit-note\').forEach(button => {
                button.addEventListener(\'click\', function () {
                    const noteId = this.dataset.noteId;
                    const content = this.dataset.noteContent;
                    const apiAyatIdentifier = this.dataset.apiAyatIdentifier;
                    const actionUrl = this.dataset.actionUrl;

                    adminNoteForm.action = actionUrl;
                    adminNoteMethod.value = \'PUT\';
                    adminNoteContent.value = content;
                    adminNoteApiAyatIdentifierModal.value = apiAyatIdentifier; // Jika dibutuhkan (biasanya tidak untuk update)
                    adminNoteModalLabel.textContent = \'Edit Catatan Admin untuk Ayat \' + apiAyatIdentifier;
                });
            });


            // GlobalKeyword Modal
            const globalKeywordModal = document.getElementById(\'globalKeywordModal\');
            const addGlobalKeywordForm = document.getElementById(\'addGlobalKeywordForm\');
            const keywordApiEntityIdentifierModal = document.getElementById(\'keywordApiEntityIdentifierModal\');
            const keywordEntityTypeModal = document.getElementById(\'keywordEntityTypeModal\');
            const existingKeywordsList = document.getElementById(\'existingKeywordsList\');
            const newKeywordText = document.getElementById(\'newKeywordText\');
            const globalKeywordModalLabel = document.getElementById(\'globalKeywordModalLabel\');
            
            let currentKeywordApiEntityIdentifier = \'\';
            let currentKeywordEntityType = \'\';

            document.querySelectorAll(\'.btn-manage-keywords\').forEach(button => {
                button.addEventListener(\'click\', function () {
                    currentKeywordApiEntityIdentifier = this.dataset.apiEntityIdentifier;
                    currentKeywordEntityType = this.dataset.entityType;
                    const existingKeywords = JSON.parse(this.dataset.existingKeywords || \'[]\');
                    
                    globalKeywordModalLabel.textContent = \'Kelola Kata Kunci untuk \' + currentKeywordEntityType + \' \' + currentKeywordApiEntityIdentifier;
                    
                    // Set form action and hidden fields for adding new keyword
                    // The action URL needs the entity identifier and type.
                    // Example: /admin/global-keywords/{apiEntityIdentifier}/{entityType}
                    const addActionUrl = \`{{ url('admin/global-keywords') }}/\${currentKeywordApiEntityIdentifier}/\${currentKeywordEntityType}\`.replace(/ayat:(\\d+):(\\d+)/g, \'ayat/$1:$2\'); // Handle potential colon issue if not using named route correctly with such params
                    // Safer to use named route if possible, but JS construction is an alternative if route structure is fixed.
                    // Let\'s assume a fixed structure for JS action for simplicity here. If your route('admin.global_keywords.store', [params]) works in JS, use that.
                    // For this example, I\'ll use a simplified way to set action. Consider how your routes are structured.
                    // The route for store is POST admin/global-keywords/{apiEntityIdentifier}/{entityType}
                    addGlobalKeywordForm.action = \`{{ route('admin.global_keywords.store', ['apiEntityIdentifier_placeholder', 'entityType_placeholder']) }}\`
                                                .replace(\'apiEntityIdentifier_placeholder\', currentKeywordApiEntityIdentifier)
                                                .replace(\'entityType_placeholder\', currentKeywordEntityType);

                    keywordApiEntityIdentifierModal.value = currentKeywordApiEntityIdentifier;
                    keywordEntityTypeModal.value = currentKeywordEntityType;
                    newKeywordText.value = \'\';

                    // Populate existing keywords
                    existingKeywordsList.innerHTML = \'\'; // Clear previous
                    if (existingKeywords.length > 0) {
                        existingKeywords.forEach(kw => {
                            const li = document.createElement(\'li\');
                            li.className = \'list-group-item d-flex justify-content-between align-items-center\';
                            li.innerHTML = `
                                <span>\${kw.keyword}</span>
                                <form action="{{ url('admin/global-keywords') }}/\${kw.id}" method="POST" class="delete-keyword-form" onsubmit="return confirm(\'Yakin ingin menghapus kata kunci \'\${kw.keyword}\'?\');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="keyword_id_for_js_delete" value="\${kw.id}"> {{-- just for clarity if needed --}}
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            `;
                            // The form action for delete uses Route Model Binding, so kw.id is correct.
                            // We need to make sure the route('admin.global_keywords.destroy', kw.id) is generated correctly
                            // For JS, it's easier to construct URL string or use a template.
                            const deleteForm = li.querySelector(\'.delete-keyword-form\');
                            deleteForm.action = \`{{ route('admin.global_keywords.destroy', ['keyword' => 'KEYWORD_ID_PLACEHOLDER']) }}\`.replace(\'KEYWORD_ID_PLACEHOLDER\', kw.id);

                            existingKeywordsList.appendChild(li);
                        });
                    } else {
                        existingKeywordsList.innerHTML = \'<li class="list-group-item">Belum ada kata kunci.</li>\';
                    }
                });
            });
        });
    </script>
</body>
</html> 