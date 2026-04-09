@props([
    'value' => '',
])

<form method="GET" action="{{ route('teacher.students.index') }}" class="teacher-roster__search">
    <div class="teacher-roster__search-field">
        <label for="teacher-student-search" class="sr-only">Cari murid</label>
        <span class="teacher-roster__search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="M20 20l-3.5-3.5"></path>
            </svg>
        </span>
        <input
            id="teacher-student-search"
            class="teacher-roster__search-input"
            name="search"
            type="search"
            value="{{ $value }}"
            placeholder="Cari murid..."
        >
    </div>
</form>
