@props([
    'notes',
    'noteImages',
])

<section class="dme-page-stack" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
    <article class="dme-section-card dme-section-stack">
        <div class="dme-section-header__content">
            <h2 class="dme-section-title">Catatan Terbaru</h2>
            <p class="dme-section-copy">Hapus catatan apa pun dari dasbor admin.</p>
        </div>

        <div class="dme-section-stack">
            @forelse ($notes as $note)
                <div class="dme-alert dme-alert--neutral">
                    <div class="dme-section-stack">
                        <div class="dme-section-header__content">
                            <p><strong>Catatan #{{ $note->id }}</strong> untuk siswa #{{ $note->student_id }}</p>
                            <p class="dme-section-copy">{{ $note->author_name_snapshot }} ({{ $note->author_role_snapshot }})</p>
                        </div>

                        <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $note->body), 140) ?: 'Catatan hanya berisi gambar' }}</p>
                        <p class="dme-section-copy">{{ $note->note_date }} · {{ optional($note->created_at)->format('Y-m-d H:i') }}</p>

                        <form method="POST" action="{{ route('admin.notes.destroy', $note) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dme-button--danger">Hapus catatan</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="dme-empty-state">
                    <div class="dme-empty-state__icon" aria-hidden="true">N</div>
                    <h3 class="dme-empty-state__title">Belum ada catatan untuk dimoderasi</h3>
                    <p class="dme-empty-state__copy">Catatan terbaru akan muncul di sini saat perlu ditinjau.</p>
                </div>
            @endforelse
        </div>
    </article>

    <article class="dme-section-card dme-section-stack">
        <div class="dme-section-header__content">
            <h2 class="dme-section-title">Gambar Catatan Terbaru</h2>
            <p class="dme-section-copy">Hapus gambar catatan apa pun dari dasbor admin.</p>
        </div>

        <div class="dme-section-stack">
            @forelse ($noteImages as $noteImage)
                <div class="dme-alert dme-alert--neutral">
                    <div class="dme-section-stack">
                        <div class="dme-section-header__content">
                            <p><strong>Gambar #{{ $noteImage->id }}</strong> pada catatan #{{ $noteImage->note_id }}</p>
                            <p>{{ $noteImage->original_filename ?: 'Gambar catatan tersimpan' }}</p>
                        </div>

                        <p class="dme-section-copy">{{ $noteImage->mime_type }} · {{ number_format((int) $noteImage->size_bytes) }} bytes</p>

                        <form method="POST" action="{{ route('admin.note-images.destroy', $noteImage) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dme-button--danger">Hapus gambar</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="dme-empty-state">
                    <div class="dme-empty-state__icon" aria-hidden="true">I</div>
                    <h3 class="dme-empty-state__title">Belum ada gambar untuk dimoderasi</h3>
                    <p class="dme-empty-state__copy">Unggahan terbaru akan muncul di sini saat perlu dimoderasi.</p>
                </div>
            @endforelse
        </div>
    </article>
</section>
