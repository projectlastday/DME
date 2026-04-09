@props([
    'activeTab' => 'teacher',
])

<nav class="dme-tab-list" aria-label="Student note tabs">
    <a
        href="{{ route('student.notes.index', ['tab' => 'teacher']) }}"
        @class(['dme-tab', 'is-active' => $activeTab === 'teacher'])
        @if ($activeTab === 'teacher') aria-current="page" @endif
    >
        Teacher Notes
    </a>

    <a
        href="{{ route('student.notes.index', ['tab' => 'mine']) }}"
        @class(['dme-tab', 'is-active' => $activeTab === 'mine'])
        @if ($activeTab === 'mine') aria-current="page" @endif
    >
        My Notes
    </a>
</nav>
